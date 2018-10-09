<?php
require "db.php";
session_start();

if (isset($_SESSION["email"]) && !empty($_SESSION["email"])) {
    header("location: profile.php");
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
            $couchViewAndKey = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$_POST["email"]}\"";
            $response = $client->request("GET", $couchViewAndKey);
            $json = $response->getBody()->getContents();
            $decoded_json = json_decode($json, true);
            $couch_rows_arr = $decoded_json["rows"];

            // If it doesn't return an empty array, the email is in use
            if (empty($couch_rows_arr) === false) {
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
        // ACTUALLY POST TO COUCH
        $response = $client->request("POST", "phpusers", [
            "json" => $_POST
        ]);

        // Checks for confirmation
        if ($response->getBody()) {
            $_SESSION["email"] = $_POST["email"];
            header("location: profile.php");
        } else {
            array_push($input_error_array, "There was a problem posting to database.");
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