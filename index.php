<?php
/* Author: Christian H */
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
    require_once "db.php";
    if(!(isset($_COOKIE['sessionID']))) {
        header("Location: ./login.php");
        exit();
    }
    $SessionID = $_COOKIE['sessionID'];
    $SessionQry = "SELECT * FROM $databaseName.stlcc.Sessions WHERE sessionKey = '$SessionID'";
    $SessionStmt = $conn->query($SessionQry);
    $SessionRecord = $SessionStmt->fetch(PDO::FETCH_ASSOC);
    if(!$SessionRecord){
        header("Location: ./login.php");
        exit();
    }
    if($SessionID !== $SessionRecord['sessionKey']){
        $auth = 0;
    }
    $AuthQry = "SELECT [Role] FROM SprintAssign.stlcc.Users WHERE UserID in (SELECT UserID FROM SprintAssign.stlcc.Sessions WHERE sessionKey = '$SessionID')";
    $AuthStmt = $conn->query($AuthQry);
    $AuthRecord = $AuthStmt->fetch(PDO::FETCH_ASSOC);
    $auth = $AuthRecord['Role'];
    if($auth == 0){
        setcookie("loginStatus", "2", "/", "localhost", 0, 0);
        header("Location: ./login.php");
        exit();
    }
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>STLCC Attendance Tracker</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php
            if($auth == "1") {
                echo "<p>User is AppAdmin</p>";
            } else if($auth == "2") {
                echo "<p>User is QualifiedStaff</p>";
            } else if($auth == "3") {
                echo "<p>User is Instructor</p>";
            } else if($auth == "4") {
                echo "<p>User is Student</p>";
            } else {
                echo "<p>No Auth Set</p>";
            }
        ?>
    </body>
</html>

