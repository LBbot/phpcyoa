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

// DO A SESSION CHECK ON EMAIL AND FLAG (see var_dump)







// if they post the form on the page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Empty array for errors to start
    $input_error_array = array();

    $hashedcode = $_POST["passcode"];
    // escape code maybe
    try {
        // compare it with what's in the db


    } catch (Exception $e) {
        array_push($input_error_array, "Error connecting to database. Please try again later.");
    }
}


// Set up page title and <head>/header, and container
$page_title = "Reset password - PHP CYOA";
include_once "head.php";
var_dump($_SESSION);
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
