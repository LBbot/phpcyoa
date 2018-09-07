<?php
require "db.php";
session_start();

if (isset($_SESSION["email"]) && !empty($_SESSION["email"])) {
    header("location: profile.php");
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // $_POST contains the form data as associative array
    // strip any whitespace
    $_POST["email"] = trim($_POST["email"]);
    // Check for blank data
    foreach ($_POST as $form_entry) {
        if ($form_entry == "") {
            $custom_error = "All fields are required and cannot be left blank.";
            exit(include_once "error.php"); // Will check for the above variable and display it
        }
    }

    // 404 or database error catching with try/catch
    try {
        // Check for duplicate email, by getting Couch view of emails with key of the email posted
        $response = $client->request("GET", "phpusers/_design/views/_view/emails-and-passwords?key=\"{$_POST["email"]}\"");
        $json = $response->getBody()->getContents();
        $decoded_json = json_decode($json, true);
        $couch_rows_arr = $decoded_json["rows"];

        // FOR DEBUGGING
        print_r($couch_rows_arr);

        // If it doesn't return an empty array, the email is in use
        if (empty($couch_rows_arr) === false) {
            $custom_error = "Email address already in use. Please log in or try another.";
            exit(include_once "error.php");
        }
    } catch (Exception $e) {
        $custom_error = "Error connecting to database. Please try again later.";
        exit(include_once "error.php"); // Will check for the above variable and display it
    }

    // Compare password with confirmation
    if ($_POST["password"] !== $_POST["passwordconfirm"]) {
        $custom_error = "Passwords must match";
        exit(include_once "error.php"); // Will check for the above variable and display it
    }

    // Let's remove the confirm once tested, we don't need that in the DB
    unset($_POST["passwordconfirm"]);

    // Set the rounds on bcrypt for password hashing
    $options = ["cost" => 12]; // TODO: try removing this comma
    // replace password with hashed one
    $_POST["password"] = password_hash($_POST["password"], PASSWORD_BCRYPT, $options);

    // add date/time in machine readable and human readable form because hey, PHP can do that nicely
    // We push key/value pairs to associative array by defining it kinda like a property
    $_POST["date"] = date(DATE_ATOM);
    $_POST["readable_date"] = date("H:i:sA D d/m/Y");

    // ACTUALLY POST TO COUCH
    $response = $client->request("POST", "phpusers", [
        "json" => $_POST
    ]);

    // Checks for confirmation
    if ($response->getBody()){
        echo "Success!";
        header("location: login.php");
    } else {
        echo "There was a problem posting to database.";
    }
}

    // Set up page title and <head>/header
    $page_title = "Register an account - PHP CYOA";
    include_once "head.php";
?>

    <form method = "post">
        <!-- <label for="username">Username: </label>
        <input type="text" id="username" name="username" maxlength="25"><br> -->

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