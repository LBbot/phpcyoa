<?php
require "couch_functions.php";
require "session_cookie_checker.php";
session_start();

// Check if no session cookie or token cookie and if so: send user back to login
if (session_cookie_check() === false) {
    header("location: login.php");
}

// 404 or database error catching with try/catch
try {
    // URLencode so it will replace any pluses (for example) and avoid breaking the Couch query
    $email = urlencode($_SESSION["email"]);
    $couchViewKey = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$email}\"&include_docs=true";
    // Get the doc
    $couch_output_arr = couch_get_decode_json($couchViewKey);
    $user_doc = $couch_output_arr[0]["doc"];
} catch (Exception $e) {
    $custom_error = "Error connecting to database. Please try again later.";
    echo $custom_error;
}

// Set up page title and <head>/header, and container
$page_title = "Your account - PHP CYOA";
include_once "head.php";
?>

    <!-- escape any of the users characters we're displaying back to them -->
    <p><?php echo htmlspecialchars($user_doc["email"]); ?></p>

    <form method="get" action="gametemplate.php">
        <input type="submit" class="custom-button" name="1" value="Start new game">
    </form>

    <?php if (isset($user_doc["saved_game_number"]) && !empty($user_doc["saved_game_number"])) : ?>
        <form method="get" action="gametemplate.php">
            <input
                type="submit"
                class="custom-button"
                name=" <?php echo $user_doc["saved_game_number"]; ?>"
                value="Resume progress"
            >
        </form>
    <?php endif; ?>



    <a href = "logout.php"><div class = "unimportant-button">Logout</div></a>

</div>
</body>
</html>
