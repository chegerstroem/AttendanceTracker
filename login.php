<?php
/* Author: Christian H */
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
if(isset($_COOKIE['loginStatus'])){
    $status = $_COOKIE['loginStatus'];
}
?>
<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>STLCC Attendance Tracker Login</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <form action="auth.php" method="post">
            <div id="loginBox">
                <p>Please enter your username and password.</p>
                <?php
                    if((isset($status) && $status !== "0")){
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
