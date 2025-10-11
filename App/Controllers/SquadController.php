<?php
namespace App\Controllers;
use Flight;
use PDO;
use PDOException;
use App\Models\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\RequestException;

class SquadController 
{
    private $pdo;
    private $baseUrl;
    private $baseTestUrl;
    private $secretKey;
    private $publicKey;
    private $secretKeytest;
    private $publicKeytest;
    private $transactionModel;
    public function __construct()
    {
        $this->baseUrl           = $_ENV['SQUAD_BASE_URL'];
        $this->baseTestUrl       = $_ENV['SQUAD_BASE_URL_TEST'];
        $this->secretKey         = $_ENV['SQUAD_SECRET'];
        $this->publicKey         = $_ENV['SQUAD_PUBLIC'];
        $this->secretKeytest     = $_ENV['SQUAD_SECRET_TEST'];
        $this->publicKeytest     = $_ENV['SQUAD_PUBLIC_TEST'];
    }
    
    
    public function init() 
    {
    $r = Flight::request()->query;
    $email = $r['email'] ?? '';
    $amount = isset($r['amount']) ? intval($r['amount']) * 100 : 0;
    $callback = $r['callback_url'] ?? '';
    $ref = $r['ref'] ?? time() . rand(1000, 9999);
    $redirect = isset($r['red']) && $r['red'] === 'true';

    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->post("{$this->baseUrl}/transaction/Initiate", [
            'headers' => [
                'Authorization' => "Bearer {$this->secretKey}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'email'           => $email,
                'amount'          => $amount,
                'callback_url'    => $callback,
                'transaction_ref' => $ref,
                'initiate_type'   => 'inline',
                'currency'        => 'NGN',
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        $checkout_url = $body['data']['checkout_url'] ?? null;
        $reference = $body['data']['transaction_ref'] ?? null;

        if ($redirect && isset($body['data']['checkout_url'])) {
            Flight::redirect($body['data']['checkout_url']);
            return;
        }

        Flight::json([
            'status' => true, 
            'message' => 'Checkout URL created', 
            'data' => [
                'authorization_url' => $checkout_url,
                'reference' => $reference,
            ], 
        ], 200);

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

    $this->transactionModel = new Transaction();

    // Check if transaction already exists in the database
    $existingTransaction = $this->transactionModel->findByReference($reference);

    if ($existingTransaction) {
        // If it exists, return data from the database without calling the API
        return Flight::json([
            'status' => true,
            'message' => 'Transaction previously verified and retrieved from log',
            'data' => $existingTransaction
        ]);
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

        // Since we checked above and it didn't exist, we now insert it.
            // Insert new transaction
            $transactionData = [
                'email'     => $data['email'] ?? '',
                'amount'    => $data['transaction_amount'] / 100,
                'reference' => $data['transaction_ref'],
                'status'    => $data['transaction_status'],
                'fees'      => 0.00,
                'paid_at'   => $data['created_at'],
                'channel'   => $data['transaction_type'],
                'currency'  => $data['transaction_currency_id'],
                'ip_address'=> $data['ip_address'] ?? '',
                'service'   => 'squad'
            ];
            $this->transactionModel->create($transactionData);

        return Flight::json([
            'status' => true,
            'message' => 'Transaction verified and logged',
            'data' => [
                'email'     => $data['email'] ?? '',
                'amount'    => $data['transaction_amount'] / 100,
                'reference' => $data['transaction_ref'],
                'status'    => $data['transaction_status'],
                'fees'      => 0.00,
                'paid_at'   => $data['created_at'],
                'channel'   => $data['transaction_type'],
                'currency'  => $data['transaction_currency_id'],
                'ip_address'=> $data['ip_address'] ?? '',
                'service'   => 'squad'
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
    }
}


}