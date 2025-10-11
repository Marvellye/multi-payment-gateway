<?php
require './vendor/autoload.php'; 
date_default_timezone_set('Africa/Lagos');
use Dotenv\Dotenv;
use Medoo\Medoo;
use Jenssegers\Blade\Blade;
use Spatie\Ignition\Ignition;
use App\Middleware\SecurityMiddleware;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load(); 
SecurityMiddleware::apply();
$ignition = Ignition::make()
    ->applicationPath(__DIR__)
    ->useDarkMode()
    ->shouldDisplayException($_ENV['APP_ENV'] === 'local')
    ->register();

Flight::set('db', new Medoo([
    'type' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_NAME'],
    'username' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8mb4'
]));

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
            'api_url' => $_ENV['BASE_URL']
            ]);
});
Flight::map('error', function(Throwable $e) use ($ignition) {
    if ($_ENV['APP_ENV'] === 'local') {
        $ignition->handleException($e);
    } else {
        Flight::response()->status(500);
        echo Flight::get('blade')->render('errors.500', [
            'title' => 'Server Error',
            'message' => 'Something went wrong. Please try again later.'
        ]);
    }
});

Flight::route('/', function () {
    echo Flight::get('blade')->render('index', ['title' => 'Marvelly Payment Gateway']);
});

Flight::group('/live', function(){ 
    //Paystack
    Flight::route('GET /paystack', ['App\Controllers\PaystackController', 'index']);
    Flight::route('GET /paystack/inline', ['App\Controllers\PaystackController', 'inline']);
    Flight::route('GET /paystack/init', ['App\Controllers\PaystackController', 'init']);
    Flight::route('GET /paystack/verify', ['App\Controllers\PaystackController', 'verify']);
    //Squad
    Flight::route('GET /squad/init', ['App\Controllers\SquadTestController', 'init']);
    Flight::route('GET /squad/verify', ['App\Controllers\SquadTestController', 'verify']);
});

Flight::group('/test', function(){ 
    //Paystack
    Flight::route('GET /paystack', ['App\Controllers\PaystackTestController', 'index']);
    Flight::route('GET /paystack/inline', ['App\Controllers\PaystackTestController', 'inline']);
    Flight::route('GET /paystack/init', ['App\Controllers\PaystackTestController', 'init']);
    Flight::route('GET /paystack/verify', ['App\Controllers\PaystackTestController', 'verify']);
    //squad
    Flight::route('GET /squad/init', ['App\Controllers\SquadTestController', 'init']);
    Flight::route('GET /squad/verify', ['App\Controllers\SquadTestController', 'verify']);
});

Flight::start();