<?php
require "db.php";
require "session_cookie_checker.php";
session_start();


// Check if no session cookie or token cookie and if so user shouldn't be here so send them back to login
if (session_cookie_check() === false) {
    header("location: login.php");
}

// Get email from session before we destroy it
// URLencode so it will replace any pluses (for example) and avoid breaking the Couch query
$email = urlencode($_SESSION["email"]);

// Get the token first so we can delete it later
if (isset($_COOKIE["et_cookie"]) && !empty($_COOKIE["et_cookie"])) {
    $raw_token = json_decode($_COOKIE["et_cookie"]) -> t;

    // Delete the cookie token from DB if it exists
    // 404 or database error catching with try/catch
    try {
        // Get doc ID with email
        $response = $client->request("GET", "phpusers/_design/views/_view/emails-and-passwords?key=\"{$email}\"");
        $json = $response->getBody()->getContents();
        $decoded_json = json_decode($json, true);
        $couch_rows_arr = $decoded_json["rows"];
        $id_to_update = $couch_rows_arr[0]["id"];

        // Get original full doc so we can return it in full after we edit it
        $response = $client->request("GET", "/phpusers/$id_to_update");
        $json = $response->getBody()->getContents();
        $decoded_json = json_decode($json, true);
        // Hashed tokens are an array
        $hashed_tokens_array = $decoded_json["cookie_tokens"];

        // Loop through hashed tokens looking for whatever matches if we hash the input token
        for ($i = 0; $i < $hashed_tokens_array; $i+=1) {
            if (password_verify($raw_token, $hashed_tokens_array[$i])) {
                // Token matches, so we can delete it from the array and get out of the loop
                unset($decoded_json["cookie_tokens"][$i]);
                break;
            }
        }

        // Actually re-encode and send the json back with a PUT
        $response = $client->request("PUT", "/phpusers/$id_to_update", [
            "json" => $decoded_json
        ]);
        echo "success";
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
