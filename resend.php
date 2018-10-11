<?php
require "db.php";
session_start();

// copy the email sending code in here

// Make sure they have a session for this specifically, else send them back to login
if (!isset($_SESSION["unconfirmed_email"]) || empty($_SESSION["unconfirmed_email"])) {
    header("location: login.php");
}

try {
    // If any + signs in email address, replace them with unicode so it doesn't break the Couch query.
    $urlencoded_email = urlencode($_SESSION["unconfirmed_email"]);

    // Get doc from Couch
    $couchViewAndKey = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$urlencoded_email}\"";
    $response = $client->request("GET", $couchViewAndKey);
    $json = $response->getBody()->getContents();
    $decoded_json = json_decode($json, true);
    $couch_rows_arr = $decoded_json["rows"];

    // Get the full doc with the ID so we can add the token to it
    $name_id = $couch_rows_arr[0]["id"];
    $response = $client->request("GET", "/phpusers/$name_id");
    $json = $response->getBody()->getContents();
    $decoded_json = json_decode($json, true);

    // If their code is already activated they shouldn't be loading this page, redirect them to profile
    if ($decoded_json["activation_code"] === "activated") {
        header("location: profile.php");
    }

    // OTHERWISE WE CONTINUE AND MAKE A NEW CODE, PUT BACK TO THE DB AND SEND A NEW EMAIL
    $new_code = bin2hex(random_bytes(4));
    $decoded_json["activation_code"] = $new_code;

    // Actually re-encode and send the json back with a PUT
    $response = $client->request("PUT", "/phpusers/$name_id", [
        "json" => $decoded_json
    ]);

    // Sending email address TODO: change this to actual url or whatever
    $email = "totallyrealemail@hotmail.com";
    // Recipient email address
    $to = $decoded_json["email"];
    $subject = "PHPCYOA - complete your registration!";
    $message = "Your email has been registered for PHPCYOA and requested a new confirmation code. To activate your account, simply enter the following code on the activation screen: \n" . $decoded_json["activation_code"] . "\nIf you did not register, you can just ignore this. Otherwise, we hope you enjoy the game!";

    // Create email headers
    $headers = "From: " . $email . "\r\n" .
    "Reply-To: ". $email . "\r\n" .
    "X-Mailer: PHP/" . phpversion();

    // Sending email and redirecting to account activation page
    if (mail($to, $subject, $message, $headers)) {
        header("location: account_activation.php");
    } else {
        array_push($input_error_array, "Unable to send email. Please try again!");
    }


    header("location: account_activation.php");

} catch (Exception $e) {
    array_push($input_error_array, "Error connecting to database. Please try again later.");
}
