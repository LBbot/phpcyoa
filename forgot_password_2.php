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

// enter code

// SHOULD THE PREVIOUS FORM POST TO THIS PAGE SO WE HAVE EMAIL IN THE POST AND CAN'T GO HERE WITHOUT IT?!?!?!?!?!?!??!

// WILL HAVE TO MAKE SURE WHETHER THEY ARE POSTING FROM LAST PAGE OR POSTING TO THIS PAGE





// if they post the form on the page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Empty array for errors to start
    $input_error_array = array();

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

// If any errors: show them
if (!empty($input_error_array)) {
    foreach ($input_error_array as $single_error) {
        echo "<h3>$single_error</h3>";
    }
}
?>

    <p>The email may take a few moments to arrive. Be sure to check your spam folder. Enter the passcode into the form
    below. </p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method = "post">
        <label for="confirmcode">Passcode: </label>
        <input class="form-textbox" type="text" id="passcode" name="passcode" maxlength="256"><br>
        <input class="custom-button" type="submit" class="js-submit" value="Submit">
    </form>

    <p>Alternatively you can go <a href="forgot_password_1.php">back to the previous page</a> to send another email and
    code.</p>

</body>
</html>
