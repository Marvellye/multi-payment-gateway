<?php
namespace App\Controllers;
use Flight;
use PDO;
use PDOException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\RequestException;

class PaystackController 
{
    private $pdo;
    private $baseUrl;
    private $secretKey;
    private $publicKey;
    private $secretKeytest;
    private $publicKeytest;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->baseUrl           = $_ENV['PAYSTACK_BASE_URL'];
        $this->secretKey         = $_ENV['PAYSTACK_SECRET'];
        $this->publicKey         = $_ENV['PAYSTACK_PUBLIC'];
        $this->secretKeytest     = $_ENV['PAYSTACK_SECRET_TEST'];
        $this->publicKeytest     = $_ENV['PAYSTACK_PUBLIC_TEST'];
    }
    
    public function index()
    {
        echo Flight::get('blade')->render('paystack.index', [
            'publicKey' => $this->publicKey
            ]);
    }
    
    public function inline()
    {
        $request = Flight::request()->query;

        echo Flight::get('blade')->render('paystack.inline', [
            'publicKey'   => $this->publicKey,
            'email'       => $request['email'] ?? '',
            'amount'      => $request['amount'] ?? '',
            'firstName'   => $request['first_name'] ?? '',
            'lastName'    => $request['last_name'] ?? '',
            'phone'       => $request['phone'] ?? '',
            'callbackUrl' => $request['callback_url'] ?? ''
        ]);
    }

    public function init() 
    {
    $r = Flight::request()->query;
    $email = $r['email'] ?? '';
    $amount = isset($r['amount']) ? intval($r['amount']) * 100 : 0;
    $callback = $r['callback_url'] ?? '';
    $ref = $r['ref'] ?? '';
    $redirect = isset($r['red']) && $r['red'] === 'true';

    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->post("{$this->baseUrl}/transaction/initialize", [
            'headers' => [
                'Authorization' => "Bearer {$this->secretKey}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'email'        => $email,
                'amount'       => $amount,
                'callback_url' => $callback,
                'reference'    => $ref,
                'metadata' => [ 'custom_fields' => [
                    [
                        'display_name'   => 'Full Name',
                        'variable_name'  => 'full_name',
                        'value'          => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))
                    ],
                    [
                        'display_name'   => 'Phone Number',
                        'variable_name'  => 'phone_number',
                        'value'          => $r['phone'] ?? ''
                    ]
                ]]
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        if ($redirect && isset($body['data']['authorization_url'])) {
            Flight::redirect($body['data']['authorization_url']);
            return;
        }

        Flight::json($body);

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        $msg = $e->getMessage();
        if ($e->hasResponse()) {
            $resp = $e->getResponse();
            $msg .= ' | HTTP: ' . $resp->getStatusCode();
            $msg .= ' | Body: ' . $resp->getBody()->getContents();
        }
        Flight::json(['status' => false, 'message' => 'Payment initialization failed', 'error' => $msg], 500);
    }
}

public function verify()
{
    $request = Flight::request()->query;
    $reference = $request['reference'] ?? null;

    if (!$reference) {
        return Flight::json([
            'status' => false,
            'message' => 'Transaction reference is missing',
        ], 400);
    }

    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->get("{$this->baseUrl}/transaction/verify/{$reference}", [
            'headers' => [
                'Authorization' => "Bearer {$this->secretKey}",
                'Accept'        => 'application/json',
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        if (!$body['status']) {
            return Flight::json([
                'status' => false,
                'message' => 'Verification failed',
                'data' => $body['data'] ?? []
            ], 400);
        }

        $data = $body['data'];

        // Check if transaction already exists
        $stmt = $this->pdo->prepare("SELECT id FROM transaction WHERE reference = :reference LIMIT 1");
        $stmt->execute(['reference' => $data['reference']]);
        $exists = $stmt->fetch();

        if (!$exists) {
            // Insert new transaction
            $insert = $this->pdo->prepare("
                INSERT INTO transaction (
                    email, amount, reference, status, fees, paid_at, channel, currency, ip_address, service
                ) VALUES (
                    :email, :amount, :reference, :status, :fees, :paid_at, :channel, :currency, :ip_address, :service
                )
            ");

            $insert->execute([
                'email'     => $data['customer']['email'] ?? '',
                'amount'    => $data['amount'] / 100, // Convert from kobo to naira
                'reference' => $data['reference'],
                'status'    => $data['status'],
                'fees'      => $data['fees'] / 100 ?? 0,
                'paid_at'   => $data['paid_at'],
                'channel'   => $data['channel'],
                'currency'  => $data['currency'],
                'ip_address'=> $data['ip_address'] ?? '',
                'service'   => 'paystack'
            ]);
        }

        return Flight::json([
            'status' => true,
            'message' => 'Transaction verified and logged',
            'data' => [
                'email'     => $data['customer']['email'] ?? '',
                'amount'    => $data['amount'] / 100,
                'reference' => $data['reference'],
                'status'    => $data['status'],
                'fees'      => $data['fees'] / 100 ?? 0,
                'paid_at'   => $data['paid_at'],
                'channel'   => $data['channel'],
                'currency'  => $data['currency'],
                'ip_address'=> $data['ip_address'] ?? '',
                'service'   => 'paystack'
            ]//$data
        ]);

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        $msg = $e->getMessage();
        if ($e->hasResponse()) {
            $msg .= ' | ' . $e->getResponse()->getBody()->getContents();
        }
        return Flight::json([
            'status' => false,
            'message' => 'Verification failed',
            'error' => $msg
        ], 500);
    } catch (\PDOException $e) {
        return Flight::json([
            'status' => false,
            'message' => 'Database error',
            'error' => $e->getMessage()
        ], 500);
    }
}


}