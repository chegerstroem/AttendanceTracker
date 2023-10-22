<?php
/* 
    Author     : Christian H - All code
*/
// Ensure time matches current timezone (as does the database)
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "db.php"; // include db connection php
require "session.php"; // verify session
$operation = $_POST['operation'];
switch($operation){ // parse operation and call function based on result
    case "courses":
        showCourses($auth);
        break;
    case "classes":
        break;
    case "instructors":
        break;
    case "students":
        break;
    case "staff":
        break;
    case "manageUsers":
        break;
    case "attendance":
        break;
    default:
        echo "<p>No operation retrieved</p>";
        break;
    
}

function showCourses($auth){
    echo "<h2>Courses</h2>";
    switch($auth){
        case "1":
            echo "<p>Courses Here</p>";
            break;
        default:
            break;
    }
}