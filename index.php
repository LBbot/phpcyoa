<?php
session_start();
require "session_cookie_checker.php";


// Check if no session cookie or token cookie and if so: send user back to login
if (session_cookie_check() === false) {
    header("location: login.php");
} else {
    header("location: profile.php");
}
