<?php
namespace App\Middleware;
use Flight;
class SecurityMiddleware
{
    public static function apply()
    {   
        Flight::before('start', function(&$params, &$output) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");

            // Handle preflight requests
            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                header("HTTP/1.1 200 OK");
                exit();
            }
        });
    }
}
