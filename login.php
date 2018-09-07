<?php
require "db.php";
session_start();

if (isset($_SESSION["email"]) && !empty($_SESSION["email"])) {
    header("location: profile.php");
}

// if form is posted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    print_r($_POST);

    // 404 or database error catching with try/catch
    try {
        // Check for duplicate username, by getting Couch view of emails with key of the email posted
        $response = $client->request("GET", "phpusers/_design/views/_view/emails-and-passwords?key=\"{$_POST["email"]}\"");
        $json = $response->getBody()->getContents();
        $decoded_json = json_decode($json, true);
        $couch_rows_arr = $decoded_json["rows"];

        // FOR DEBUGGING
        print_r($couch_rows_arr);

        // If it returns an empty array, the username is in use
        if (empty($couch_rows_arr)) {
            $custom_error = "Email address does not exist.";
            exit(include_once "error.php");
        }

        // Password is in the value of the doc
        $hashed_password = $couch_rows_arr[0]["value"];
        // PASSWORD VERIFCATION
        if (!password_verify($_POST["password"], $hashed_password)) {
            $custom_error = "Password is incorrect.";
            exit(include_once "error.php");
        }

        echo "CORRECT PASSWORD!!!!!";

        $_SESSION["email"] = $couch_rows_arr[0]["key"];
        // var_dump($_SESSION);
        header("location: profile.php");

    } catch (Exception $e) {
        $custom_error = "Error connecting to database. Please try again later.";
        exit(include_once "error.php"); // Will check for the above variable and display it
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

    </div>

</body>
</html>