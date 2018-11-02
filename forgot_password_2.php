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
// If their pwchange is set to true, they should be on step 3:
if ($_SESSION["pwchange"] === true) {
    header("location: forgot_password_3.php");
    exit();
}


// if they post the form on the page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Empty array for errors to start
    $input_error_array = array();

    // If any + signs in email address, replace them with unicode so it doesn't break the Couch query.
    $encoded_email = urlencode($_SESSION["pwchange_email"]);

    // escape code maybe
    try {
        // Get doc from Couch to get hashed passcode
        $cpath = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$encoded_email}\"&include_docs=true";
        $couch_output_arr = couch_get_decode_json($cpath);
        $hashed_passcode = $couch_output_arr[0]["doc"]["password_reset_code"];

        // User password verify to compare the two
        if (!password_verify($_POST["passcode"], $hashed_passcode)) {
            array_push($input_error_array, "Passcode is incorrect.");
        }

        if (empty($input_error_array)) {
            $_SESSION["pwchange"] = true;
            header("location: forgot_password_3.php");
            exit();
        }
    } catch (Exception $e) {
        array_push($input_error_array, "Error connecting to database. Please try again later.");
    }
}


// Set up page title and <head>/header, and container
$page_title = "Reset password step 2 - PHP CYOA";
include_once "head.php";
?>

    <p>The email may take a few moments to arrive. Be sure to check your spam folder. Enter the passcode into the form
    below. </p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method = "post">
        <label for="passcode">Passcode: </label>
        <input class="form-textbox" type="text" id="passcode" name="passcode" maxlength="256"><br>
        <input class="custom-button" type="submit" class="js-submit" value="Submit">
    </form>

    <p>Alternatively you can go <a href="forgot_password_1.php">back to the previous page</a> to send another email and
    code.</p>

</body>
</html>
