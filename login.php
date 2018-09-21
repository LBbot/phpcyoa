<?php
require "db.php";
session_start();

if (isset($_SESSION["email"]) && !empty($_SESSION["email"])) {
    header("location: profile.php");
}

// if form is posted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Empty array for errors to start
    $input_error_array = array();

    // trim email, sanitize filter, then validation filter. If false, don't get db or do anything else
    if (filter_var(filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL) === false) {
        array_push($input_error_array, "Invalid email");
    } else {
        // 404 or database error catching with try/catch
        try {
            // Check for duplicate username, by getting Couch view of emails with key of the email posted

            // If any + signs in email address, replace them with unicode so it doesn't break the Couch query.
            $plus_sign_email_converter = str_replace("+", "%2B", $_POST["email"]);

            $couchViewAndKey = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$plus_sign_email_converter}\"";
            $response = $client->request("GET", $couchViewAndKey);
            $json = $response->getBody()->getContents();
            $decoded_json = json_decode($json, true);
            $couch_rows_arr = $decoded_json["rows"];

            // If it returns an empty array, the username/email is not in use, so can't check password
            if (empty($couch_rows_arr)) {
                array_push($input_error_array, "Email address does not exist.");
            } else { // otherwise let's compare passwords
                // Password is in the value of the doc
                $hashed_password = $couch_rows_arr[0]["value"];
                // PASSWORD VERIFCATION
                if (!password_verify($_POST["password"], $hashed_password)) {
                    array_push($input_error_array, "Password is incorrect.");
                }
            }

            if (empty($input_error_array)) {
                $_SESSION["email"] = $couch_rows_arr[0]["key"];
                header("location: profile.php");
            }
        } catch (Exception $e) {
            array_push($input_error_array, "Error connecting to database. Please try again later.");
        }
    }
}

// Set up page title and <head>/header
$page_title = "Login - PHP CYOA";
include_once "head.php";

// If any errors: show them
if (!empty($input_error_array)) {
    foreach ($input_error_array as $single_error) {
        echo "<h3>$single_error</h3>";
    }
}

?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method = "post">
        <label for="email">Email address: </label>
        <input class="form-textbox" type="text" id="email" name="email" maxlength="256"><br>

        <label for="password">Password: </label>
        <input class="form-textbox" type="password" id="password" name="password" maxlength="256"><br>

        <input class="custom-button" type="submit" class="js-submit" value="Log in">
    </form>

    <p>Don't have an account? <a href = "register.php">Register here</a>.</p>

    </div>

</body>
</html>