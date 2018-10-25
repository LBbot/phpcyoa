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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Empty array for errors to start
    $input_error_array = array();

    // $_POST contains the form data as associative array
    // strip any whitespace
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

    // Compare password with confirmation
    if ($_POST["password"] !== $_POST["passwordconfirm"]) {
        array_push($input_error_array, "Passwords must match");
    }

    // Let's remove the confirm once tested, we don't need that in the DB
    unset($_POST["passwordconfirm"]);

        // If no errors, hash password, add date, post to couch and redirect to login
    if (empty($input_error_array)) {
        // Set the rounds on bcrypt for password hashing
        $options = ["cost" => 12];
        // replace password with hashed one
        $_POST["password"] = password_hash($_POST["password"], PASSWORD_BCRYPT, $options);

        // add date/time in machine readable and human readable form because hey, PHP can do that nicely
        // We push key/value pairs to associative array by defining it kinda like a property
        $_POST["date"] = date(DATE_ATOM);
        $_POST["readable_date"] = date("H:i:sA D d/m/Y");
        $_POST["cookie_tokens"] = [];
        $_POST["activation_code"] = bin2hex(random_bytes(4));
        // ACTUALLY POST TO COUCH (use "" because no ID is needed, it's going straight to /phpusers)
        try {
            couch_put_or_post("POST", "", $_POST);

            // Sending email address TODO: change this to actual url or whatever
            $email = "totallyrealemail@hotmail.com";
            // Recipient email address
            $to = $_POST["email"];
            $subject = "PHPCYOA - complete your registration!";

            $message = <<<ENDOFHEREDOCTEXT
Your email has been registered for PHPCYOA. To activate your account, simply visit:

http://localhost:5000/account_activation.php?code={$_POST["activation_code"]}

Or enter the following code on the activation screen:

{$_POST["activation_code"]}

If you did not register, you can just ignore this. Otherwise, we hope you enjoy the game!
ENDOFHEREDOCTEXT;

            // Create email headers
            $headers = "From: " . $email . "\r\n" .
            "Reply-To: ". $email . "\r\n" .
            "X-Mailer: PHP/" . phpversion();

            // Sending email
            if (mail($to, $subject, $message, $headers)) {
                $_SESSION["unconfirmed_email"] = $_POST["email"];
                header("location: account_activation.php");
                exit();
            } else {
                array_push($input_error_array, "Unable to send email. Please try again!");
            }
        } catch (Exception $e) {
            array_push($input_error_array, "Error connecting to database. Please try again later.");
        }
    }
}

// Set up page title and <head>/header
$page_title = "Register an account - PHP CYOA";
include_once "head.php";

// If any errors: show them
if (!empty($input_error_array)) {
    foreach ($input_error_array as $single_error) {
        echo "<h3>$single_error</h3>";
    }
}

?>

    <form method = "post">
        <label for="email">Email address: </label>
        <input class="form-textbox" type="text" id="email" name="email" maxlength="256"><br>

        <label for="password">Password: </label>
        <input class="form-textbox" type="password" id="password" name="password" maxlength="256"><br>

        <label for="passwordconfirm">Confirm password: </label>
        <input class="form-textbox" type="password" id="passwordconfirm" name="passwordconfirm" maxlength="256"><br>

        <input class="custom-button" type="submit" class="js-submit" value="Register">
    </form>

    <p>Already registered? <a href = "login.php">Log in here.</a></p>

</body>
</html>