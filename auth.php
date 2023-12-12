<?php
/* 
    Author     : Christian H - All code
*/

// Ensure time matches current timezone (as does the database)
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
    require 'db.php';

    // Get username and password from post
    $pw = filter_input(INPUT_POST,"pass",FILTER_SANITIZE_ADD_SLASHES);
    $user = filter_input(INPUT_POST,"user",FILTER_SANITIZE_ADD_SLASHES);
    // Create sql statement and run on database
    $tsql = "SELECT [UserID], [PasswordHash] FROM stlcc.Users WHERE Username = '$user'";
    $stmt = $conn->query($tsql);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    $userID = $record['UserID'];
    // Get time in SQL datetime format
    $time = date('Y-m-d H:i:s');
    // Check for user in database - return to login with error if non-existent
    if(!($record)){
        setcookie("loginStatus", "1");
        header("Location: ./login.php");
        exit();
    }
    // Verify password with hash stored in database - If successful, create/update session entry in database
    if(password_verify($pw, trim($record['PasswordHash']))){
        $sessionID = bin2hex(random_bytes(24));
        $sessQry = "IF EXISTS (SELECT * FROM stlcc.Sessions WHERE UserID = $userID) 
        BEGIN UPDATE stlcc.Sessions SET sessionKey = '$sessionID', loginDateTime = '$time' WHERE UserID = $userID END
        ELSE 
        BEGIN INSERT INTO stlcc.Sessions VALUES ('$sessionID', $userID, '$time') END";
        $conn->query($sessQry);
        setcookie("sessionID", "$sessionID");
        setcookie("loginStatus", "0");
        header( "Location: /");
        exit();
    } else { // If unsuccessful, return to login with error
        setcookie("loginStatus", "2");
        header("Location: ./login.php");
        exit();
    }