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
if(isset($_COOKIE['redirect'])){
    $redirect = $_COOKIE['redirect'];
}

$errorMessages = [
    "1" => "Username does not exist.",
    "2" => "Password is incorrect.",
    "3" => "Your session has expired due to inactivity. Please re-enter your account details.",
    "4" => "Your account is disabled. Please contact the system administrator for further information.",
];
?>
<!--xKy89Rt-->
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>STLCC Attendance Tracker Login</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <header role="banner" id="header">
            <div class="headerContent">
                <div class="headerLogo">
                    <img src="img/logo.svg" alt="Saint Louis Community College Logo" href="index.php"/>
                </div>
                <div class="headerHeading">
                    <p>Student Attendance Tracker</p>
                </div>
            </div>
        </header>
        <form action="auth.php" method="post"> <!-- Login form - posts to auth.php -->
            <div id="loginBox">
                <p>Please enter your STLCC user account details below to log in.</p>
                <?php
                    if((isset($status) && $status !== "0")){ // Display error depending on login status (partially implemented)
                       echo "<p class='loginErrorMessage'>$errorMessages[$status]</p>";
                    }
                    if (!(isset($redirect))) {
                        setcookie("loginStatus", "0");
                    }
                    setcookie("redirect", "", time() - 3600);
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
