<?php
session_start();

// Make session an empty array for extra security before we destroy it
$_SESSION = array();

session_destroy();

header("location: login.php");

?>
