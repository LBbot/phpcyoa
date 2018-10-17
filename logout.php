<?php
require "couch_functions.php";
require "session_cookie_checker.php";
session_start();


// Check if no session cookie or token cookie and if so user shouldn't be here so send them back to login
if (session_cookie_check() === false) {
    header("location: login.php");
    exit();
}

// Get the token first so we can delete it later
if (isset($_COOKIE["et_cookie"]) && !empty($_COOKIE["et_cookie"])) {
    $raw_token = json_decode($_COOKIE["et_cookie"]) -> t;

    // Delete the cookie token from DB if it exists
    // 404 or database error catching with try/catch
    try {
        // Get email from session before we destroy it
        // URLencode so it will replace any pluses (for example) and avoid breaking the Couch query
        $email = urlencode($_SESSION["email"]);

        // Get doc ID with email
        $couchViewKey = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$email}\"&include_docs=true";
        $couch_rows_arr = couch_get_decode_json($couchViewKey);

        // Hashed tokens are an array
        $hashed_tokens_array = $couch_rows_arr[0]["doc"]["cookie_tokens"];

        // Loop through hashed tokens looking for whatever matches if we hash the input token
        for ($i = 0; $i < $hashed_tokens_array; $i+=1) {
            if (password_verify($raw_token, $hashed_tokens_array[$i])) {
                // Token matches, so we can delete it from the array and get out of the loop
                unset($couch_rows_arr[0]["doc"]["cookie_tokens"][$i]);
                break;
            }
        }

        // Actually re-encode and send the json back with a PUT
        couch_put_or_post("PUT", $couch_rows_arr[0]["id"], $couch_rows_arr[0]["doc"]);

    } catch (Exception $e) {
        $custom_error = "Error connecting to database. Please try again later.";
        echo $custom_error;
    }
}

// Delete cookie by setting it blank and re-setting the expiry to a day ago
setcookie("et_cookie", "", time()-3600);

// Make session an empty array for extra security before we destroy it
$_SESSION = array();
session_destroy();

header("location: login.php");
