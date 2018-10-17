<?php
session_start();
require "session_cookie_checker.php";

// Set up page title and <head>/header
$page_title = "PHP choose-your-own-adventure";
include_once "head.php";
?>

    <p class="prose">A short, simple game written in PHP in the vein of a
    <a href="https://en.wikipedia.org/wiki/Gamebook">choose-your-own-adventure</a>, where you are presented with a
    generic fantasy scenario and make choices that can decide whether you and those around you live or die*.
    <br /><br />
    <sup>*"You" being the character in the game - the game is not designed to harm you or those you around you in real
    life.<sup></p>

    <!-- TODO: include a screenshot here -->

    <p class="prose">Replay with the savegame feature to see the other outcomes and possiblities!</p>



    <p>To play, just: </p>

    <?php
    // Determine user account status to display correct options available.
    if (session_cookie_check() === false) {
        if (isset($_SESSION["unconfirmed_email"]) && !empty($_SESSION["unconfirmed_email"])) {
            echo "<p>Confirm your email address to <a href=\"account_activation.php\">activate your account!</a></p>";
        } else {
            echo "<p><a href=\"register.php\">Register an account</a> with your email address!</p>";
            echo "<p><a href=\"login.php\">Or log in</a> if you already have an account!</p>";
        }
    } else {
        echo "<p>Go to <a href=\"profile.php\">your profile page</a>!</p>";
    }
    ?>

    <!-- TODO: Put an actual email here -->
    <p>Any issues? <a href="">Send an email</a>.</p>

    </div>

</body>
</html>