<?php
/* 
    Author     : Christian H - All code
*/
// Ensure time matches current timezone (as does the database)
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
    require_once "db.php"; // include db connection php (missing from code for security)
    if(!(isset($_COOKIE['sessionID']))) { // Check for session ID cookie, return to login if it's not present
        header("Location: ./login.php");
        exit();
    }
    // Get session id, query db for matching key
    $SessionID = $_COOKIE['sessionID'];
    $SessionQry = "SELECT * FROM $databaseName.stlcc.Sessions WHERE sessionKey = '$SessionID'";
    $SessionStmt = $conn->query($SessionQry);
    $SessionRecord = $SessionStmt->fetch(PDO::FETCH_ASSOC);
    if(!$SessionRecord){ // If no match was found, return to login page
        header("Location: ./login.php");
        exit();
    }
    $AuthQry = "SELECT [Role] FROM SprintAssign.stlcc.Users WHERE UserID in (SELECT UserID FROM SprintAssign.stlcc.Sessions WHERE sessionKey = '$SessionID')";
    $AuthStmt = $conn->query($AuthQry);
    $AuthRecord = $AuthStmt->fetch(PDO::FETCH_ASSOC);
    $auth = $AuthRecord['Role'];
    if($auth == "0"){ // If unauthorized in the database, disallow login with unique login status (to be implemented)
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
            // Display unique content depending on auth level as per the database
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

