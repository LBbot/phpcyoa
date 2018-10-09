<?php

function session_cookie_check()
{
    // Due to function scope we have to require the Guzzle Client class again inside the function
    require "db.php";

    // Check if session cookie
    if (isset($_SESSION["email"]) && !empty($_SESSION["email"])) {
        return true;
    // If NO session but user DOES have a cookie
    } elseif (isset($_COOKIE["et_cookie"]) && !empty($_COOKIE["et_cookie"])) {
        //check CouchDB for token match and if it matches we reissue session cookie
        try {
            // Get email from cookie (decode json string value of cookie back to associative array, to get e value)
            // Encode it back for URLs so it doesn't break the Couch query!
            $urlencoded_email = urlencode(json_decode($_COOKIE["et_cookie"]) -> e);
            $raw_token = json_decode($_COOKIE["et_cookie"]) -> t;

            // Use email to get document with hashed token array from Couch view
            $couchViewAndKey = "phpusers/_design/views/_view/emails-and-cookie-tokens?key=\"{$urlencoded_email}\"";
            $response = $client->request("GET", $couchViewAndKey);
            $json = $response->getBody()->getContents();
            $decoded_json = json_decode($json, true);
            // Hashed tokens are an array (so user can log in on multiple devices/sessions)
            $hashed_tokens_array = $decoded_json["rows"][0]["value"];

            // Loop through hashed tokens array and see if hashing the raw token inside the cookie (t) matches any of
            // the hashes in the DB.
            foreach ($hashed_tokens_array as $hashed_token) {
                if (password_verify($raw_token, $hashed_token)) {
                    // Token matches, so let's give the user a new session cookie to authenticate!
                    // We get the email address from the Couch doc so its correctly not url encoded and is safe
                    $_SESSION["email"] = $decoded_json["rows"][0]["key"];
                    return true;
                }
            }
        } catch (Exception $e) { // Database error
            // TODO: can we pass an error to the array on this other page?????????????????
            // doing nothing seems to pass to the final return anyway? Though we should have an error.
        }
        // If no session and no matching cookie, send unauthenticated user back to login.
    }
    return false;
}
