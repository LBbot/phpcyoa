<?php

// enter new password

// check that they have some sort of special forgotten password session, else redirect,
// only reason they should be on this page, and it shouldn't affect anything else
if (!isset($_SESSION["password_reset_email"]) || !empty($_SESSION["password_reset_email"])) {
    header("location: login.php");
    exit();
}



// HANDLE POST HERE





// Set up page title and <head>/header
$page_title = "Register an account - PHP CYOA";
include_once "head.php";
?>

    <form method = "post">
        <label for="password">Password: </label>
        <input class="form-textbox" type="password" id="password" name="password" maxlength="256"><br>

        <label for="passwordconfirm">Confirm password: </label>
        <input class="form-textbox" type="password" id="passwordconfirm" name="passwordconfirm" maxlength="256"><br>

        <input class="custom-button" type="submit" class="js-submit" value="Confirm">
    </form>

</body>
</html>
