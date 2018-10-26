<?php
require "couch_functions.php";
require "session_cookie_checker.php";
session_start();

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

// We add any session errors from validating in forgot_password_2.php into this page's store, and get rid of that
// session
if (isset($_SESSION["error"]) || !empty($_SESSION["error"])) {
    $input_error_array = array($_SESSION["error"]);
    unset($_SESSION["error"]);
}

// Set up page title and <head>/header, and container
$page_title = "Reset password - PHP CYOA";
include_once "head.php";

// If any errors: show them
if (!empty($input_error_array)) {
    foreach ($input_error_array as $single_error) {
        echo "<h3>$single_error</h3>";
    }
}
?>

    <p>Enter your email address below and we'll send a one-time use passcode to that email address that will allow you
    to create a new password.</p>

    <form action="forgot_password_2.php" method = "post">
        <label for="email">Email address: </label>
        <input class="form-textbox" type="text" id="email" name="email" maxlength="256"><br>
        <input class="custom-button" type="submit" class="js-submit" value="Submit">
    </form>

    <p><a href="login.php">Click here to go back</a>.</p>

</body>
</html>
