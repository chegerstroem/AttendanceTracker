<?php
/* 
    Author     : Christian H - All code
*/
try {
    if(!(isset($_COOKIE['sessionID']))) { // Check for session ID cookie, return to login if it's not present
        header("Location: ./login.php");
        exit();
    }

    // Get session id, query db for matching key
    // FIXME: handle checking expiration dates and clearing session records here
    $SessionID = $_COOKIE['sessionID'];
    $SessionQry = "SELECT * FROM $databaseName.stlcc.Sessions WHERE sessionKey = '$SessionID'";
    $SessionStmt = $conn->query($SessionQry);
    $SessionRecord = $SessionStmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$SessionRecord){ // If no match was found, return to login page
        header("Location: ./login.php");
        exit();
    }
    // Get auth level from database
    $AuthQry = "SELECT [Role] FROM SprintAssign.stlcc.Users WHERE Username in (SELECT Username FROM SprintAssign.stlcc.Sessions WHERE sessionKey = '$SessionID')";
    $AuthStmt = $conn->query($AuthQry);
    $AuthRecord = $AuthStmt->fetch(PDO::FETCH_ASSOC);
    $auth = $AuthRecord['Role'];

    if(!(in_array($auth,["1", "2", "3", "4"], true))){ // If unauthorized in the database, disallow login with unique login status (to be implemented)
        setcookie("loginStatus", "2");
        header("Location: ./login.php");
        exit();
    }
    // Get username from database
    $UserQry = "SELECT Username FROM SprintAssign.stlcc.Sessions WHERE sessionKey = '$SessionID'";
    $UserStmt = $conn->query($UserQry);
    $UserRecord = $UserStmt->fetch(PDO::FETCH_ASSOC);
    $username = $UserRecord['Username'];
}
catch (PDOException $e) { // Temporary debug messages
    echo "<p>PDO Exception: ".$e->getMessage()."</p>";
}
catch (Exception $e) {
    echo "<p>PHP Exception: ".$e->getMessage()."</p>";
}