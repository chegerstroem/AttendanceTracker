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
$operation = $_POST['operation'];
switch($operation){ // parse operation and call function based on result
    case "courses":
        showCourses();
        break;
    case "classes":
        showClasses();
        break;
    case "instructors":
        echo "<p>Not Implemented.</p>";
        break;
    case "students":
        echo "<p>Not Implemented.</p>";
        break;
    case "staff":
        echo "<p>Not Implemented.</p>";
        break;
    case "manageUsers":
        echo "<p>Not Implemented.</p>";
        break;
    case "attendance":
        echo "<p>Not Implemented.</p>";
        break;
    case "dashboard":
        showDashboard();
        break;
    default:
        echo "<p>No operation retrieved</p>";
        break;
    
}

/* getFLName(string $username)
 *  This function queries the database for the staff, instructor, or student
 *  record based on the username in the sessions table
 *  Returns: array with first and last name in position 0 and 1 respectively
 */
function getFLName($username) {
    global $conn;

    $NameQry = "DECLARE @role varchar= (SELECT [Role] FROM stlcc.Users WHERE Username = '$username');
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
    END";
    $NameStmt = $conn->query($NameQry);
    $NameRecord = $NameStmt->fetch(PDO::FETCH_NUM);
    return [$NameRecord[0], $NameRecord[1]];
}

/* getUsertypeID(string $username)
 *  This function queries the database for the staff, instructor, or student
 *  record based on the username in the sessions table
 *  Returns: integer representing the ID for the usertype
 */
function getUsertypeID($username) {
    global $conn;

    $IDQry = "DECLARE @role varchar= (SELECT [Role] FROM stlcc.Users WHERE Username = '$username');
    IF ((@role = '1') or (@role = '3'))
    BEGIN
        SELECT StaffID FROM stlcc.Staff WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')
    END
    ELSE IF (@role = '2')
    BEGIN
        SELECT InstructorID FROM stlcc.Instructors WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')
    END
    ELSE IF (@role = '4')
    BEGIN
        SELECT StudentID FROM stlcc.Students WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username')
    END";
    $IDStmt = $conn->query($IDQry);
    $IDRecord = $IDStmt->fetch(PDO::FETCH_NUM);
    return $IDRecord[0];
}

function getAdminID($username){
    global $conn;

   $adminIDQry = "SELECT AdminID FROM stlcc.Admins WHERE StaffID IN (SELECT StaffID FROM stlcc.Staff WHERE UserID IN (SELECT UserID FROM stlcc.Users WHERE Username = '$username'))";
   $adminIDStmt = $conn->query($adminIDQry);
   $adminIDRecord = $adminIDStmt->fetch(PDO::FETCH_NUM);
   return $adminIDRecord[0];
}

/* showCourses()
 * Generates content for the courses page
 * Uses session cookie to determine username
 */
function showCourses(){
    global $auth;
    global $conn;
    global $username;

    echo "<h1>Courses</h1>";
    switch($auth){
        case "1":
            $courseQry = "SELECT * FROM stlcc.Courses";
            $courseStmt = $conn->query($courseQry);
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
            while ($courseRecord = $courseStmt->fetch(PDO::FETCH_ASSOC)) {
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
            break;
        case "2":
            break;
        case "3":
            break;
        case "4":
            break;
    }
}

/* showClasses()
 * Generates content for the classes page
 * Uses session cookie to determine username
 */
function showClasses(){
    global $auth;
    global $conn;
    global $username;

    echo "<h1>Classes</h1>";
    switch($auth){
        case "1":
            $classQry = "SELECT * FROM stlcc.Classes";
            $classStmt = $conn->query($classQry);
            echo 
            "<table>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course ID</td>
                        <td>Instructor ID</td>
                        <td>Start Date</td>
                        <td>Class Start Time</td>
                        <td>Class End Time</td>
                        <td>Days</td>
                    </tr>
                </thead>
                    <tbody>";
            while ($classRecord = $classStmt->fetch(PDO::FETCH_ASSOC)) {
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
            $classQry = "SELECT ClassID, CourseID, ClassDate, StartTime, EndTime, [Days]
            FROM stlcc.Classes
            WHERE InstructorID = '$id'";
            
            $classStmt = $conn->query($classQry);
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
            while ($classRecord = $classStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                foreach($classRecord as $record) {
                    echo "<td>".$record."</td>";
                }
                echo "</tr>";
            }
            echo    "</tbody>
            </table>";
            break;
        case "3":
            break;
        case "4":
            break;
    }
}

/* showDashboard()
 * Generates content for the dashboard page
 * Uses session cookie to determine username
 */
function showDashboard() {
    global $auth;
    global $conn;
    global $username;

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
    }
} 
