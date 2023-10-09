<?php
/* Author: Christian H */
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
    require 'db.php';

    $pw = filter_input(INPUT_POST,"pass",FILTER_SANITIZE_ADD_SLASHES);
    $user = filter_input(INPUT_POST,"user",FILTER_SANITIZE_ADD_SLASHES);
    $tsql = "SELECT * FROM $databaseName.stlcc.Users WHERE Username = '$user'";
    $stmt = $conn->query($tsql);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    $time = date('Y-m-d H:i:s');
    if($stmt->rowCount() == 0){
        setcookie("loginStatus", "1", time()+3600, "/", "localhost", 0, 0);
        header("Location: ./login.php");
        exit();
    }
    echo "<p>Row count not zero</p>";
    echo "<p>$pw</p>";
    echo "<p>", trim($record['Password']), "</p>";
    if(password_verify($pw, trim($record['Password']))){
        $sessionID = bin2hex(random_bytes(32));
        $sessQry = "IF EXISTS (SELECT 1 FROM SprintAssign.stlcc.Sessions WHERE Username = '$user') BEGIN UPDATE SprintAssign.stlcc.Sessions SET sessionKey = '$sessionID' END ELSE BEGIN INSERT INTO SprintAssign.stlcc.Sessions VALUES ('$sessionID', '$user', '$time') END";
        $sessStmt = $conn->query($sessQry);
        setcookie("sessionID", "$sessionID", time()+3600, "/", "localhost", 0, 0);
        setcookie("loginStatus", "0", time()+3600, "/", "localhost", 0, 0);
        header( "Location: ./index.php");
        exit();
    } else {
        setcookie("loginStatus", "1", time()+3600, "/", "localhost", 0, 0);
        header("Location: ./login.php");
        exit();
    }