<?php
/* 
    Author     : Christian H - All code
*/
// Ensure time matches current timezone (as does the database)
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Get login status cookie value
if(isset($_COOKIE['loginStatus'])){
    $status = $_COOKIE['loginStatus'];
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>STLCC Attendance Tracker Login</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <form action="auth.php" method="post"> <!-- Login form - posts to auth.php -->
            <div id="loginBox">
                <p>Please enter your username and password.</p>
                <?php
                    if((isset($status) && $status !== "0")){ // Display error depending on login status (partially implemented)
                       echo "<p style='color:red'>Login Error</p>";
                    }
                    setcookie("loginStatus", "0", time()+3600, "/", "localhost", 0, 0);
                ?>
                <div id="loginControls">
                    <input name="user" type="text" placeholder="Username" id="user" class="loginControl" required/><br>
                    <input name="pass" type="password" placeholder="Password" id="pass" class="loginControl" required/><br>
                    <button type="submit" id="loginButton" class="loginControl button">Login</button>
                </div>
            </div>
        </form>
    </body>
</html>
