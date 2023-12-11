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
    $SessionQry = "SELECT loginDateTime FROM $databaseName.stlcc.Sessions WHERE sessionKey = '$SessionID'";
    $SessionStmt = $conn->query($SessionQry);
    $SessionRecord = $SessionStmt->fetch(PDO::FETCH_NUM);
    if ($SessionRecord) {$sessionTime = strtotime($SessionRecord[0]);}
    $currTime = strtotime(date('Y-m-d H:i:s'));
    $currTimeSql = date('Y-m-d H:i:s');
    
    // Check if the session exists
    if (!($SessionRecord)) { // If no match was found , return to login page
        unset($_COOKIE["sessionID"]);
        header("Location: ./login.php");
        exit(0);
    }

    //if the session timestamp more than 30 minutes old redirect to login with status 3
    if ($sessionTime < strtotime("-30 minutes", $currTime)) {
        setcookie("loginStatus", "3", 0, "/");
        setcookie("redirect", "1", 0, "/");
        $conn->query("DELETE FROM stlcc.Sessions WHERE sessionKey = '$SessionID'");
        header("Location: ./login.php");
        exit(0);
    }

    // Get auth level and username from database
    $AuthQry = "SELECT [UserTypeID], [UserID], [Username], [AccountDisabled] FROM stlcc.Users WHERE UserID in (SELECT UserID FROM stlcc.Sessions WHERE sessionKey = '$SessionID')";
    $AuthStmt = $conn->query($AuthQry);
    $AuthRecord = $AuthStmt->fetch(PDO::FETCH_ASSOC);
    $auth = $AuthRecord['UserTypeID'];
    $userID = $AuthRecord['UserID'];
    $username = $AuthRecord['Username'];
    $disabled = $AuthRecord['AccountDisabled'];

    //Session record exists and timestamp is less than 30 minutes old, update the timestamp if timestamp is at least one minute old
    if ($sessionTime < strtotime("-1 minute", $currTime)) {
        $sessQry = "UPDATE stlcc.Sessions SET loginDateTime = '$currTimeSql' WHERE UserID = $userID";
        $sessStmt = $conn->query($sessQry);
    }

    // Disallow access if account disabled flag is set
    if ($disabled == "1") {
        setcookie("loginStatus", "4");
        header("Location: ./login.php");
        exit(0);
    }
}
catch (PDOException $e) { // Temporary debug messages
    echo "<p>PDO Exception: ".$e->getMessage()."</p>";
}
catch (Exception $e) {
    echo "<p>PHP Exception: ".$e->getMessage()."</p>";
}