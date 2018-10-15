<?php
require "couch_functions.php";
session_start();

// The user has successfully registered an account, now they need to verify their email with a 4 digit passcode or link.
// set session email and status in register.php when you redirect here

// Make sure they have a session for this specifically, else send them back to login
if (!isset($_SESSION["unconfirmed_email"]) || empty($_SESSION["unconfirmed_email"])) {
    header("location: login.php");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // If any plus signs in email address, replace them with unicode so it doesn't break the Couch query.
        $urlencoded_email = urlencode($_SESSION["unconfirmed_email"]);

        // Get doc from Couch
        $couchViewKey = "phpusers/_design/views/_view/emails-and-codes?key=\"{$urlencoded_email}\"&include_docs=true";

        $couch_rows_arr = couch_get_decode_json($couchViewKey);

        // CHECK IF CONFIRMATION CODE IS ACTUALLY CORRECT
        if ($couch_rows_arr[0]["value"] !== $_POST["confirmcode"]) {
            echo "Confirmation code is incorrect";
            exit();
        }

        // If confirmation is confirmed then we basically go through the login process.
        // Create a random token
        $token = bin2hex(random_bytes(12));

        // Set bcrypt rounds, then hash token with them.
        $options = ["cost" => 12];
        $hashedToken = password_hash($token, PASSWORD_BCRYPT, $options);

        // To stop CouchDB filling up with old expired token hashes, when we reach a certain number - we delete
        // the first one in the array before we add a new one to the end
        if (count($couch_rows_arr[0]["doc"]["cookie_tokens"]) > 2) {
            array_shift($couch_rows_arr[0]["doc"]["cookie_tokens"]);
        }

        $couch_rows_arr[0]["doc"]["activation_code"] = "activated";

        // Push the token to the cookie_tokens array
        array_push($couch_rows_arr[0]["doc"]["cookie_tokens"], $hashedToken);

        // Actually re-encode and send the json doc back with a PUT to the ID
        couch_put_or_post("PUT", $couch_rows_arr[0]["id"], $couch_rows_arr[0]["doc"]);

        // Let's actually log the user in! Start with creating a session with user's email
        // We rewrite and destroy the session first to get rid of ["unconfirmed_email"]
        $_SESSION = array();
        session_destroy();
        $_SESSION["email"] = $couch_rows_arr[0]["key"];
        // Set expiry for cookie
        $thirtyDaysExpiry = time() + 86400 * 30;
        // Put users email (e) and raw token (t) in an ASSOCIATIVE ARRAY flattened to an encoded json STRING
        // so we can put both into the value of the cookie and decode when we need it
        $email_and_token = (json_encode(array("e" => $couch_rows_arr[0]["key"], "t" => $token)));
        // HTTPS (boolean 1 of 2) set to false because localhost. SET IT TO TRUE ON A SERVER WITH SSL
        setcookie("et_cookie", $email_and_token, $thirtyDaysExpiry, "/", "", false, false);
        // And FINALLY redirect user to profile
        header("location: profile.php");

    } catch (Exception $e) {
        $custom_error = "Error connecting to database. Please try again later.";
        echo $custom_error;
    }
}

// Set up page title and <head>/header
$page_title = "Register an account - PHP CYOA";
include_once "head.php";
?>

<h2><?php echo htmlspecialchars($_SESSION["unconfirmed_email"]); ?></h2>

<p>A code has been sent to your email address. Click the link in the email OR enter the code the box below to activate
your account. </p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method = "post">
        <label for="confirmcode">Confirmation code: </label>
        <input class="form-textbox" type="text" id="confirmcode" name="confirmcode" maxlength="256"><br>
        <input class="custom-button" type="submit" class="js-submit" value="Submit">
    </form>

    <p>Deleted or lost your activation message? <a href = "resend.php">Click here to send another</a>.</p>

    <p>Something gone wrong? You can <a href = "logout.php">log out here</a>.</p>

    </div>

</body>
</html>

