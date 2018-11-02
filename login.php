<?php
require "session_cookie_checker.php";
require "couch_functions.php";
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

// if form is posted
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
            } else { // otherwise let's compare passwords
                // Password is in the value of the doc
                $hashed_password = $couch_output_arr[0]["value"];
                // PASSWORD VERIFCATION
                if (!password_verify($_POST["password"], $hashed_password)) {
                    array_push($input_error_array, "Password is incorrect.");
                }
            }

            if (empty($input_error_array)) {
                // check if account is not activated (confirmed email), if so, redirect to activation page.

                if ($couch_output_arr[0]["doc"]["activation_code"] !== "activated") {
                    // We also need to give them a session.
                    $_SESSION["unconfirmed_email"] = $couch_output_arr[0]["key"];
                    header("location: account_activation.php");
                    exit();
                }

                // Create a random token for cookie
                $token = bin2hex(random_bytes(12));

                // Set bcrypt rounds, then hash token with them.
                $options = ["cost" => 12];
                $hashedToken = password_hash($token, PASSWORD_BCRYPT, $options);

                // Get the full doc
                $fullDoc = $couch_output_arr[0]["doc"];

                // To stop CouchDB filling up with old expired token hashes, when we reach a certain number - we delete
                // the first one in the array before we add a new one to the end
                if (count($fullDoc["cookie_tokens"]) > 2) {
                    array_shift($fullDoc["cookie_tokens"]);
                }

                // Push the token to the cookie_tokens array
                array_push($fullDoc["cookie_tokens"], $hashedToken);

                // Actually re-encode and send the json doc back with a PUT to the ID
                couch_put_or_post("PUT", $couch_output_arr[0]["id"], $fullDoc);

                // If that's successful let's actually log the user in! Start with creating a session with user's email
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
            }
        } catch (Exception $e) {
            array_push($input_error_array, "Error connecting to database. Please try again later.");
        }
    }
}

// Set up page title and <head>/header
$page_title = "Login - PHP CYOA";
include_once "head.php";
?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method = "post">
        <label for="email">Email address: </label>
        <input class="form-textbox" type="text" id="email" name="email" maxlength="256"><br>

        <label for="password">Password: </label>
        <input class="form-textbox" type="password" id="password" name="password" maxlength="256"><br>

        <input class="custom-button" type="submit" class="js-submit" value="Log in">
    </form>


    <p>Don't have an account? <a href = "register.php">Register here</a>.</p>

    <p><a href = "forgot_password_1.php">Forgotten your password?</a></p>
    </div>

</body>
</html>