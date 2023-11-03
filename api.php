<?php
/* 
    Author     : Christian H - All code
*/
// Ensure time matches current timezone (as does the database)
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require "db.php"; // include db connection php
require "session.php"; // verify session
if (!(isset($_POST['search']))) {
    //if (isset($_POST['operation'])){$operation = $_POST['operation'];} else {$operation = "";}
    $operation = $_POST['operation'];
    switch($operation){ // parse operation and call function based on result
        case "courses":
            showCourses();
            break;
        case "classes":
            showClasses();
            break;
        case "instructors":
            showInstructors();
            break;
        case "students":
            showStudents();
            break;
        case "staff":
            showStaff();
            break;
        case "manageUsers":
            manageUsers();
            break;
        case "attendance":
            showAttendance();
            break;
        case "dashboard":
            showDashboard();
            break;
        case "logout":
            logout();
        case "user":
            showUserPage();
            break;
        default:
            echo "<p>".(isset($operation) ? "Unknown operation: '$operation'" : "No operation provided." )."</p>";
            break;
    }
} else {
    $searchQuery = $_POST['search'];
    if (isset($_POST['role'])){$role = $_POST['role'];} else {$role = null;}
    userSearch($searchQuery, $role);
}
// executeQuery(string sql)
// This function takes a SQL string and executes it using PDO
function executeQuery($sql) {
    global $conn;
    return $conn->query($sql);
    
}

// incrementQuery(PDO::query() $statement, bool $numIndex)
// This function increments the PDO statement and returns an
// array with either number or column name indexes
function incrementQuery($statement, $numIndex = null){
    global $conn;
    if (!(isset($numIndex))){$numIndex = 0;}

    $record = $statement->fetch($numIndex ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
    return $record;
}

function sortUserArray($userArray) {
    usort($userArray, function($a,$b){
        if ($a[3] == $b[3]) {
            return $a[2] > $b[2] ? 1 : -1;
        }
        return $a[3] > $b[3] ? 1 : -1;
    });

    return $userArray;
}

// getAllUsers()
// Returns: An array containing all user information from all user tables,
// including: UserID, Username, First Name, Last Name, Email, User Type
function getAllUsers(){
    $userArray = array();

    // execute each getter function for the user types
    $userArray = getAllStudents($userArray);
    $userArray = getAllStaff($userArray);
    $userArray = getAllInstructors($userArray);

    return $userArray;
}

function getAllStudents($userArray = null){
    global $auth;
    global $username;

    if (!(isset($userArray))){$userArray = array();}

    if ($auth == "2"){
        $id = getUsertypeID($username);
        $userStudentStmt = executeQuery("SELECT Students.StudentID, Users.Username, Students.StudentFirstName, Students.StudentLastName, Students.StudentEmail FROM stlcc.Students 
        JOIN stlcc.Users ON Users.UserID = Students.UserID
        WHERE StudentID IN (SELECT StudentID FROM stlcc.Enrollment e
             JOIN stlcc.Classes c ON c.ClassID = e.ClassID
                WHERE c.InstructorID = '5')");
    } else {
        $userStudentStmt = executeQuery("SELECT Users.UserID, Users.Username, Students.StudentFirstName, Students.StudentLastName, Students.StudentEmail 
        FROM stlcc.Users
        INNER JOIN stlcc.Students ON Students.UserID = Users.UserID;");  
    }

    while($userStudentRecord = incrementQuery($userStudentStmt, 1)) {
        array_push($userArray, $userStudentRecord);
        $userArray[count($userArray) - 1][5] = "Student";
    }

    return $userArray;
}

function getAllStaff($userArray = null){
    if (!(isset($userArray))){$userArray = array();}

    $userStaffStmt = executeQuery("SELECT Users.UserID, Users.Username, Staff.StaffFirstName, Staff.StaffLastName, Staff.StaffEmail 
    FROM stlcc.Users
    INNER JOIN stlcc.Staff ON Staff.UserID = Users.UserID;");
    while($userStaffRecord = incrementQuery($userStaffStmt, 1)) {
        array_push($userArray, $userStaffRecord);
        $userArray[count($userArray) - 1][5] = "Staff";
    }

    return $userArray;
}

function getAllInstructors($userArray = null){
    if (!(isset($userArray))){$userArray = array();}

    $userInstructorStmt = executeQuery("SELECT Users.UserID, Users.Username, Instructors.InstructorFirstName, Instructors.InstructorLastName, Instructors.InstructorEmail 
    FROM stlcc.Users
    INNER JOIN stlcc.Instructors ON Instructors.UserID = Users.UserID;");
    while($userInstructorRecord = incrementQuery($userInstructorStmt, 1)) {
        array_push($userArray, $userInstructorRecord);
        $userArray[count($userArray) - 1][5] = "Instructor";
    }

    return $userArray;
}

/* getUsers(string $query)
 * Returns an array of user records from the database.
 * If a query is provided when the function is called, it 
 * takes a search query as input and checks
 * against users retrieved from getAllUsers()
 * A case insensitive regex comparison is performed
 * Returns: an array of user records, potentially based on the query
 */
function getUsers($userArray, $query) {
    $newUserArray = array();
    $expr = "/$query/i";

    if (!(isset($query)) || $query == "") {
        return $userArray;
    }
    foreach ($userArray as $userRecord) {
        if (( preg_match($expr, $userRecord[1])) || ( preg_match($expr, $userRecord[2])) || (preg_match($expr, $userRecord[3]))) {
            array_push($newUserArray, $userRecord);
        }
    }

    return $newUserArray;
}

/* getFLName(string $username)
 *  This function queries the database for the staff, instructor, or student
 *  record based on the username in the sessions table
 *  Returns: array with first and last name in position 0 and 1 respectively
 */
function getFLName($username) {
    $NameStmt = executeQuery("DECLARE @role varchar= (SELECT [Role] FROM stlcc.Users WHERE Username = '$username');
    IF ((@role = '1') or (@role = '3'))
    BEGIN
        SELECT StaffFirstName, StaffLastName FROM stlcc.Staff WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')
    END
    ELSE IF (@role = '2')
    BEGIN
        SELECT InstructorFirstName, InstructorLastName FROM stlcc.Instructors WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')
    END
    ELSE IF (@role = '4')
    BEGIN
        SELECT StudentFirstName, StudentLastName FROM stlcc.Students WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')
    END");
    $NameRecord = incrementQuery($NameStmt, 1);
    return [$NameRecord[0], $NameRecord[1]];
}

/* getUsertypeID(string $username)
 *  This function queries the database for the staff, instructor, or student
 *  record based on the username in the sessions table
 *  Returns: integer representing the ID for the usertype
 */
function getUsertypeID($username) {
    global $auth;

    switch($auth){
        case "1":
        case "3":
            $IDStmt = executeQuery("SELECT StaffID FROM stlcc.Staff WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')");
            break;
        case "2":
            $IDStmt = executeQuery("SELECT InstructorID FROM stlcc.Instructors WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')");
            break;
        case "4":
            $IDStmt = executeQuery("SELECT StudentID FROM stlcc.Students WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')");
            break;
    }
    $IDRecord = incrementQuery($IDStmt, 1);
    return $IDRecord[0];
}

// getAdminID(string username)
// Gets the ID of an Administrator
function getAdminID($username){
    $adminIDStmt = executeQuery("SELECT AdminID FROM stlcc.Admins WHERE StaffID IN (SELECT StaffID FROM stlcc.Staff WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username'))");
    $adminIDRecord = incrementQuery($adminIDStmt, 1);
    return $adminIDRecord[0];
}

// userSearch(string $query = null, string $role = null)
function userSearch($query = null, $role = null) {
    global $username;
    global $auth;

    $userArray = array();
    if(!(isset($role)) || $role == ""){
        $role = "sfi";
    }
    
    if (preg_match("/f/", $role) && ($auth == "1")) { // Deny access to users manually changing search query role
        $staffArray = getUsers(getAllStaff(), $query);
        $userArray = array_merge($userArray, $staffArray);
    }
    if (preg_match("/i/", $role) && ($auth == "1" || $auth == "3")) { // Deny access to users manually changing search query role
        $instructorArray = getUsers(getAllInstructors(), $query);
        $userArray = array_merge($userArray, $instructorArray);
    }
    if (preg_match("/s/", $role)) {
        $studentArray = getUsers(getAllStudents(), $query);
        $userArray = array_merge($userArray, $studentArray);
    }
    $userArray = sortUserArray($userArray);

    echo   "<table>
                <thead>
                    <tr >
                        <td>User ID</td>
                        <td>Username</td>
                        <td>First Name</td>
                        <td>Last Name</td>
                        <td>E-mail Address</td>
                        <td>User Role</td>
                    </tr>
                </thead>
                <tbody>";
            foreach($userArray as $userEntry) {
                echo "<tr id='user".$userEntry[0]."'>";
                foreach($userEntry as $record) {
                    echo "<td>".$record."</td>";
                }
                echo "</tr>";
            }
    echo "</tbody></table>";
}

/* This function handles system logouts. The session
 * record is removed from the table based on the username
 * retrieved using the session key.
 */
function logout() {
    global $username;

    executeQuery("DELETE FROM stlcc.Sessions WHERE Username = '$username'");
    header("Location: ./login.php");
    exit();
}

/* showInstructors()
 * Generates a form and content to manage system users
 * Returns: html code for the user management page
 */
function manageUsers() {
    global $auth;

    echo "<h1>User Management</h1>";
    switch($auth){
        case "1":
            echo "<input type='text' placeholder='Search All Users' id='userSearchBox' onkeyup='userSearch(this.value)'></input>
            <div id='userSearchResultsBox' class='userMgmtBox' onload='operation($(this).children(\":nth-child(1)\").innerHTML'>";
            userSearch();
            echo "</div><script type='application/javascript'>$(.userMgmtBox > table > tbody > tr).onclick = function(){operation($(this).children(':nth-child(1)').innerHTML);};</script>";
            
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

/* showStaff()
 * Generates content for the staff page
 * Returns: html code for staff page
 */
function showStaff(){
    global $auth;
    global $username;

    echo "<h1>Staff</h1>";
    switch($auth){
        case "1":
            echo "<input type='text' placeholder='Search Users' id='userSearchBox' onkeyup='userSearch(this.value, \"f\")'></input>
            <div id='userSearchResultsBox'>";
            userSearch(null, 'f');
            echo "</div>";
            break;
        default:
        echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

/* showInstructors()
 * Generates content for the instructors page
 * Returns: html code for instructors page
 */
function showInstructors(){
    global $auth;
    global $conn;
    global $username;

    echo "<h1>Instructors</h1>";
    switch($auth){
        case "1":
            echo "<input type='text' placeholder='Search Instructors' id='userSearchBox' onkeyup='userSearch(this.value, \"i\")'></input>
            <div id='userSearchResultsBox'>";
            userSearch(null, 'i');
            echo "</div>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

function showStudents(){
    global $auth;
    global $username;

    echo "<h1>Students</h1>";
    switch($auth){
        case "1":
        case "2":
        case "3":
            echo "<input type='text' placeholder='Search Students' id='userSearchBox' onkeyup='userSearch(this.value, \"s\");'></input>
            <div id='userSearchResultsBox'>";
            userSearch(null, "s");
            echo "</div>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

/* showCourses()
 * Generates content for the courses page
 * Uses session cookie to determine username
 */
function showCourses(){
    global $auth;
    global $username;

    echo "<h1>Courses</h1>";
    switch($auth){
        case "1":
        case "2":
        case "3":
        case "4":
            $courseStmt = executeQuery("SELECT * FROM stlcc.Courses");
            echo 
            "<table>
                <thead>
                    <tr>
                        <td>Course ID</td>
                        <td>Course Name</td>
                        <td>Course Description</td>
                    </tr>
                </thead>
                    <tbody>";
            while ($courseRecord = incrementQuery($courseStmt, 1)) {
                echo "<tr>";
                foreach($courseRecord as $record) {
                    echo "<td>".$record."</td>";
                }
                echo "</tr>";
            }
            echo    "</tbody>
            </table>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

/* showClasses()
 * Generates content for the classes page
 * Uses session cookie to determine username
 */
function showClasses(){
    global $auth;
    global $username;

    echo "<h1>Classes</h1>";
    switch($auth){
        case "1":
        case "3":
            $classStmt = executeQuery("SELECT Classes.ClassID, Classes.CourseID, concat(Instructors.InstructorLastName, ', ', Instructors.InstructorFirstName) AS Instructor, Classes.ClassDate, Classes.StartTime, Classes.EndTime, Classes.[Days] FROM stlcc.Classes
            INNER JOIN stlcc.Instructors ON Instructors.InstructorID = Classes.InstructorID");
            echo 
            "<table>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course ID</td>
                        <td>Instructor</td>
                        <td>Start Date</td>
                        <td>Class Start Time</td>
                        <td>Class End Time</td>
                        <td>Days</td>
                    </tr>
                </thead>
                    <tbody>";
            while ($classRecord = incrementQuery($classStmt, 1)) {
                echo "<tr>";
                foreach($classRecord as $record) {
                    echo "<td>".$record."</td>";
                }
                echo "</tr>";
            }
            echo    "</tbody>
            </table>";
            break;
        case "2":
            $id = getUsertypeID($username);
            
            $classStmt = executeQuery("SELECT ClassID, CourseID, ClassDate, StartTime, EndTime, [Days]
            FROM stlcc.Classes
            WHERE InstructorID = '$id'");
            echo 
            "<table>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course ID</td>
                        <td>Start Date</td>
                        <td>Class Start Time</td>
                        <td>Class End Time</td>
                        <td>Days</td>
                    </tr>
                </thead>
                    <tbody>";
            while ($classRecord = incrementQuery($classStmt)) {
                echo "<tr>";
                foreach($classRecord as $record) {
                    echo "<td>".$record."</td>";
                }
                echo "</tr>";
            }
            echo    "</tbody>
            </table>";
            break;
        case "4":
            $id = getUsertypeID($username);
            $classStmt = executeQuery("SELECT stlcc.Classes.ClassID,
            Classes.CourseID,
            concat(Instructors.InstructorLastName, ', ', Instructors.InstructorFirstName) AS Instructor,
            Classes.ClassDate,
            Classes.StartTime,
            Classes.EndTime,
            Classes.[Days]
            FROM stlcc.Classes
                    INNER JOIN stlcc.Instructors ON Instructors.InstructorID = Classes.InstructorID
                        INNER JOIN stlcc.Enrollment ON Enrollment.ClassID = Classes.ClassID
                            WHERE Enrollment.StudentID = '$id'");
            echo 
            "<table>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course ID</td>
                        <td>Instructor</td>
                        <td>Start Date</td>
                        <td>Class Start Time</td>
                        <td>Class End Time</td>
                        <td>Days</td>
                    </tr>
                </thead>
                    <tbody>";
            while ($classRecord = incrementQuery($classStmt, 1)) {
                echo "<tr>";
                foreach($classRecord as $record) {
                    echo "<td>".$record."</td>";
                }
                echo "</tr>";
            }
            echo    "</tbody>
            </table>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

function showAttendance() {
    global $auth, $username;

    switch($auth){
        case "1":
        case "3":
            echo "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'>All Student Percentage</div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'>Average Student Percentage</div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'>All Student Lates</div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'>All Student Absences</div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'>Interactive Calendar</div>";
            echo "</div>";
            break;
        case "2":
            echo "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'>My Student Percentage</div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'>My Avergage Student Percentage</div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'>My Student Lates</div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'>My Student Absences</div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'>Interactive Calendar</div>";
            echo "</div>";
            break;
        case "4":
            echo "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'>My Percentage</div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'>My Upcoming Classes</div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'>My Lates</div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'>My Absences</div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'>Interactive Calendar</div>";
            echo "</div>";
            break;
    }
}

/* showDashboard()
 * Generates content for the dashboard page
 * Uses session cookie to determine username
 */
function showDashboard() {
    global $auth, $username;

    $nameArray = getFLName($username);
    $usertypeID = getUsertypeID($username);

    echo "<h1>Dashboard</h1>
    <h2>Welcome back, ".$nameArray[0]." ".$nameArray[1]."</h2>";
    switch($auth){
        case "1":
            $adminID = getAdminID($username);
            echo "<p>Administrator ID: $adminID</p>";
            echo "<p>Staff ID: $usertypeID</p>";
            break;
        case "2":
            echo "Instructor ID: $usertypeID</p>";
            break;
        case "3":
            echo "Staff ID: $usertypeID</p>";
            break;
        case "4":
            echo "Student ID: $usertypeID</p>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
} 

function showUserPage() {
    global $auth;
    global $username;

    $nameArray = getFLName($username);
    $usertypeID = getUsertypeID($username);

    echo "<h1>Account Details</h1>
    <h3>Name</h3>
    </p>".$nameArray[0]." ".$nameArray[1]."</p>";
    switch($auth){
        case "1":
            $adminID = getAdminID($username);
            echo "<h3>Administrator ID</h3>
            </p>$adminID</p>";
            echo "<h3>Staff ID</h3>
            <p>$usertypeID</p>";
            break;
        case "2":
            echo "Instructor ID: $usertypeID</p>";
            break;
        case "3":
            echo "Staff ID: $usertypeID</p>";
            break;
        case "4":
            echo "Student ID: $usertypeID</p>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
} 