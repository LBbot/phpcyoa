<?php
require "couch_functions.php";
require "session_cookie_checker.php";
session_start();


// somehow send them back to  account activation?
// new email entry page



// Set up page title and <head>/header
$page_title = "Update your email address - PHP CYOA";
include_once "head.php";
?>

    <p>Update your email address</p>

    <form method = "post">
        <label for="email">New email address: </label>
        <input class="form-textbox" type="text" id="email" name="email" maxlength="256"><br>

        <label for="password">Please confirm your current password: </label>
        <input class="form-textbox" type="password" id="password" name="password" maxlength="256"><br>

        <input class="custom-button" type="submit" class="js-submit" value="Confirm">
    </form>

    <p>Changed your mind? <a href = "profile.php">Return to profile</a>.</p>

</body>
</html>