<?php
// config file for DB

require "./vendor/autoload.php";
use GuzzleHttp\Client;

$client = new Client([
    // Base URI is used with relative requests
    "base_uri" => "http://127.0.0.1:5984/",
    "timeout"  => 2.0
]);
