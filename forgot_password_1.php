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

// if they post the form on the page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Empty array for errors to start
    $input_error_array = array();

    // trim email, sanitize filter, then validation filter. If false, don't get db or do anything else
    if (filter_var(filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL) === false) {
        array_push($input_error_array, "Invalid email");
    } else {
        // If any + signs in email address, replace them with unicode so it doesn't break the Couch query.
        $urlencoded_email = urlencode($_POST["email"]);

        // 404 or database error catching with try/catch
        try {
            // Check for duplicate username, by getting Couch view of emails with key of the email posted
            // Get doc from Couch
            $cpath = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$urlencoded_email}\"&include_docs=true";
            $couch_output_arr = couch_get_decode_json($cpath);

            // If it returns an empty array, the username/email is not in use, so can't check password
            if (empty($couch_output_arr)) {
                array_push($input_error_array, "Email address does not exist.");
            }

            // If no errors, let's proceed
            if (empty($input_error_array)) {
                // Generate a code
                $password_reset_code = bin2hex(random_bytes(8));

                // Let's get the full doc so we can add a property and do a PUT
                $fullDoc = $couch_output_arr[0]["doc"];
                $fullDoc["password_reset_code"] = $password_reset_code;

                // Actually re-encode and send the json doc back with a PUT to the ID
                couch_put_or_post("PUT", $fullDoc["_id"], $fullDoc);

                // Sending email address TODO: change this to actual url or whatever
                $email = "totallyrealemail@hotmail.com";
                // Recipient email address (the key in that Couch view)
                $to = $fullDoc["email"];
                $subject = "PHPCYOA - Reset password";

                $message = <<<ENDOFHEREDOCTEXT
We have received a request to reset your password. If this was really you, please enter the following code into the
page that asks for it.

{$password_reset_code}

If you did not reset your password, you can just ignore this.
ENDOFHEREDOCTEXT;

                // Create email headers
                $headers = "From: " . $email . "\r\n" .
                "Reply-To: ". $email . "\r\n" .
                "X-Mailer: PHP/" . phpversion();

                // Sending email and redirecting to account activation page
                if (mail($to, $subject, $message, $headers)) {
                    header("location: forgot_password_2.php");
                    exit();
                } else {
                    array_push($input_error_array, "Unable to send mail. Please try again later.");
                }
            }
        } catch (Exception $e) {
            array_push($input_error_array, "Error connecting to database. Please try again later.");
        }
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

    <p>Enter your email address below and we'll send a one-time use passcode to that email address that will allow you
    to create a new password.</p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method = "post">
        <label for="confirmcode">Email address: </label>
        <input class="form-textbox" type="text" id="email" name="email" maxlength="256"><br>
        <input class="custom-button" type="submit" class="js-submit" value="Submit">
    </form>

    <p><a href="login.php">Click here to go back</a>.</p>

</body>
</html>
