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
                                <button id='classes' onclick='operation(this.id)'>Classes</button>
                                <button id='attendance' onclick='operation(this.id)'>Attendance</button>
                                <button id='instructors' onclick='operation(this.id)'>Instructors</button>
                                <button id='students' onclick='operation(this.id)'>Students</button>
                                <button id='staff' onclick='operation(this.id)'>Staff</button>
                                <button id='manageUsers' onclick='operation(this.id)'>Manage Users</button>";
                                break;
                            case "2":
                                echo "<button id='Students' onclick='operation(this.id)'>Students</button>
                                <button id='courses' onclick='operation(this.id)'>Courses</button>
                                <button id='classes' onclick='operation(this.id)'>Classes</button>
                                <button id='attendance' onclick='operation(this.id)'>Attendance</button>";
                                break;
                            case "3":
                                echo "<button id='Students' onclick='operation(this.id)'>Students</button>
                                <button id='courses' onclick='operation(this.id)'>Courses</button>
                                <button id='classes' onclick='operation(this.id)'>Classes</button>
                                <button id='attendance' onclick='operation(this.id)'>Attendance</button>";
                                break;
                            case "4":
                                echo "<button id='courses' onclick='operation(this.id)'>Courses</button>
                                <button id='classes' onclick='operation(this.id)'>Classes</button>
                                <button id='attendance' onclick='operation(this.id)'>Attendance</button>";
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

