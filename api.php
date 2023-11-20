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

/* getAttendAvgs(Array $userArray = null)
 * This function gets classdates and attendance records from
 * the database and compares them with either the passed
 * list of students, or if not supplied, then all students
 * available to the invoking user.
 * Returns: Array containing total classes, attended classes, and lates, and total students.
 */
function getAttendAvgs ($userArray = null) {
    if(!(isset($userArray))){$userArray = getAllStudents();}
    $userIDs = [];
    foreach ($userArray as $record){
        array_push($userIDs, $record[0]);
    }
    $attendQuery = "SELECT s.UserID, 
    CAST(a.AttendanceDateTime AS DATE) AS AttendDate, 
    CAST(a.AttendanceDateTime AS TIME) AS AttendTime, 
    c.StartTime AS ClassStart, 
    c.EndTime AS ClassEnd, 
    a.ClassID FROM (VALUES ";
    foreach ($userIDs as $userID){
        $attendQuery .= "($userID), ";
    }
    $attendQuery = rtrim($attendQuery, ", ");
    $attendQuery .= ") AS s (UserID) 
            LEFT JOIN stlcc.Attendance a ON a.UserID = s.UserID 
                INNER JOIN stlcc.Classes c ON c.ClassID = a.ClassID 
        ORDER BY AttendDate";

    $attendStmt = executeQuery($attendQuery);

    $classDatesQuery = "SELECT ClassDate, cd.ClassID, Canceled, e.UserID FROM stlcc.ClassDates cd
        LEFT JOIN stlcc.Enrollment e ON e.ClassID = cd.ClassID
        WHERE ClassDate <= CAST(GETDATE() AS DATE) AND ClassDate > '2023-10-10'
        ORDER BY ClassDate, UserID"; // Don't return classDates after today and before semester beginning
                                     // Beginning date is hardcoded here but could easily be set to a value 
                                     // queried from the database - Out of scope for now

    $classDatesStmt = executeQuery($classDatesQuery);

    $classDates = [];
    while ($classDatesRecord = incrementQuery($classDatesStmt)){
        if(in_array($classDatesRecord['UserID'], $userIDs)){
            array_push($classDates, $classDatesRecord);
        }
    }

    $allAttendRecords = [];
    while ($attendRecord = incrementQuery($attendStmt)){
        array_push($allAttendRecords, $attendRecord);
    }

    $attendRecordsByID = [];
    foreach ($userIDs as $userID){
        $recordArray = [];
        foreach ($allAttendRecords as $currAttendRecord){
            if ($currAttendRecord['UserID'] == $userID){
                array_push($recordArray, $currAttendRecord);
            }
        }
        array_push($attendRecordsByID, $recordArray);
    }
    $attended = 0;
    $total = 0;
    $late = 0;
    foreach ($classDates as $classDate) {
        if ($classDate['Canceled'] == 1){break;}
        $total+=1;
        foreach ($attendRecordsByID as $attendRecordIDN){
            foreach ($attendRecordIDN as $attendRecord){
                if (($attendRecord['UserID'] == $classDate['UserID']) && ($attendRecord['AttendDate'] == $classDate['ClassDate'])){
                    if (strtotime($attendRecord['AttendTime']) > (strtotime($attendRecord['ClassStart']) + 300)){
                        $late+=1;
                    }
                    $attended+=1;
                    break;
                }
            }

        }
    }
    return [$total, $attended, $late, count($userIDs)];
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

function getAllStudents($userArray = null){
    global $auth;
    global $userID;

    if (!(isset($userArray))){$userArray = array();}

    if ($auth == "2"){
        $userStudentStmt = executeQuery("SELECT UserID, Username, FirstName, LastName, Phone, Email 
        FROM stlcc.Users
        WHERE UserTypeID = 4
        AND UserID IN (SELECT e.UserID FROM stlcc.Enrollment e
             JOIN stlcc.Instruction i ON i.ClassID = e.ClassID
                WHERE i.UserID = '$userID')");
    } else if ($auth == "4") {
        $userStudentStmt = executeQuery("SELECT UserID, Username, FirstName, LastName, Phone, Email 
        FROM stlcc.Users
        WHERE UserID = $userID");
    } else {
        $userStudentStmt = executeQuery("SELECT UserID, Username, FirstName, LastName, Phone, Email
        FROM stlcc.Users
        WHERE UserTypeID = 4");
    }

    while($userStudentRecord = incrementQuery($userStudentStmt, 1)) {
        array_push($userArray, $userStudentRecord);
        $userArray[count($userArray) - 1][6] = "Student";
    }

    return $userArray;
}

function getAllStaff($userArray = null){
    if (!(isset($userArray))){$userArray = array();}

    $userStaffStmt = executeQuery("SELECT UserID, Username, FirstName, LastName, Phone, Email
    FROM stlcc.Users
    WHERE UserTypeID = 3");
    while($userStaffRecord = incrementQuery($userStaffStmt, 1)) {
        array_push($userArray, $userStaffRecord);
        $userArray[count($userArray) - 1][6] = "Staff";
    }

    return $userArray;
}

function getAllInstructors($userArray = null){
    if (!(isset($userArray))){$userArray = array();}

    $userInstructorStmt = executeQuery("SELECT UserID, Username, FirstName, LastName, Phone, Email
    FROM stlcc.Users
    WHERE UserTypeID = 2");
    while($userInstructorRecord = incrementQuery($userInstructorStmt, 1)) {
        array_push($userArray, $userInstructorRecord);
        $userArray[count($userArray) - 1][6] = "Instructor";
    }

    return $userArray;
}

function getAllAdministrators($userArray = null){
    if (!(isset($userArray))){$userArray = array();}

    $userAdminStmt = executeQuery("SELECT UserID, Username, FirstName, LastName, Phone, Email
    FROM stlcc.Users
    WHERE UserTypeID = 1");
    while($userAdminRecord = incrementQuery($userAdminStmt, 1)) {
        array_push($userArray, $userAdminRecord);
        $userArray[count($userArray) - 1][6] = "Administrator";
    }

    return $userArray;
}

/* getUsers(array $userArray, string $query)
 * Returns an array of user records from the database.
 * If a query is provided when the function is called, it 
 * takes a search query as input and checks the given user
 * array for matching entries
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
    $NameStmt = executeQuery("SELECT FirstName, LastName FROM stlcc.Users WHERE Username = '$username'");
    $NameRecord = incrementQuery($NameStmt, 1);
    return [$NameRecord[0], $NameRecord[1]];
}

/* getUserID(string $username)
 *  This function queries the database for the staff, instructor, or student
 *  record based on the username given
 *  Returns: integer representing the ID for the usertype
 */
function getUserID($username) {
    $userIDStmt = executeQuery("SELECT UserID FROM stlcc.Users where Username = '$username'");
    $userIDRecord = incrementQuery($userIDStmt, 1);
    return $userIDRecord[0];
}

// usersSearch(string $query = null, string $role = null)
function usersSearch($query = null, $role = null) {
    global $username;
    global $auth;

    $userArray = array();
    if(!(isset($role)) || $role == ""){
        $role = "sfia";
    }
    
    if (preg_match("/f/", $role) && ($auth == "1")) { // Deny access to users manually changing search query role
        $staffArray = getUsers(getAllStaff(), $query);
        $userArray = array_merge($userArray, $staffArray);
    }
    if (preg_match("/a/", $role) && ($auth == "1")) {
        $adminArray = getUsers(getAllAdministrators(), $query);
        $userArray = array_merge($userArray, $adminArray);
    }
    if (preg_match("/i/", $role) && ($auth == "1" || $auth == "3")) { // Deny access to users manually changing search query role
        $instructorArray = getUsers(getAllInstructors(), $query);
        $userArray = array_merge($userArray, $instructorArray);
    }
    if (preg_match("/s/", $role) && ($auth == "1" || $auth == "2" || $auth == "3")) {
        $studentArray = getUsers(getAllStudents(), $query);
        $userArray = array_merge($userArray, $studentArray);
    }

    $userArray = sortUserArray($userArray);

    echo   "<table class='infoTable'>
                <thead>
                    <tr >
                        <td>User ID</td>
                        <td>Username</td>
                        <td>First Name</td>
                        <td>Last Name</td>
                        <td>Phone</td>
                        <td>E-mail Address</td>
                        <td>User Role</td>
                    </tr>
                </thead>
                <tbody>";
            foreach($userArray as $userEntry) {
                echo "<tr data-userid='".$userEntry[0]."'>";
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
    global $userID;

    executeQuery("DELETE FROM stlcc.Sessions WHERE UserID = $userID");
    header("Location: ./login.php");
    exit();
}

function attendClass($classID, $accessCode) {
    global $userID, $auth;

    switch($auth){
        case "4":
            $codeStmt = executeQuery("SELECT c.ClassID, codes.AccessCode, c.StartTime, c.EndTime from stlcc.Classes c
                                    JOIN stlcc.AccessCodes codes ON c.CodeID = codes.CodeID
                                    WHERE c.ClassID = $classID");
            $codeFromDB = incrementQuery($codeStmt, 1)[1];
            while($codeRecord = incrementQuery($codeStmt, 1)){
                array_push($codeArray, $codeRecord);
            }
            $currTimeSql = date('H:i:s');
            if (($accessCode == $codeFromDB) && (strtotime("-5 minutes", $codeArray[0][2]) < strtotime($currTimeSql)) && (strtotime($codeArray[0][3]) > strtotime($currTimeSql))){
                $attendStmt = executeQuery("INSERT INTO stlcc.Attendance
                                            VALUES
                                            ('$currTimeSql', '$userID', '$classID');");
            } else {
                echo "<p style='color: red'>Error: Incorrect Access code.</p>";
            }
                
            break;
        default:
            echo "<p style='color: red'>Error: Invalid API call for UserID: $userID, Auth level: $auth</p>";
            break;
    }
}

/* showAttendWindow()
 * This function generated the attendance form for
 * students to mark themselves present for a class
 * Returns: html code for the attendance window
 */ 
function showAttendWindow() {
    global $userID;

    $classArray = [];
    $enrollmentStmt = executeQuery("SELECT ClassID FROM stlcc.Enrollment WHERE UserID = $userID");
    while($enrollmentRecord = incrementQuery($enrollmentStmt, 1)){
        array_push($classArray, $enrollmentRecord[0]);
    }

    $attendQuery = "SELECT co.CourseName, c.ClassID, c.StartTime, c.EndTime, c.StartDate, c.EndDate 
    FROM stlcc.Classes c 
    JOIN stlcc.Courses co ON c.CourseID = co.CourseID
        JOIN stlcc.ClassDates cd ON cd.ClassID = c.ClassID
                WHERE c.StartDate <= CAST(GETDATE() AS DATE) 
                AND c.EndDate >= CAST(GETDATE() AS DATE) 
                AND cd.ClassDate = CAST(GETDATE() AS DATE) 
                AND (DATEADD(MINUTE, -5, c.StartTime) <= CAST(GETDATE() AS TIME) AND c.EndTime > CAST(GETDATE() AS TIME))
                AND c.ClassID IN (";
    foreach ($classArray as $class){
        $attendQuery .= "$class, ";
    }
    $attendQuery = rtrim($attendQuery, ",  ").");";
    $attendStmt = executeQuery($attendQuery);
    $attendArray = [];
    while($attendRecord = incrementQuery($attendStmt, 1)){
        array_push($attendArray, $attendRecord);
    }

    echo "<div class='overlay' onclick='closeAttendWindow()'></div>
        <div id='attendClassWindow'>
            <div id='attendClassWindowCloseButton' onclick='closeAttendWindow()'></div>
                <div class='attendFormDescription'>
                    <p>Please select your class from the list and enter your access code in the box below.</p>
                </div>
                <div class='attendFormFieldGroup'>
                <div id='attendWindowMessageBox'></div>
                    <div class='attendFormFieldBox attendFormInputContainer'>
                        <select id='classChoice'>
                            <option value='D'>Please select a Class</option>";
    foreach ($attendArray as $attendRecord){
        echo "<option value='".$attendRecord[1]."'>".$attendRecord[1].": ".$attendRecord[0]."</option>";
    }

    echo           "</select>
                </div>
                <div class='attendFormFieldBox'>
                    <input type='text' id='accessCode' placeholder='Ex. ABC1-XYZ2'></input>
                </div>
            </div>
            <div class='attendFormFieldGroup attendFormButtonGroup attendFormInputContainer'>
                <button id='submitAttendFormButton' onclick='attendClass($(\"#classChoice\").val(), $(\"#accessCode\").val())'>Attend Class</button>
            </div>
        </div>";
}

function manageUser ($userID) {
    global $auth;

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Edit User Details</h2></div>";
    echo "
    <table class='manageUserDetailsTable userDetails'>
        <tbody>
            <tr>
                <td><input type='text' id='username'></input></td>
                <td>Change the user's account username.</td>
            </tr>
            <tr>
                <td><input type='text' id='fName'></input></td>
                <td>Change the user's first name.</td>
            </tr>
            <tr>
                <td><input type='text' id='lName'></input></td>
                <td>Change the user's last name.</td>
            </tr>
            <tr>
                <td><input type='text' id='email'></input></td>
                <td>Change the user's email prefix.</td>
            </tr>
            <tr>
                <td>+1 (<input type='text' id='phone-area' class='phoneArea' size='3'></input>) <input id='phone-prefix' class='phonePrefix' size='3'></input>-<input id='phone-line' class='phoneLine' size='4'></input></td>
                <td>Change the user's phone number.</td>
            </tr>
            <tr>
                <td><input type='checkbox' id='accoutnDisabled'>Account Disabled</input></td>
                <td>Check this box to disable the account.</td>
            </tr>
        </tbody>
    </table>
    <table class='manageUserDetailsTable userPassword'>
        <tbody>
            <tr>
                <td><input type='password' id='userPassword'></input></td>
                <td></td>
            </tr>
            <tr>
                <td><input type='password' id='confirmPassword'></input></td>
                <td></td>
            </tr>
        </tbody>
    </table>";
}

/* showUserManagement()
 * Generates a form and content to manage system users
 * Returns: html code for the user management page
 */
function showUserManagement() {
    global $auth;

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>User Management</h2></div>";
    switch($auth){
        case "1":
            echo "<input type='text' placeholder='Search All Users' id='userSearchBox' onkeyup='usersSearch(this.value)'></input>
            <div class='searchRoleOptions'>
                <input type='checkbox' value='s' checked onclick='usersSearch(getElementById(\"userSearchBox\").value)'>Students</input>
                <input type='checkbox' value='f' checked onclick='usersSearch(getElementById(\"userSearchBox\").value)'>Staff</input>
                <input type='checkbox' value='i' checked onclick='usersSearch(getElementById(\"userSearchBox\").value)'>Instructors</input>
                <input type='checkbox' value='a' checked onclick='usersSearch(getElementById(\"userSearchBox\").value)'>Admins</input>
            </div>
            <div id='userSearchResultsBox' class='userMgmtBox'>";
            usersSearch();
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

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Staff</h2></div>";
    switch($auth){
        case "1":
            echo "<input type='text' placeholder='Search Staff' id='userSearchBox' onkeyup='usersSearch(this.value, \"f\")'></input>
            <div id='userSearchResultsBox'>";
            usersSearch(null, 'f');
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

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Instructors</h2></div>";
    switch($auth){
        case "1":
            echo "<input type='text' placeholder='Search Instructors' id='userSearchBox' onkeyup='usersSearch(this.value, \"i\")'></input>
            <div id='userSearchResultsBox'>";
            usersSearch(null, 'i');
            echo "</div>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

/* showStudents()
 * Generates content for the students page
 * Returns: html code for students page
 */
function showStudents(){
    global $auth;
    global $username;

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Students</h2></div>";
    switch($auth){
        case "1":
        case "2":
        case "3":
            echo "<input type='text' placeholder='Search Students' id='userSearchBox' onkeyup='usersSearch(this.value, \"s\");'></input>
            <div id='userSearchResultsBox'>";
            usersSearch(null, "s");
            echo "</div>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

/* showCourses()
 * Generates content for the courses page
 */
function showCourses(){
    global $auth;
    global $username;

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Courses</h2></div>";
    switch($auth){
        case "1":
        case "2":
        case "3":
        case "4":
            $courseStmt = executeQuery("SELECT * FROM stlcc.Courses");
            echo 
            "<div class='resultsBox'>
                <table class='infoTable'>
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
            </table></div>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

/* showClasses()
 * Generates content for the classes page
 */
function showClasses(){
    global $auth;
    global $userID;

    $daysArray = [null, "S", "M", "T", "W", "TH", "F", "SA"];

    $classStmt = "";
    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Classes</h2></div>";
    switch($auth){
        case "1":
        case "3":
            $classStmt = executeQuery("SELECT Classes.ClassID, Classes.CourseID, concat(Users.LastName, ', ', Users.FirstName)
            AS Instructor, Classes.StartDate, Classes.EndDate, Classes.StartTime, Classes.EndTime, Classes.[Days] FROM stlcc.Classes
            INNER JOIN stlcc.Instruction ON Instruction.ClassID = Classes.ClassID
                INNER JOIN stlcc.Users ON Users.UserID = Instruction.UserID");
            echo 
            "<table class='infoTable'>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course ID</td>
                        <td>Instructor</td>
                        <td>Start Date</td>
                        <td>End Date</td>
                        <td>Start Time</td>
                        <td>End Time</td>
                        <td>Days</td>
                    </tr>
                </thead>
                    <tbody>";
            break;
        case "2":
            $classStmt = executeQuery("SELECT Classes.ClassID, CourseID, StartDate, EndDate, StartTime, EndTime, [Days]
            FROM stlcc.Classes
            JOIN stlcc.Instruction ON Classes.ClassID = Instruction.ClassID
            WHERE Instruction.UserID = '$userID'");
            echo 
            "<table class='infoTable'>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course ID</td>
                        <td>Start Date</td>
                        <td>End Date</td>
                        <td>Start Time</td>
                        <td>End Time</td>
                        <td>Days</td>
                    </tr>
                </thead>
                    <tbody>";
            break;
        case "4":
            $classStmt = executeQuery("SELECT Classes.ClassID,
            Classes.CourseID,
            concat(Users.LastName, ', ', Users.FirstName) AS Instructor,
            Classes.StartDate,
            Classes.EndDate,
            Classes.StartTime,
            Classes.EndTime,
            Classes.[Days]
            FROM stlcc.Classes
                    LEFT JOIN stlcc.Instruction ON Instruction.ClassID = Classes.ClassID
                        INNER JOIN stlcc.Enrollment ON Enrollment.ClassID = Classes.ClassID
                            LEFT JOIN stlcc.Users ON Users.UserID = Instruction.UserID
                                WHERE Enrollment.UserID = '$userID'");
            echo 
            "<table class='infoTable'>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course ID</td>
                        <td>Instructor</td>
                        <td>Start Date</td>
                        <td>End Date</td>
                        <td>Start Time</td>
                        <td>End Time</td>
                        <td>Days</td>
                    </tr>
                </thead>
                    <tbody>";
            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            return;
    }
    while ($classRecord = incrementQuery($classStmt, 1)) {
        echo "<tr>";
        $i = 0;
        $daysString = "";
        foreach($classRecord as $record) {
            if ($i == 7 || ($auth == "2" && $i == 6)) {
                echo "<td>";
                $j = 0;
                foreach (str_split(str_pad(decbin(ord($record)), 8, "0", STR_PAD_LEFT)) as $currPos) {
                    if ($j == 0){
                        $j++;
                        continue;
                    }
                    if ($currPos == "1"){
                        $daysString .= $daysArray[$j].", ";
                    }
                    if ($j == 7){
                        $j++;
                        break;
                    }
                    $j++;
                }
                
                echo rtrim($daysString, ", ")."</td>";
            } else {
                echo "<td>".$record."</td>";
            }
            $i++;
        }
        echo "</tr>";
    }
    echo    "</tbody>
    </table>";
    return;
}

function showAttendance() {
    global $auth, $username;
    
    $attendArray = getAttendAvgs();
    
    $avgClassesAttendedPerc = ($attendArray[1] / $attendArray[0]) * 100;
    $avgLatePercentage = ($attendArray[2] / $attendArray[1]) * 100;
    $avgAbsences = ($attendArray[0] - $attendArray[1]) / $attendArray[3];
    $avgLates = $attendArray[2] / $attendArray[3];
    
    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Attendance Dashboard</h2></div>";
    switch($auth){
        case "1":
        case "3":
            echo "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'><p class='attendGridItemHeading'>Student Attendance Percentage</p><p class='attendGridItemResult'>".number_format($avgClassesAttendedPerc, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'><p class='attendGridItemHeading'>Student Late Percentage</p><p class='attendGridItemResult'>".number_format($avgLatePercentage, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'><p class='attendGridItemHeading'>Average Absences / Student</p><p class='attendGridItemResult'>".number_format($avgAbsences, 2)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'><p class='attendGridItemHeading'>Average Lates / Student</p><p class='attendGridItemResult'>".number_format($avgLates, 2)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'><p class='attendGridItemHeading'>Ongoing Classes</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem6'><p class='attendGridItemHeading'>Report Center</p></div>";
            echo "</div>";
            break;
        case "2":
            echo "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'><p class='attendGridItemHeading'>Student Attendance Percentage</p><p class='attendGridItemResult'>".number_format($avgClassesAttendedPerc, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'><p class='attendGridItemHeading'>Student Late Percentage</p><p class='attendGridItemResult'>".number_format($avgLatePercentage, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'><p class='attendGridItemHeading'>Average Absences / Student</p><p class='attendGridItemResult'>".number_format($avgAbsences, 2)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'><p class='attendGridItemHeading'>Average Lates / Student</p><p class='attendGridItemResult'>".number_format($avgLates, 2)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'><p class='attendGridItemHeading'>My Upcoming Classes</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem6'><p class='attendGridItemHeading'>Report Center</p></div>";
            echo "</div>";
            break;
        case "4":
            echo     "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'><p class='attendGridItemHeading'>My Attendance Percentage</p><p class='attendGridItemResult'>".number_format($avgClassesAttendedPerc, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'><p class='attendGridItemHeading'>My Late Percentage</p><p class='attendGridItemResult'>".number_format($avgLatePercentage, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'><p class='attendGridItemHeading'>My Absences</p><p class='attendGridItemResult'>".number_format($avgAbsences, 0)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'><p class='attendGridItemHeading'>My Lates</p><p class='attendGridItemResult'>".number_format($avgLates, 0)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'><p class='attendGridItemHeading'>My Upcoming Classes</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem6'><p class='attendGridItemHeading'>Report Center</p></div>";
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
    $userID = getUserID($username);

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Dashboard</h2></div>
    <h2>Welcome back, ".$nameArray[0]." ".$nameArray[1]."</h2>";
    echo "User ID: $userID</p>";
    switch($auth){
        case "1":

            break;
        case "2":

            break;
        case "3":

            break;
        case "4":

            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
} 

function showUserPage() {
    global $auth;
    global $userID;
    global $username;

    $nameArray = getFLName($username);

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Account Details</h2></div>
    <h3>Name</h3>
    </p>".$nameArray[0]." ".$nameArray[1]."</p>
    <h3>User ID:</h3><p>$userID</p>";
    switch($auth){
        case "1":

            break;
        case "2":

            break;
        case "3":

            break;
        case "4":

            break;
        default:
            echo "<h2 style='color: red'>Unauthorized</h2>";
            break;
    }
}

function main() {
    if (isset($_POST['operation'])){$operation = $_POST['operation'];} else {$operation = null;}
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
            showUserManagement();
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
        case "attendClass":
            $classID;
            $accessCode;
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {break;}
            if (isset($_POST['accessCode'])){$accessCode = $_POST['accessCode'];} else {break;}
            attendClass($classID, $accessCode);
            break;
        case "showAttendWindow":
            showAttendWindow();
            break;
        case "userSearch":
            if (isset($_POST['search'])){$search = $_POST['search'];} else {$search = null;}
            if (isset($_POST['role'])){$role = $_POST['role'];} else {$role = null;}
            usersSearch($search, $role);
            break;
        case "manageUser":
            if (isset($_POST['userID'])){$userID = $_POST['userID'];} else {break;}
            manageUser($userID);
            break;
        default:
            echo "<p>".(isset($operation) ? "Unknown operation: '$operation'" : "No operation provided." )."</p>";
            break;
    }

    return 1;
}

main();