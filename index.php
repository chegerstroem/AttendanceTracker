<?php
/* 
    Author     : Christian H - All code
*/
    // Ensure time matches current timezone (as does the database)
    date_default_timezone_set('America/Chicago');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require "db.php"; // include db connection php
    require "session.php"; // validate session - return to login on failure
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>STLCC Attendance Tracker</title>
        <link rel="stylesheet" href="style.css">
        <script src="jquery-3.7.1.min.js"></script>
        <script src="action.js"></script>
    </head>
    <body>
        <!--Document header shows select buttons based on auth level -->
        <header role="banner" id="header">
            <div class="headerContent">
                <div class="headerLogo">
                    <img src="img/logo.svg" alt="Saint Louis Community College Logo" href="index.php"/>
                </div>
                <div class="headerHeading">
                    <p>Student Attendance Tracker</p>
                </div>
                <div class="headerNav">
                    <?php
                        switch($auth) {
                            case "1":
                                echo "<button id='courses' onclick='operation(this.id)'>Courses</button>
                                <button id='classes>Classes</button>
                                <button id='attendance'>Attendance</button>
                                <button id='instructors'>Instructors</button>
                                <button id='students'>Students</button>
                                <button id='staff'>Staff</button>
                                <button id='manageUsers'>Manage Users</button>";
                                break;
                            case "2":
                                echo "<button id='Students'>Students</button>
                                <button id='courses'>Courses</button>
                                <button id='classes'>Classes</button>
                                <button id='attendance'>Attendance</button>";
                                break;
                            case "3":
                                echo "<button id='Students'>Students</button>
                                <button id='courses'>Courses</button>
                                <button id='classes'>Classes</button>
                                <button id='attendance'>Attendance</button>";
                                break;
                            case "4":
                                echo "<button id='courses'>Courses</button>
                                <button id='classes'>Classes</button>
                                <button id='attendance'>Attendance</button>";
                                break;
                        }
                    ?>
                </div>
            </div>
        </header>
        <div id="contentBox">
        </div>
    </body>
</html>

