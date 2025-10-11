<?php

require './vendor/autoload.php'; // Ensure you load Composer's autoload
date_default_timezone_set('Africa/Lagos');
use Dotenv\Dotenv;
use Jenssegers\Blade\Blade;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();  

$dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

//Declare assets function
function asset($path) {
    return '/assets/' . ltrim($path, '/');
}

// Blade setup
$views = __DIR__ . '/views';
$cache = __DIR__ . '/cache';
$blade = new Blade($views, $cache);

// Share Blade with Flight
Flight::set('blade', $blade);


// Allow CORS for a specific domain
Flight::before('start', function(&$params, &$output) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header("HTTP/1.1 200 OK");
        exit();
    }
});

// Handle 404 Not Found errors
Flight::map('notFound', function(){
    // Render a custom 404 error page
    Flight::json([
            'success' => 'false',
            'status' => '404',
            'message' => 'Sorry, the page you are looking for does not exist',
            'api_url' => $_ENV[BASE_URL]
            ]);
});

Flight::route('/', function () {
    echo Flight::get('blade')->render('index', ['title' => 'Marvelly Payment Gateway']);
});

Flight::group('/live', function() use ($pdo){ 
    //Paystack
    Flight::route('GET /paystack', function() use ($pdo) {
        $controller = new App\Controllers\PaystackController($pdo);
        $controller->index();
    });
    Flight::route('GET /paystack/inline', function() use ($pdo) {
        $controller = new App\Controllers\PaystackController($pdo);
        $controller->inline();
    });
    Flight::route('GET /paystack/init', function() use ($pdo) {
        $controller = new App\Controllers\PaystackController($pdo);
        $controller->init();
    });
    Flight::route('GET /paystack/verify', function() use ($pdo) {
        $controller = new App\Controllers\PaystackController($pdo);
        $controller->verify();
    });
});

Flight::group('/test', function() use ($pdo){ 
    //Paystack
    Flight::route('GET /paystack', function() use ($pdo) {
        $controller = new App\Controllers\PaystackTestController($pdo);
        $controller->index();
    });
    Flight::route('GET /paystack/inline', function() use ($pdo) {
        $controller = new App\Controllers\PaystackTestController($pdo);
        $controller->inline();
    });
    Flight::route('GET /paystack/init', function() use ($pdo) {
        $controller = new App\Controllers\PaystackTestController($pdo);
        $controller->init();
    });
    Flight::route('GET /paystack/verify', function() use ($pdo) {
        $controller = new App\Controllers\PaystackTestController($pdo);
        $controller->verify();
    });
});

Flight::start();