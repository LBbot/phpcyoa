<?php
require "couch_functions.php";
require "session_cookie_checker.php";
session_start();

// - validate email input first
// - get from email in session
// - compare password
// - update their email
// - write them a new activation code
// - PUT to Couchdb
// - edit sessions/cookies!
//-  send email
// - send to account_activation.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Empty array for errors to start
    $input_error_array = array();

    // $_POST contains the form data as associative array. We strip any whitespace
    $_POST["email"] = trim($_POST["email"]);
    // Check for blank data
    foreach ($_POST as $form_entry) {
        if ($form_entry == "") {
            array_push($input_error_array, "All fields are required and cannot be left blank.");
            break;
        }
    }

    // trim email, sanitize filter, then validation filter. If false, don't get db or do anything else
    if (filter_var(filter_var($_POST["email"], FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL) === false) {
        array_push($input_error_array, "Invalid email");
        //check for backslashes, if it returns a number (not false), skip everything else below
    } elseif (strpos($_POST["email"], "\\") !== false) {
        array_push($input_error_array, "Email cannot include backslashes.");
    } else {
        // 404 or database error catching with try/catch
        try {
            // Check for duplicate email, by getting Couch view of emails with key of the email posted
            $cpath = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$_POST["email"]}\"";
            $couch_output_arr = couch_get_decode_json($cpath);

            // If it doesn't return an empty array, the email is in use
            if (empty($couch_output_arr) === false) {
                array_push($input_error_array, "Email address already in use. Please log in or try another.");
            }
        } catch (Exception $e) {
            array_push($input_error_array, "Error connecting to database. Please try again later.");
        }
    }

    // If any + signs in email address, replace them with unicode so it doesn't break the Couch query.
    $encoded_email = urlencode($_SESSION["email"]);

    try {
        // get the full doc
        $cpath = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$encoded_email}\"&include_docs=true";
        $couch_output_arr = couch_get_decode_json($cpath);
        $fullDoc = $couch_output_arr[0]["doc"];

        // Compare password
        $hashed_password = $couch_output_arr[0]["value"];
        if (!password_verify($_POST["password"], $hashed_password)) {
            array_push($input_error_array, "Password is incorrect.");
        }

        // If no errors, let's do the thing
        if (empty($input_error_array)) {
            // ACTUAL email change
            $fullDoc["email"] = $_POST["email"];
            // Reset all cookies for security
            $fullDoc["cookie_tokens"] = [];
            // Gen them a new activation code
            $fullDoc["activation_code"] = bin2hex(random_bytes(4));

            // Put to couch
            couch_put_or_post("PUT", $fullDoc["_id"], $fullDoc);

            // Sending email address TODO: change this to actual url or whatever
            $email = "totallyrealemail@hotmail.com";
            // Recipient email address
            $to = $_POST["email"];
            $subject = "PHPCYOA - confirm your new email address!";

            $message = <<<ENDOFHEREDOCTEXT
Your email has been registered for PHPCYOA. To re-activate your account, simply visit:

http://localhost:5000/account_activation.php?code={$fullDoc["activation_code"]}

Or enter the following code on the activation screen:

{$fullDoc["activation_code"]}

If you did not register, you can just ignore this. Otherwise, we hope you enjoy the game!
ENDOFHEREDOCTEXT;

            // Create email headers
            $headers = "From: " . $email . "\r\n" .
            "Reply-To: ". $email . "\r\n" .
            "X-Mailer: PHP/" . phpversion();

            // Sending email
            if (mail($to, $subject, $message, $headers)) {
                // WE USE SOME LOGOUT.PHP logic to reset cookies and sessions
                // Delete cookie by setting it blank and re-setting the expiry to a day ago
                setcookie("et_cookie", "", time()-3600);
                // Make session an empty array to reset it
                $_SESSION = array();

                // Then we write a new session and send user to account activation
                $_SESSION["unconfirmed_email"] = $_POST["email"];
                header("location: account_activation.php");
                exit();
            } else {
                array_push($input_error_array, "Unable to send email. Please try again!");
            }
        }
    } catch (Exception $e) {
        array_push($input_error_array, "Error connecting to database. Please try again later.");
    }
}


// Set up page title and <head>/header
$page_title = "Update your email address - PHP CYOA";
include_once "head.php";
?>

    <p>Update your email address</p>

    <form method = "post">
        <label for="email">New email address: </label>
        <input class="form-textbox" type="text" id="email" name="email" maxlength="256"><br>

        <label for="password">Please confirm your current password: </label>
        <input class="form-textbox" type="password" id="password" name="password" maxlength="256"><br>

        <input class="custom-button" type="submit" class="js-submit" value="Confirm">
    </form>

    <p>Changed your mind? <a href = "profile.php">Return to profile</a>.</p>

</body>
</html>
