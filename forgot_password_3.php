<?php
require "couch_functions.php";
require "session_cookie_checker.php";
session_start();

// VALIDATE if user should be here from cookies/sessions!
// Check if session cookie or token cookie and if so: send logged in user to profile
if (session_cookie_check()) {
    header("location: profile.php");
    exit();
}
// Check if user has unactivated account and redirect to activation page if so.
if (isset($_SESSION["unconfirmed_email"]) && !empty($_SESSION["unconfirmed_email"])) {
    header("location: account_activation.php");
    exit();
}
//If they haven't got an pwchange_email in their session, they shouldn't be here and go back to step 1
if (!isset($_SESSION["pwchange_email"]) || empty($_SESSION["pwchange_email"])) {
    header("location: forgot_password_1.php");
    exit();
}
// If their pwchange is NOT true, they should be on step 2:
if ($_SESSION["pwchange"] !== true) {
    header("location: forgot_password_2.php");
    exit();
}


// if they post the form on the page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Empty array for errors to start
    $input_error_array = array();

    // If any + signs in email address, replace them with unicode so it doesn't break the Couch query.
    $encoded_email = urlencode($_SESSION["pwchange_email"]);

    // Ensure no inputs are left blank
    if ($_POST["password"] == "" || $_POST["passwordconfirm"] == "") {
        array_push($input_error_array, "Both fields are required and cannot be left blank.");
    }

    // Ensure password input and confirmation input match
    if ($_POST["password"] !== $_POST["passwordconfirm"]) {
        array_push($input_error_array, "Passwords must match");
    }

    if (empty($input_error_array)) {
        try {
            // Let's get the full doc so we can rewrite the hashed password and do a PUT
            $cpath = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$encoded_email}\"&include_docs=true";
            $couch_output_arr = couch_get_decode_json($cpath);
            $fullDoc = $couch_output_arr[0]["doc"];

            // Set the rounds on bcrypt for passcode hashing
            $options = ["cost" => 12];
            $fullDoc["password"] = password_hash($_POST["password"], PASSWORD_BCRYPT, $options);

            // Delete the passcode from the doc for security
            unset($fullDoc["password_reset_code"]);

            // Confirm an unconfirmed account with this because, why not? They've proved their email.
            $fullDoc["activation_code"] = "activated";

            // Create a random token for cookie
            $token = bin2hex(random_bytes(12));

            // hash token for storage (options defined earlier).
            $hashedToken = password_hash($token, PASSWORD_BCRYPT, $options);

            // To stop CouchDB filling up with old expired token hashes, when we reach a certain number - we delete
            // the first one in the array before we add a new one to the end
            if (count($fullDoc["cookie_tokens"]) > 2) {
                array_shift($fullDoc["cookie_tokens"]);
            }

            // Push the token to the cookie_tokens array
            array_push($fullDoc["cookie_tokens"], $hashedToken);

            // Actually re-encode and send the json doc back with a PUT to the ID
            couch_put_or_post("PUT", $fullDoc["_id"], $fullDoc);

            // Delete those temporary sessions - make session an empty array for extra security before we destroy
            $_SESSION = array();
            session_destroy();

            // FINALLY we just go through the LOGIN process! Start with creating a session with user's email
            $_SESSION["email"] = $fullDoc["email"];
            // Set expiry for cookie
            $thirtyDaysExpiry = time() + 86400 * 30;
            // Put users email (e) and raw token (t) in an ASSOCIATIVE ARRAY flattened to an encoded json STRING
            // so we can put both into the value of the cookie and decode when we need it
            $email_and_token = (json_encode(array("e" => $fullDoc["email"], "t" => $token)));
            // HTTPS (boolean 1 of 2) set to false because localhost. SET IT TO TRUE ON A SERVER WITH SSL
            setcookie("et_cookie", $email_and_token, $thirtyDaysExpiry, "/", "", false, false);
            // And FINALLY redirect user to profile
            header("location: profile.php");
            exit();

        } catch (Exception $e) {
            array_push($input_error_array, "Error connecting to database. Please try again later.");
        }
    }
}


// Set up page title and <head>/header
$page_title = "Reset password - PHP CYOA";
include_once "head.php";
?>

    <p>Now that your email is verified, you can enter your new password below.</p>

    <form method = "post">

        <p><?php echo htmlspecialchars($_SESSION["pwchange_email"]) ?></p>

        <label for="password">Password: </label>
        <input class="form-textbox" type="password" id="password" name="password" maxlength="256"><br>

        <label for="passwordconfirm">Confirm password: </label>
        <input class="form-textbox" type="password" id="passwordconfirm" name="passwordconfirm" maxlength="256"><br>

        <input class="custom-button" type="submit" class="js-submit" value="Confirm">
    </form>

</body>
</html>
