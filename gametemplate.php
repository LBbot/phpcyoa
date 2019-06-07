<?php
session_start();
require "couch_functions.php";
require "session_cookie_checker.php";

// Check if no session cookie or token cookie and if so: send user back to login
if (session_cookie_check() === false) {
    header("location: login.php");
    exit();
}

// If no ?query to _GET in URL, redirect to page 1
if (!isset(array_keys($_GET)[0])) {
    header("location: gametemplate.php?1");
    exit();
}
// Otherwise get number ID of section/chapter/choice/page number.
$current_ID = (array_keys($_GET)[0]);

// Get JSON file from File System and read it (automatically closes)
$json = file_get_contents('./choices.json');
// Decode JSON
$json_data = json_decode($json, true);

// If number in url ? query does not exist in JSON, redirect to page 1
if (!isset($json_data[$current_ID]["the_question"])) {
    header("location: gametemplate.php?1");
    exit();
}
// Otherewise set up variables for the question on load
$the_question = $json_data[$current_ID]["the_question"];

// If answer a exists, set up variables for both answers and IDs
if (array_key_exists("answer_a", $json_data[$current_ID])) {
    $answer_a = $json_data[$current_ID]["answer_a"];
    $a_id = $json_data[$current_ID]["a_id"];
    $answer_b = $json_data[$current_ID]["answer_b"];
    $b_id = $json_data[$current_ID]["b_id"];
}
if (array_key_exists("answer_c", $json_data[$current_ID])) {
    $answer_c = $json_data[$current_ID]["answer_c"];
    $c_id = $json_data[$current_ID]["c_id"];
}

// If user saves, they POST their current chapter to the DB (but we still do all the aforementioned get stuff)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If $_post includes "save progress" via pressing the save button
    if (in_array("Save progress", $_POST)) {
        // 404 or database error catching with try/catch
        try {
            //Get doc by user email, replace any plus signs with unicode so query does not break
            $email = urlencode($_SESSION["email"]);
            // Get doc ID from view query result
            $couchViewKey = "phpusers/_design/views/_view/emails-and-passwords?key=\"{$email}\"&include_docs=true";
            $couch_rows_arr = couch_get_decode_json($couchViewKey);

            $couch_rows_arr[0]["doc"]["saved_game_number"] = $current_ID;

            // PUT saved game page/chapter in doc and send back to Couch
            couch_put_or_post("PUT", $couch_rows_arr[0]["id"], $couch_rows_arr[0]["doc"]);

            $save_message = "Saved game (Warning: this will be overwritten next time you save)";

        } catch (Exception $e) {
            $save_message = "Error connecting to database. WARNING: GAME HAS NOT BEEN SAVED";
        }
    }
}

// don't include the basic template header for the actual game because the <h1> just gets in the way,
// so we have the custom <head> below.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>PHP CYOA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="public/styles.css" />
</head>
<body>

<div class = "game-container">

<?php
if (isset($save_message) && !empty($save_message)) {
    echo "<p class=\"save-message\">" . $save_message . "</p>";
}
?>

    <p class="prose"><?php
        echo $the_question;
    ?></p>

    <?php if (isset($a_id) && isset($b_id)) : ?>
        <form method = "get">
            <input
                type="submit"
                class = "custom-button"
                name="<?php echo $a_id; ?>"
                value="<?php echo $answer_a; ?>"
            >
        </form>
        <form method = "get">
        <input
                type="submit"
                class = "custom-button"
                name="<?php echo $b_id; ?>"
                value="<?php echo $answer_b; ?>"
            >
        </form>

        <form method = "post">
            <input
                class="unimportant-button"
                type="submit"
                name="<?php echo $current_ID; ?>"
                value="Save progress"
            >
        </form>
    <?php endif; ?>

    <!-- in case of single answer, we use c, no save button -->
    <?php if (isset($c_id)) : ?>
        <form method = "get">
            <input
                type="submit"
                class = "custom-button"
                name="<?php echo $c_id; ?>"
                value="<?php echo $answer_c; ?>"
            >
        </form>
    <?php endif; ?>

    <a href = "profile.php"><div class = "unimportant-button">Return to main menu</div></a>



</div>

</body>
</html>