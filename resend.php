<?php
require "couch_functions.php";
session_start();

// copy the email sending code in here

// Make sure they have a session for this specifically, else send them back to login
if (!isset($_SESSION["unconfirmed_email"]) || empty($_SESSION["unconfirmed_email"])) {
    header("location: login.php");
}

try {
    // If any + signs in email address, replace them with unicode so it doesn't break the Couch query.
    $urlencoded_email = urlencode($_SESSION["unconfirmed_email"]);

    // Get from Couch by email with full doc included
    $couchViewKey = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$urlencoded_email}\"&include_docs=true";
    $couch_rows_arr = couch_get_decode_json($couchViewKey);

    // We get the full doc with ["doc"] so we can add the token to it
    // If their code is already activated they shouldn't be loading this page, redirect them to profile
    if ($couch_rows_arr[0]["doc"]["activation_code"] === "activated") {
        header("location: profile.php");
    }

    // OTHERWISE WE CONTINUE AND MAKE A NEW CODE, PUT BACK TO THE DB AND SEND A NEW EMAIL
    $new_code = bin2hex(random_bytes(4));
    $couch_rows_arr[0]["doc"]["activation_code"] = $new_code;

    // Actually re-encode and send the json doc back with a PUT to the ID
    couch_put_or_post("PUT", $couch_rows_arr[0]["id"], $couch_rows_arr[0]["doc"]);

    // Sending email address TODO: change this to actual url or whatever
    $email = "totallyrealemail@hotmail.com";
    // Recipient email address (the key in that Couch view)
    $to = $couch_rows_arr[0]["key"];
    $subject = "PHPCYOA - complete your registration!";

    $message = <<<ENDOFHEREDOCTEXT
Your email was registered for PHPCYOA and a new activation code was requested. To activate the account, simply visit:

http://localhost:5000/account_activation.php?code={$new_code}

Or enter the following code on the activation screen:

{$new_code}

If you did not register, you can just ignore this. Otherwise, we hope you enjoy the game!
ENDOFHEREDOCTEXT;

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
