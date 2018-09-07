<?php
require "db.php";
session_start();

if (!isset($_SESSION["email"]) || empty($_SESSION["email"])) {
    header("location: login.php");
}

    // Set up page title and <head>/header, and container
    $page_title = "Your account - PHP CYOA";
    include_once "head.php";
?>


    <!-- escape any of the users characters we're displaying back to them -->
    <h2><?php echo htmlspecialchars($_SESSION["email"]); ?></h2>

    <form method="get" action="gametemplate.php">
        <input type="submit" class="custom-button" name="1" value="Start new game">
    </form>

    <?php
        // 404 or database error catching with try/catch
        try {
            $email = $_SESSION["email"];
            $response = $client->request("GET", "phpusers/_design/views/_view/emails-and-passwords?key=\"{$email}\"");
            $json = $response->getBody()->getContents();
            $decoded_json = json_decode($json, true);
            $couch_rows_arr = $decoded_json["rows"];
            $id_to_update = $couch_rows_arr[0]["id"];

            $response = $client->request("GET", "/phpusers/$id_to_update");
            $json = $response->getBody()->getContents();
            $decoded_json = json_decode($json, true);

        } catch (Exception $e) {
            $custom_error = "Error connecting to database. Please try again later.";
            echo $custom_error;
        }
    ?>



    <?php if (isset($decoded_json["saved_game_number"]) && !empty($decoded_json["saved_game_number"])) : ?>
        <form method="get" action="gametemplate.php">
            <input
                type="submit"
                class="custom-button"
                name=" <?php echo $decoded_json["saved_game_number"]; ?>"
                value="Resume progress"
            >
        </form>
    <?php endif; ?>



    <a href = "logout.php"><div class = "unimportant-button">Logout</div></a>

</div>
</body>
</html>
