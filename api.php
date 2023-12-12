<?php
/* 
    Author     : Christian H - All code
*/

// Ensure time matches current timezone (as does the database)
date_default_timezone_set('America/Chicago');

//FIXME: Remove debug messages before project completion
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "db.php"; // include db connection php
require "session.php"; // verify session

// Bring in FPDF
require "fpdf/fpdf.php";

// Set FPDF flags

$BORDER_LEFT="L";
$BORDER_RIGHT="R";
$BORDER_TOP="T";
$BORDER_BOTTOM="B";
$NO_BORDER=0;
$BORDER=1;

$ALIGN_CENTER="C";
$ALIGN_LEFT="L";
$ALIGN_RIGHT="R";
$JUSTIFY="J";

$FILL = true;
$NO_FILL = false;

$LN_RIGHT = 0;
$LN_NEXT = 1;
$LN_BELOW = 2;

$OUT_STRING="S";

// Set default height and width
$CELL_HEIGHT=5;
$CELL_WIDTH=30;

// Class Definitions

class REPORT_PDF extends FPDF
    {
        function Header() {
            $time = date('g:i:s A');
            $date = date('Y-m-d');
            $this->Image('/srv/http/img/stlcc_logo_pdf.png',10,6,30);
            $this->SetY($this->GetY() - 5);
            $this->SetFont("SourceSansPro-Regular","",14);
            $this->SetX($this->GetX() + 70);
            $this->Cell(50,5,'Student Attendance Report',0,0,'C');
            $xPos = $this->GetX();
            $this->SetX($xPos + 50);
            $this->SetFont("SourceSansPro-Regular","",8);
            $xPos = $this->GetX();
            $this->Cell(20,4,$date,0,0,'C');
            $this->Ln();
            $this->SetX($xPos);
            $this->Cell(20,4,$time,0,0,'C');
            $this->Ln(10);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont("SourceSansPro-Regular","",8);
            $this->Cell(0,10,'Page '.$this->PageNo().' of {nb}',0,0,'C');
        }

        function TableHeader($header) {
            global $CELL_WIDTH, $CELL_HEIGHT, $BORDER_TOP, $BORDER_LEFT, $BORDER_RIGHT, $BORDER_BOTTOM, $ALIGN_CENTER, $FILL, $LN_RIGHT;
            $this->SetFillColor(210,210,210);
            $first = true;
            $i=0;
            foreach($header as $col) {
                if ($first) {
                    $this->Cell($CELL_WIDTH + 10,$CELL_HEIGHT,$col,$BORDER_TOP.$BORDER_LEFT.$BORDER_BOTTOM, $LN_RIGHT,$ALIGN_CENTER, $FILL); 
                    $first = false;
                } else {
                    if (isset($header[$i+1])) {
                        $this->Cell($CELL_WIDTH,$CELL_HEIGHT,$col,$BORDER_TOP.$BORDER_BOTTOM, $LN_RIGHT, $ALIGN_CENTER, $FILL);
                    } else {
                        $this->Cell($CELL_WIDTH,$CELL_HEIGHT,$col,$BORDER_TOP.$BORDER_BOTTOM.$BORDER_RIGHT, $LN_RIGHT, $ALIGN_CENTER, $FILL);
                    }
                }
                $i++;
            }
            $this->Ln();
        }


        function CreateAvgTable($reportRecords) {
            global $CELL_HEIGHT, $CELL_WIDTH, $BORDER,
            $BORDER_BOTTOM, $BORDER_LEFT, $BORDER_RIGHT,
            $ALIGN_CENTER, $ALIGN_LEFT, $ALIGN_RIGHT,
            $OUT_STRING, $JUSTIFY, $FILL, $NO_FILL,
            $NO_BORDER;
            
            // return ["TotalAttended"=>$attended, "TotalLates"=>$late, "TotalDates"=>$total, "Records"=>$reportRecords, "StudentCount"=>count($userArray)];
            // return [$total, $attended, $late, count($userIDs)];

            $avgClassesAttendedPerc = round(($reportRecords['TotalAttended'] / $reportRecords['TotalDates']) * 100, 2);
            $avgLatePerc = $reportRecords['TotalAttended'] == 0 ? 0 : round(($reportRecords['TotalLates'] / $reportRecords['TotalAttended']) * 100, 2);
            $avgAbsences = $reportRecords['TotalAttended'] == 0 ? 0 : ($reportRecords['TotalDates'] - $reportRecords['TotalAttended']) / $reportRecords['StudentCount'];
            $avgLates = $reportRecords['TotalLates'] == 0 ? 0 : $reportRecords['TotalLates'] / $reportRecords['StudentCount'];
            $absences =  $reportRecords['TotalDates'] - $reportRecords['TotalAttended'];

            $this->MultiCell(($CELL_WIDTH * 6) + 10, $CELL_HEIGHT, "Total Dates: ".$reportRecords['TotalDates']."     Total Absences: ".$absences."     Attended: ".$reportRecords['TotalAttended']."     Student Lates: ".
            $reportRecords['TotalLates']."     Average Classes Attended: ".$avgClassesAttendedPerc."%     Average Lates: ".$avgLatePerc."%",
             $NO_BORDER, $ALIGN_CENTER, $NO_FILL);
            $this->Ln();
        }

        function CreatePages($header, $reportRecords) {
            global $CELL_HEIGHT, $CELL_WIDTH, $BORDER,
            $BORDER_BOTTOM, $BORDER_LEFT, $BORDER_RIGHT,
            $ALIGN_CENTER, $ALIGN_LEFT, $ALIGN_RIGHT,
            $OUT_STRING, $JUSTIFY, $FILL, $NO_FILL;

            $shaded = true;
            $this->SetFillColor(255,255,255);
            $xPos = $this->GetX() + $CELL_WIDTH + 10;
            foreach($reportRecords["Records"] as $reportRecord) {
                if (count($reportRecord["AttendDates"]) < 7) {
                    $cellHeight = ($CELL_HEIGHT * 7) / count($reportRecord["AttendDates"]);
                    $cellHeightClassDate = $CELL_HEIGHT;
                } else {
                    $cellHeight = $CELL_HEIGHT;
                    $cellHeightClassDate = ($CELL_HEIGHT * count($reportRecord["AttendDates"])) / 7;
                }
                if ((($this->GetY() + $cellHeightClassDate) > ($this->GetPageHeight() - 60)) ||
                (count($reportRecord["AttendDates"]) >= 10 && (($this->GetY() + $cellHeightClassDate) > ($this->GetPageHeight() - 70))) ||
                (count($reportRecord["AttendDates"]) >= 13 && (($this->GetY() + $cellHeightClassDate) > ($this->GetPageHeight() - 100)))) {
                    $this->AddPage();
                    $this->TableHeader($header);
                }
                $classDateCellText = 
                "Class ID: ".$reportRecord['ClassID'].
                "\nCourse Name:\n".$reportRecord['CourseName'].
                "\nClass Date: ".$reportRecord['ClassDate'].
                "\nStart Time: ".$reportRecord['ClassStart'].
                "\nEnd Time: ".$reportRecord['ClassEnd'].
                "\nCanceled: ".($reportRecord['Canceled'] == 0 ? "Canceled: No" : "Canceled: Yes");
                $yPos = $this->GetY();
                $this->SetFont("SourceSansPro-Regular", "", 6);
                $this->SetFillColor(220,220,220);
                $this->MultiCell($CELL_WIDTH + 10, $cellHeightClassDate, $classDateCellText, $BORDER_LEFT.$BORDER_RIGHT.$BORDER_BOTTOM, $ALIGN_CENTER, $FILL);
                $this->SetY($yPos);
                $this->SetFont("SourceSansPro-Regular", "", 8);
                $j=0;
                $shaded = (count($reportRecord["AttendDates"]) == 1) ? !($shaded) : false;
                foreach ($reportRecord["AttendDates"] as $attendDate) {
                    $this->SetX($xPos);
                    foreach ($attendDate as $attendField) {
                        if ($shaded){ $this->SetFIllColor(230,232,245);} else { $this->SetFillColor(255,255,255);}
                        if (!(isset($reportRecord["AttendDates"][$j+1]))) {
                            $this->Cell($CELL_WIDTH, $cellHeight, $attendField, $BORDER_RIGHT.$BORDER_BOTTOM, 0, $ALIGN_CENTER, $FILL);
                        } else {
                            $this->Cell($CELL_WIDTH, $cellHeight, $attendField, $BORDER_RIGHT, 0, $ALIGN_CENTER, $FILL);
                        }
                    }
                    $shaded = !($shaded);
                    $this->Ln();
                    $j++;
                }
                $shaded = !($shaded);
            }
        }
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
    if (!(isset($numIndex))){$numIndex = 0;}

    $record = $statement->fetch($numIndex ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
    return $record;
}

// getAllXDays(date startDate, date endDate, array [string day, [string day...]])
// Returns: an array of strings representing the
// dates of each of the given days ("Monday", "Tuesday", etc...)
// between the given date range in SQL friendly format
function getAllXDays($startDate, $endDate, $daysArray){
    $datesArray = [];
    foreach ($daysArray as $day){
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = new DateInterval('P1D');

        $intervalPeriod = new DatePeriod($start, $interval, $end);

        foreach ($intervalPeriod as $date) {
            if ($date->format('l') === $day) {
                $datesArray[] = $date->format('Y-m-d');
            }
        }
    }
    


    return $datesArray;
}

// generateAccessCode ()
// This function generates a new access code 
// to be used with a new or existing course
// Returns: an array containing a string with the access code
// and the codeID, in that order
function generateAccessCode() {
    $chars = [];
    for($i = 0; $i < 8; $i++){
        $chars[] = mt_rand(0, 1) ? mt_rand(48, 57) : mt_rand(65, 90);
    }
    $codeString = "";
    for ($i = 0; $i < 8; $i++) {
        if ($i == 4) {
            $codeString .= "-";
        }
        $codeString .= chr($chars[$i]);
    }

    return $codeString;
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
    $attendQuery = 
    "SELECT
    e.UserID
	, cd.ClassDate
	, ad.AttendTime
	, c.StartTime AS ClassStart
	, c.EndTime AS ClassEnd
	, cd.ClassID 
    FROM stlcc.ClassDates cd
    JOIN stlcc.Enrollment e ON e.ClassID = cd.ClassID
    JOIN stlcc.Classes c ON c.ClassID = cd.ClassID
    LEFT JOIN 
    (
        SELECT 
        a.UserID
        , CAST(a.AttendanceDateTime AS DATE) AS AttendDate
        , CAST(a.AttendanceDateTime AS TIME) AS AttendTime
        , a.ClassID 
        FROM stlcc.Attendance a
        JOIN stlcc.Classes c ON c.ClassID = a.ClassID
    ) ad ON ad.UserID = e.UserID AND ad.AttendDate = cd.ClassDate AND ad.ClassID = cd.ClassID
    WHERE CONVERT(DATETIME2,CONVERT(VARBINARY(6),c.EndTime)+CONVERT(BINARY(3),cd.ClassDate)) <= CONVERT(DATETIME2,GETDATE()) 
    AND cd.ClassDate > '2023-10-10'
    AND cd.ClassID IN 
    (
        SELECT e.ClassID FROM stlcc.Enrollment e
        GROUP BY e.ClassID
    )
    AND e.UserID IN ( ";
    foreach ($userIDs as $userID){
        $attendQuery .= "'$userID', ";
    }
    $attendQuery = rtrim($attendQuery, ", ")." )";

    $attendStmt = executeQuery($attendQuery);

    $attendRecords = [];
    while ($attendRecord = incrementQuery($attendStmt)){
        array_push($attendRecords, $attendRecord);
    }

    $attended = 0;
    $total = count($attendRecords);
    $late = 0;
    foreach ($attendRecords as $currAttendRecord) {
        if ($currAttendRecord['AttendTime'] !== null) {
            $attended += 1;
            if (strtotime($currAttendRecord['AttendTime']) > (strtotime($currAttendRecord['ClassStart']) + 300)){
                $late+=1;
            }
        }
    }

    return [$total, $attended, $late, count($userIDs)];
}


// TODO: Documentation
function sortUserArray($userArray) {
    usort($userArray, function($a,$b){
        if ($a[3] == $b[3]) {
            return $a[2] > $b[2] ? 1 : -1;
        }
        return $a[3] > $b[3] ? 1 : -1;
    });

    return $userArray;
}

// TODO: Documentation
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

// TODO: Documentation
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

// TODO: Documentation
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

// TODO: Documentation
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

// FIXME: use $userID here instead
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


// FIXME: Deprecated, schema now stores all user info in stlcc.Users
// and therfore userID is now available as a global
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

// TODO: Documentation
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

// Generate Attendance Report
// TODO: Documentation
function generateReport ($reportType, $reportQuery = null, $reportStart = null, $reportEnd = null) {
    global $auth, $userID;
    
    $userArray = [];

    if (!($reportType == "all") && !(isset($reportQuery))) {echo "Search Error"; return;}
    if (!isset($start)){$start = '2023-10-10';}
    if (!isset($end)){$start = date("yyyy-mm-dd");}

    // Validation and authorization
    switch ($reportType) {
        case "student":
            switch ($auth) {
                case "1":
                case "3":
                    $userStmt = executeQuery("SELECT u.UserID 
                    FROM stlcc.Users u 
                    WHERE u.UserTypeID = '4'");
                    $tempUsers = [];
                    while ($tempUser = incrementQuery($userStmt, 1)) {
                        array_push($tempUsers, $tempUser);
                    }
                    if (in_array([$reportQuery], $tempUsers)){
                        $userArray[] = [ $reportQuery ];
                    } else {
                        echo "Unauthorized";
                        return;
                    }
                    break;
                case "2":
                    $userStmt = executeQuery("SELECT e.UserID FROM stlcc.Enrollment e
                    JOIN stlcc.Instruction i ON i.ClassID = e.ClassID
                    WHERE i.UserID = $userID
                    GROUP BY e.UserID");
                    $tempUsers = [];
                    while ($tempUser = incrementQuery($userStmt, 1)) {
                        array_push($tempUsers, $tempUser);
                    }
                    if (in_array([ $reportQuery ], $tempUsers)){
                        $userArray[] = [ $reportQuery ];
                    } else {
                        echo "Unauthorized";
                        return;
                    }
                    break;
                case "4":
                    echo "Unauthorized";
                    return;
            }
            break;
        case "class":
            switch($auth){
                case "1":
                case "2":
                case "3":
                    $userStmt = executeQuery("SELECT UserID FROM stlcc.Enrollment WHERE ClassID = $reportQuery ORDER BY ClassID");
                    while ($userRecord = incrementQuery($userStmt, 1)) {
                        array_push($userArray, $userRecord);
                    }
                    break;
                case "4":
                    $userArray[] = [ $userID ];
            }
            break;
        case "instructor":
            switch ($auth){
                case "1":
                case "3":
                    $userStmt = executeQuery("SELECT e.UserID FROM stlcc.Enrollment e
                    JOIN stlcc.Instruction i ON i.ClassID = e.ClassID
                    WHERE i.UserID = $reportQuery
                    GROUP BY e.UserID");
                    while ($userRecord = incrementQuery($userStmt, 1)) {
                        array_push($userArray, $userRecord);
                    }
                    break;
                case "2":
                    echo "Unauthorized";
                    return;
                case "4":
                    echo "Unauthorized";
                    return;
            }
            break;
        case "all":
            $userArray = getAllStudents();
            break;
    }

    $attendQuery = 
    "SELECT
    e.UserID
	, cd.ClassDate
	, CAST(ad.AttendTime AS TIME(0)) AS AttendTime
	, c.StartTime AS ClassStart
	, c.EndTime AS ClassEnd
	, cd.ClassID 
	, u.FirstName
	, u.LastName
    , co.CourseName
    , cd.Canceled
    FROM stlcc.ClassDates cd
    JOIN stlcc.Enrollment e ON e.ClassID = cd.ClassID
    JOIN stlcc.Classes c ON c.ClassID = cd.ClassID
	LEFT JOIN stlcc.Users u ON u.UserID = e.UserID
    LEFT JOIN stlcc.Courses co ON co.CourseID = c.CourseID
    LEFT JOIN 
    (
        SELECT 
        a.UserID
        , CAST(a.AttendanceDateTime AS DATE) AS AttendDate
        , CAST(a.AttendanceDateTime AS TIME) AS AttendTime
        , a.ClassID 
        FROM stlcc.Attendance a
        JOIN stlcc.Classes c ON c.ClassID = a.ClassID
    ) ad ON ad.UserID = e.UserID AND ad.AttendDate = cd.ClassDate AND ad.ClassID = cd.ClassID
    WHERE CONVERT(DATETIME2,CONVERT(VARBINARY(6),c.EndTime)+CONVERT(BINARY(3),cd.ClassDate)) <= CONVERT(DATETIME2,GETDATE()) 
    AND ClassDate > '$reportStart'
    AND ClassDate < '$reportEnd' ";
    if ($reportType == "class") {
        $attendQuery .= "AND cd.ClassID = $reportQuery ";
    } else if ($reportType == "instructor") {
        $attendQuery .= "AND cd.ClassID IN (SELECT i.ClassID FROM stlcc.Instruction i
        WHERE i.UserID = $reportQuery)";        
    }
    if ($auth == "2"){
        $attendQuery .= "AND cd.ClassID IN (SELECT i.ClassID FROM stlcc.Instruction i
        WHERE i.UserID = $userID)";
    }
    $attendQuery .= "AND e.UserID IN ( ";
    foreach ($userArray as $userID){
        $attendQuery .= "'$userID[0]', ";
    }
    $attendQuery = rtrim($attendQuery, ", ");
    $attendQuery .= " )
    ORDER BY ClassID, ClassDate";

    $attendStmt = executeQuery($attendQuery);

    $attendRecords = [];
    while ($attendRecord = incrementQuery($attendStmt)){
        $attendRecords[] = $attendRecord;
    }

    $attended = 0;
    $total = count($attendRecords);
    $late = 0;

    $reportRecords = [];
    $i = 0;
    foreach ($attendRecords as $currAttendRecord) {
        if ($currAttendRecord['AttendTime'] !== null) {
            $attended += 1;
            if (strtotime($currAttendRecord['AttendTime']) > (strtotime($currAttendRecord['ClassStart']) + 300)){
                $late+=1;
                $studentAttendDates[] = [
                    "UserID"=>$currAttendRecord["UserID"],
                    "LastName"=>$currAttendRecord["LastName"],
                    "FirstName"=>$currAttendRecord["FirstName"],
                    "AttendTime"=>$currAttendRecord["AttendTime"],
                    "Late"=>"Yes"
                ];
            } else {
                $studentAttendDates[] = [
                    "UserID"=>$currAttendRecord["UserID"],
                    "LastName"=>$currAttendRecord["LastName"],
                    "FirstName"=>$currAttendRecord["FirstName"],
                    "AttendTime"=>$currAttendRecord["AttendTime"],
                    "Late"=>"No"];
            }
        } else if ($currAttendRecord["Canceled"] == "1") {
            $studentAttendDates[] = [
                "UserID"=>$currAttendRecord["UserID"],
                "LastName"=>$currAttendRecord["LastName"],
                "FirstName"=>$currAttendRecord["FirstName"],
                "AttendTime"=>"N/A",
                "Late"=>"N/A"];
        } else {
            $studentAttendDates[] = [
                "UserID"=>$currAttendRecord["UserID"],
                "LastName"=>$currAttendRecord["LastName"],
                "FirstName"=>$currAttendRecord["FirstName"],
                "AttendTime"=>"Absent",
                "Late"=>"N/A"];
        }
        if ( !(isset($attendRecords[$i + 1])) ||
            $attendRecords[$i + 1]["ClassID"].$attendRecords[$i + 1]["ClassDate"] != $currAttendRecord["ClassID"].$currAttendRecord["ClassDate"]){
            $reportRecord["ClassID"] = $currAttendRecord["ClassID"];
            $reportRecord["CourseName"] = $currAttendRecord["CourseName"];
            $reportRecord["ClassDate"] = $currAttendRecord["ClassDate"];
            $reportRecord["ClassStart"] = $currAttendRecord["ClassStart"];
            $reportRecord["ClassEnd"] = $currAttendRecord["ClassEnd"];
            $reportRecord["Canceled"] = $currAttendRecord["Canceled"];

            $reportRecord["AttendDates"] = $studentAttendDates;
            $reportRecords[$currAttendRecord["ClassID"].",".$currAttendRecord["ClassDate"]] = $reportRecord;

            unset($studentAttendDates);
            unset($reportRecord);
        }
        $i++;
    }

    return ["TotalAttended"=>$attended, "TotalLates"=>$late, "TotalDates"=>$total, "Records"=>$reportRecords, "StudentCount"=>count($userArray)];
}

function generateReportPDF($reportRecords) {
    global $CELL_WIDTH;

    $header = array(
        "Class Date",
        "Student ID",
        "Last Name",
        "First Name",
        "Time",
        "Late"
    );
    $contentWidth = ($CELL_WIDTH * 6) + 10;
    $pdf = new REPORT_PDF("P", "mm", "Letter");
    $pdf->AliasNbPages();
    $pdf->AddFont("SourceSansPro-Regular", "");
    $pdf->SetMargins(($pdf->GetPageWidth() - $contentWidth) / 2, 15);
    $pdf->AddPage();
    $pdf->CreateAvgTable($reportRecords);
    $pdf->TableHeader($header);
    $pdf->CreatePages($header, $reportRecords);

    echo base64_encode($pdf->Output("S"));
}

function getLatestClasses() {
    global $auth, $userID;
    
    $classQuery = "SELECT c.ClassID, co.CourseName, c.StartTime, c.EndTime, cd.ClassDate FROM stlcc.Classes c
    LEFT JOIN stlcc.ClassDates cd ON cd.ClassID = c.ClassID
    LEFT JOIN stlcc.Courses co ON co.CourseID = c.CourseID
    WHERE cd.ClassDate >= CAST(GETDATE() AS DATE)
    AND cd.ClassDate < DATEADD(day,2,CAST(GETDATE() AS DATE)) 
    AND DATEADD(dd,DATEDIFF(dd,'1900',cd.ClassDate),CONVERT(DATETIME2(0),c.EndTime)) > GETDATE()";

    switch ($auth) {
        case "2":
            $classQuery .= "AND c.ClassID IN (SELECT i.ClassID
            FROM stlcc.Instruction i
            WHERE i.UserID = $userID)";
            break;
        case "4":
            $classQuery .= "AND c.ClassID IN (SELECT e.ClassID 
            FROM stlcc.Enrollment e
            WHERE e.UserID = $userID)";
            break;
    }
    $classStmt = executeQuery($classQuery);
    $classArray = [];
    $i = 0;
    while($classRecord = incrementQuery($classStmt)) {
        array_push($classArray, $classRecord);
    }

    if (count($classArray) > 0) {
        $classArray = array_chunk($classArray, 8)[0];
    } 

    return $classArray;
}

// TODO: Documentation
function submitManageUserChange ($detail, $data, $manageUserID) {
    global $auth;

    switch($auth){
        case "1":
            $columnNames = array("username"=>"Username",
                                 "passwordHash"=>"PasswordHash",
                                 "fName"=>"FirstName",
                                 "lName"=>"LastName",
                                 "email"=>"Email",
                                 "phone"=>"Phone",
                                 "accountDisabled"=>"AccountDisabled");
            if ($detail == "passwordHash"){
                $passwordHash = password_hash($data, PASSWORD_ARGON2I);
                executeQuery("UPDATE stlcc.Users SET PasswordHash = '$passwordHash' WHERE UserID = $manageUserID");
                echo "<p>User Password Updated Successfully!</p>"; 
            } else {
                executeQuery("UPDATE stlcc.Users SET ".($columnNames[$detail])." = '$data' WHERE UserID = $manageUserID");
                echo "<p>User Detail Updated Successfully!</p>";
            }
            break;
        default:
            echo "<h3 style='color: red'>Unauthorized</h3>";
    }
}

// TODO: Documentation
function submitManageXChange ($detail, $data, $detailType, $manageUserID = null, $classID = null, $courseID = null, $date = null) {
    global $auth;
    switch ($auth) {
        case "1":
            switch ($detailType) {
                case "course":
                    if ($detail == "courseName") {
                        $courseStmt = executeQuery("SELECT CourseName FROM stlcc.Courses
                                WHERE CourseID = '$courseID'
                                AND CourseName = '$data'");
                        if (incrementQuery($courseStmt)) {
                            echo "<p>Course name already exists.</p>";
                            break;
                        }
                    }
                    $columnNames = array("courseName"=>"CourseName",
                    "courseDescription"=>"CourseDescription",);
                    $courseStmt = executeQuery("UPDATE stlcc.Courses SET ".($columnNames[$detail])." = '$data' WHERE CourseID = '$courseID'");
                    if ($courseStmt) {
                        echo "<p>Course detail updated successfully!</p>";
                    } else {
                        echo "<p>Database Error</p>";
                        break;
                    }
                    break;
                case "attendance":
                    $attendanceStmt = executeQuery("UPDATE stlcc.Attendance SET AttendanceDateTime = '$date $data'
                                WHERE ClassID = '$classID'
                                AND UserID = '$manageUserID'
                                AND CAST(AttendanceDateTime AS DATE) = '$date'");
                    if ($attendanceStmt) {
                        echo "<p>Attendance time updated successfully!</p>";
                    } else {
                        echo "<p>Database Error</p>";
                        break;
                    }
                    break;
                case "user": // User updates should be moved to this function but I'm out of time
                    $columnNames = array("username"=>"Username",
                                        "passwordHash"=>"PasswordHash",
                                        "fName"=>"FirstName",
                                        "lName"=>"LastName",
                                        "email"=>"Email",
                                        "phone"=>"Phone",
                                        "accountDisabled"=>"AccountDisabled");
                    if ($detail == "passwordHash"){
                        $passwordHash = password_hash($data, PASSWORD_ARGON2I);
                        executeQuery("UPDATE stlcc.Users SET PasswordHash = '$passwordHash' WHERE UserID = $manageUserID");
                        echo "<p>User Password Updated Successfully!</p>"; 
                    } else {
                        executeQuery("UPDATE stlcc.Users SET ".($columnNames[$detail])." = '$data' WHERE UserID = $manageUserID");
                        echo "<p>user detail updated successfully!</p>";
                    }
                    break;
                case "class":
                    executeQuery("UPDATE stlcc.ClassDates
                    SET Canceled = ".($data ? 1 : 0)." 
                    WHERE ClassDate = '$date' AND ClassID = $classID");
                    echo "<p>Class date status updated successfully!</p>";
                    break;
                default:
                    echo "<h3 style='color: red'>Unauthorized</h3>";
                    break;
            }
            break;
    }
}

// TODO: Documentation 
function submitAddUser ($username, $firstName, $lastName, $phone, $email, $userType, $accountDisabled, $password) {
    global $auth;
    
    switch ($auth) {
        case "1":
            $passwordHash = password_hash($password, PASSWORD_ARGON2I);
            $userStmt = executeQuery("SELECT MAX(UserID) AS UserID FROM stlcc.Users");
            $addUserID = (int)(incrementQuery($userStmt)['UserID']) + 1;

            $userStmt = executeQuery("SELECT UserID FROM stlcc.Users WHERE Username = '$username'");
            if (incrementQuery($userStmt)) {
                echo "<p>Username already exists.</p>";
                return;
            }
            if (!(in_array($userType, ["1", "2", "3", "4"]))) {
                echo "<p>Invalid account type.</p>";
                return;
            }
            if (!(in_array($accountDisabled, ["1", "0"]))) {
                echo "<p>Data input error.</p>";
                return;
            }
            if (executeQuery("INSERT INTO stlcc.Users
                              VALUES ('$addUserID', '$username', '$passwordHash', '$firstName', '$lastName', '$phone', '$email', '$userType', '$accountDisabled')")) {
                echo "<p>User created succesfully.</p>";
            } else {
                echo "<p>Database error. Please try again or contact the IT department for assistance.</p>";
            }
            break;
        default:
            break;
    }
}

// TODO: Documentation 
function submitAddClass ($courseID, $addUserID, $startDate, $endDate, $startTime, $endTime, $days) {
    global $auth;
    
    switch ($auth) {
        case "1":
            $daysArray = [64 => "Sunday",
                          32 => "Monday",
                          16 => "Tuesday",
                          8 => "Wednesday",
                          4 => "Thursday",
                          2 => "Friday",
                          1 => "Saturday"];
            $classDaysArray = [];
            foreach([1,2,4,8,16,32,64] as $day){if ($days & $day == $day) {$classDaysArray[] = $daysArray[$day];}}
            $classStmt = executeQuery("SELECT 1 FROM stlcc.Instruction i
            JOIN stlcc.Classes c on c.ClassID = i.ClassID
            WHERE (
                (c.StartTime < '$startTime' AND c.EndTime > '$startTime')
                OR 
                (c.StartTime < '$endTime' AND c.EndTime > '$endTime')
                )
            AND ($days & c.Days) != 0
            AND i.UserID = '$addUserID'");
            if (incrementQuery($classStmt)) {
                echo "<p>There is an existing class for this instructor on one or more selected days between $startTime and $endTime.</p>";
                return;
            }
            $accessCode = generateAccessCode($classDaysArray);
            if ($codeStmt = executeQuery("INSERT INTO stlcc.AccessCodes
                                          OUTPUT INSERTED.CodeID
                                          VALUES ('$accessCode')")) {
                $codeID = incrementQuery($codeStmt)['CodeID'];
                if ($classStmt = executeQuery("INSERT INTO stlcc.Classes 
                                                OUTPUT INSERTED.ClassID
                                                VALUES ('$startDate', '$endDate', '$startTime', '$endTime', $days, '$courseID', '$codeID')")) {
                    $classID = incrementQuery($classStmt)['ClassID'];
                    executeQuery("INSERT INTO stlcc.Instruction VALUES ('$addUserID', '$classID')");
                    $classDatesQuery = "INSERT INTO stlcc.ClassDates
                                        VALUES ";
                    $classDatesArray = getAllXDays($startDate, $endDate, $daysArray);
                    $i=0;
                    foreach ($classDatesArray as $classDate) {
                        if (isset($classDatesArray[$i + 1])) {
                            $classDatesQuery .= "('$classDate', 0, '$classID'), ";
                        } else {
                            $classDatesQuery .= "('$classDate', 0, '$classID');";
                        }
                        $i++;
                    }
                    executeQuery($classDatesQuery);
                    echo "<p>Class created succesfully.</p>";
                } else {
                    echo "<p>Database error. Please try again or contact the IT department for assistance.</p>";
                }
            }

            break;
        default:
            break;
    }
}

// TODO: Documentation 
function submitAddCourse ($courseID, $courseName, $courseDescription) {
    global $auth;
    
    switch ($auth) {
        case "1":
            $userStmt = executeQuery("SELECT CourseID FROM stlcc.Courses 
                                      WHERE CourseID = '$courseID'");
            if (incrementQuery($userStmt)) {
                echo "<p>Course ID already exists.</p>";
                return;
            }
            $userStmt = executeQuery("SELECT CourseName FROM stlcc.Courses 
                                      WHERE CourseName = '$courseName'");
            if (incrementQuery($userStmt)) {
            echo "<p>Course Name already exists.</p>";
            return;
            }
            if (executeQuery("INSERT INTO stlcc.Courses
                              VALUES ('$courseID', '$courseName', '$courseDescription')")) {
                echo "<p>Attendance record created successfully.</p>";
            } else {
                echo "<p>Database error. Please try again or contact the IT department for assistance.</p>";
            }
            break;
        default:
            echo "<p style='color: red'>Unauthorized</p>";
            break;
    }
}

// TODO: Documentation 
function submitAddEnrollment ($classID, $addUserID) {
    global $auth;
    
    switch ($auth) {
        case "1":
            $enrollmentStmt = executeQuery("SELECT 1 FROM stlcc.Enrollment
                                            WHERE UserID = '$addUserID'
                                            AND ClassID = '$classID'");
            if (incrementQuery($enrollmentStmt)) {
                echo "<p>Enrollment already exists.</p>";
                return;
            }
            if (executeQuery("INSERT INTO stlcc.Enrollment VALUES ('$addUserID', '$classID')")) {
                echo "<p>Enrollment added successfully.</p>";
            } else {
                echo "<p>Database error. Please try again or contact the IT department for assistance.</p>";
            }
            break;
        default:
            echo "<p style='color: red'>Unauthorized</p>";
            break;
    }
}

// TODO: Documentation 
function submitAddAttendance ($classID, $userID, $classDate, $attendanceTime) {
    global $auth;
    
    $attendanceTime = date("H:i:s", strtotime($attendanceTime));

    switch ($auth) {
        case "1":
            $attendanceStmt = executeQuery("SELECT c.StartTime, c.EndTime, cd.ClassDate FROM stlcc.ClassDates cd
                                      JOIN stlcc.Classes c ON c.ClassID = cd.ClassID
                                      WHERE cd.ClassID = '$classID'
                                      AND cd.ClassDate = '$classDate'");
            $classRecord = incrementQuery($attendanceStmt);
            if (!$classRecord) {
                echo "<p>There is no class on that day.</p>";
                return;
            }
            if (strtotime("-5 minutes", strtotime($classRecord['StartTime'])) > strtotime($attendanceTime) || strtotime($classRecord['EndTime']) < strtotime($attendanceTime)) {
                echo "<p>Class does not take place at that time.</p>";
                return;
            }
            if ($attendanceDateStmt = incrementQuery(executeQuery("SELECT 1 FROM stlcc.Attendance
                                                WHERE ClassID = '$classID'
                                                AND UserID = '$userID'
                                                AND CAST(AttendanceDateTime AS DATE) = '$classDate'"))) {
                echo "<p>This attendance record already exists.</p>";
                return;
            }
            if (executeQuery("INSERT INTO stlcc.Attendance
                              VALUES ('".$classDate." ".$attendanceTime."', '$userID', '$classID')")) {
                echo "<p>Attendance record created successfully.</p>";
            } else {
                echo "<p>Database error. Please try again or contact the IT department for assistance.</p>";
            }
            break;
        default:
            break;
    }
}


/* This function handles system logouts. The session
 * record is removed from the table based on the username
 * retrieved using the session key.
 */
function logout() {
    global $userID;

    executeQuery("DELETE FROM stlcc.Sessions WHERE UserID = '$userID'");
    unset($_COOKIE['loginStatus']);
    header("Location: ./login.php");
    exit();
}

// TODO: Documentation
function attendClass($classID, $accessCode) {
    global $userID, $auth;

    switch($auth){
        case "4":
            $codeArray = [];
            $codeStmt = executeQuery("SELECT c.ClassID, codes.AccessCode, c.StartTime, c.EndTime from stlcc.Classes c
                                    JOIN stlcc.AccessCodes codes ON c.CodeID = codes.CodeID
                                    WHERE c.ClassID = $classID");
            while($codeRecord = incrementQuery($codeStmt, 1)){
                array_push($codeArray, $codeRecord);
            }
            $classRecord = $codeArray[0];
            $currTimeSql = date('Y-m-d H:i:s');
            $currDateSql = date('Y-m-d');
            if (($accessCode == $classRecord[1]) && (strtotime("-5 minutes", strtotime($classRecord[2])) < strtotime($currTimeSql)) && (strtotime($classRecord[3]) > strtotime($currTimeSql))){
                $attendCheckStmt = executeQuery("SELECT * FROM stlcc.Attendance
                                                 WHERE UserID = '$userID'
                                                 AND ClassID = '$classID'
                                                 AND CAST(AttendanceDateTime AS DATE) = '$currDateSql'");
                if (incrementQuery($attendCheckStmt)) {
                    echo "<p style='color: red'>Error: Class already attended.</p>";
                } else {
                $attendStmt = executeQuery("INSERT INTO stlcc.Attendance
                                            VALUES
                                            ('$currTimeSql', '$userID', '$classID')");
                echo "<p>Attendance submitted.</p>";                                            
                }
            } else {
                echo "<p style='color: red'>Error: Incorrect Access code.</p>";
            }
                
            break;
        default:
            echo "<p style='color: red'>Error: Invalid API call for UserID: $userID, Auth level: $auth</p>";
            break;
    }
}

// TODO: Documentation
function showLatestClasses($classRecords) {
    echo "<ul class='classList'>";
    if (count($classRecords) > 0) {
        foreach($classRecords as $classRecord) {
            echo "<li id='".$classRecord['ClassID'].":".$classRecord['ClassDate']."'>";
            echo $classRecord['ClassID'].": ".$classRecord['CourseName'].", ".$classRecord['StartTime'].", ".$classRecord['ClassDate'];
            echo "</li>";
        }
    } else {
        echo "<li>No upcoming classes to show.</li>";
    }
    echo "</ul>";
}

// TODO: Documentation
function showReportQuery($reportType) {
    global $auth, $userID;

    switch ($reportType) {
        case "all":
            echo "
            <div class='reportControlContainer reportQueryContainer' id='reportQueryDiv'>
                <p>Search Query</p>
                <select class='reportQuery'disabled><option value='0'>Query</option></select>
            </div>";
            break;
        case "student":
            echo "
            <div class='reportControlContainer reportQueryContainer' id='reportQueryDiv'>
                <p>Student Search</p>
                <input class='reportQuery' list='optionList' />
                <datalist id='optionList'>";
                switch ($auth) {
                    case "1":
                    case "3":
                        $userStmt = executeQuery("SELECT e.UserID, u.FirstName, u.LastName FROM stlcc.Enrollment e
                        JOIN stlcc.Users u ON u.UserID = e.UserID
                        GROUP BY e.UserID, u.FirstName, u.LastName");
                        break;
                    case "2":
                        $userStmt = executeQuery("SELECT e.UserID, u.FirstName, u.LastName FROM stlcc.Enrollment e
                        JOIN stlcc.Instruction i ON i.ClassID = e.ClassID
                        JOIN stlcc.Users u ON u.UserID = e.UserID
                        WHERE i.UserID = $userID
                        GROUP BY e.UserID, u.FirstName, u.LastName");
                        break;
                    case "4":
                        echo "Unauthorized";
                        return;
                }
                $tempUsers = [];
                while ($tempUser = incrementQuery($userStmt)) {
                    array_push($tempUsers, $tempUser);
                }
                foreach ($tempUsers as $userRecord) {
                    echo "<option value='".$userRecord['UserID']."'>".$userRecord['UserID'].": ".$userRecord['FirstName']." ".$userRecord['LastName']."</option>";
                }
                echo "</datalist>
            </div>";
            break;
        case "instructor":
            echo "
            <div class='reportControlContainer reportQueryContainer' id='reportQueryDiv''>
                <p>Instructor Search</p>
                <input class='reportQuery' list='optionList' />
                <datalist id='optionList'>";
                switch ($auth) {
                    case "1":
                    case "3":
                        $userStmt = executeQuery("SELECT i.UserID, u.FirstName, u.LastName FROM stlcc.Instruction i
                        JOIN stlcc.Users u ON u.UserID = i.UserID
                        GROUP BY i.UserID, u.FirstName, u.LastName");
                        break;
                    case "2":
                    case "4":
                        echo "Unauthorized";
                        return;
                }
                $tempUsers = [];
                while ($tempUser = incrementQuery($userStmt)) {
                    array_push($tempUsers, $tempUser);
                }
                foreach ($tempUsers as $userRecord) {
                    echo "<option value='".$userRecord['UserID']."'>".$userRecord['UserID'].": ".$userRecord['FirstName']." ".$userRecord['LastName']."</option>";
                }
                echo "</datalist>
            </div>";
            break;
        case "class":
            echo "
            <div class='reportControlContainer reportQueryContainer' id='reportQueryDiv''>
                <p>Class Search</p>
                <input class='reportQuery' list='optionList' />
                <datalist id='optionList'>";
            switch ($auth) {
                case "1":
                case "3":
                    $classStmt = executeQuery("SELECT c.ClassID, co.CourseName
                    FROM stlcc.Classes c
                    JOIN stlcc.Courses co ON co.CourseID = c.CourseID
                    ORDER BY c.ClassID");
                    break;
                case "2":
                    $classStmt = executeQuery("SELECT c.ClassID, co.CourseName
                    FROM stlcc.Classes c
                    JOIN stlcc.Instruction i ON i.ClassID = c.ClassID
                    JOIN stlcc.Courses co ON co.CourseID = c.CourseID
                    WHERE i.UserID = $userID
                    ORDER BY c.ClassID");
                    break;
                case "4":
                    $classStmt = executeQuery("SELECT c.ClassID, co.CourseName
                    FROM stlcc.Classes c
                    JOIN stlcc.Enrollment e ON e.ClassID = c.ClassID
                    JOIN stlcc.Courses co ON co.CourseID = c.CourseID
                    WHERE e.UserID = $userID
                    ORDER BY c.ClassID");
                    break;
            }
            $tempClasses = [];
            while ($tempClass = incrementQuery($classStmt)) {
                array_push($tempClasses, $tempClass);
            }
            foreach ($tempClasses as $classRecord) {
                echo "<option value='".$classRecord['ClassID']."'>".$classRecord['ClassID'].": ".$classRecord['CourseName']."</option>";
            }
            echo "</datalist></div>";
            break;
    }
    return;
}

// TODO: Documentation
function showUserDetail ($detail, $data) {
    switch($detail){
        case "username":
            echo "<div class='manageUserInputContainer'>
                    <input type='text' data-detail='username' placeholder='Username' value='$data'/>
                 </div>";
            break;
        case "fName":
            echo "<div class='manageUserInputContainer'>
                    <input type='text' data-detail='fName' placeholder='First' value='$data'/>
                 </div>";
            break;
        case "lName":
            echo "<div class='manageUserInputContainer'>
                    <input type='text' data-detail='lName' placeholder='Last' value='$data'/>
                 </div>";
            break;
        case "email":
            echo "<div class='manageUserInputContainer'>
                    <input type='text' data-detail='email' placeholder='Email' value='$data'/>
                 </div>";
            break;
        case "phone":
            echo "<div class='manageUserInputContainer'>
                    <input type='text' data-detail='phone' class='phoneArea' size='3' placeholder='555' value='$data'/>
                </div>";
            break;
        case "accountDisabled":
            echo "<div class='manageUserInputContainer'>
                    <input autocomplete='off' type='checkbox' data-detail='accountDisabled' class='accountDisabledArea' id='accountDisabled' ".($data ? "checked": "").">
                    <label class='manageUserDisabledLabel' for='accountDisabled'> Disabled</label></input>
                 </div>";
            break;
        default:
            break;
    }
    return;
}

// TODO: Documentation
function editCourseDetail ($detail, $data) {
    switch($detail){
        case "courseName":
            echo "<div class='manageXInputContainer'>
                    <input type='text' id='courseName' data-detail='courseName' placeholder='Course Name' value='$data'/>
                 </div>";
            break;
        case "courseDescription":
            echo "<div class='manageXInputContainer'>
                    <textarea id='' data-detail='courseDescription' placeholder='Course Description'>$data</textarea>
                 </div>";
            break;
        default:
            break;
    }
    return;
}

// TODO: Documentation
function editAttendanceTime ($data) {
    echo "<div class='manageXInputContainer'>
            <input type='time' id='attendanceTime' data-detail='attendanceTime' value='$data'/>
          </div>";

}

// TODO: Documentation
function updateAccessCode($classID) {
    global $auth;

    switch ($auth) {
        case "1":
            $accessCode = generateAccessCode();
            if ($codeStmt = executeQuery("UPDATE stlcc.AccessCodes
                                          SET AccessCode = '$accessCode'
                                          WHERE CodeID IN 
                                          (
                                            SELECT c.CodeID from stlcc.Classes c
                                            JOIN stlcc.AccessCodes ac ON ac.CodeID = c.CodeID
                                            WHERE c.ClassID = $classID
                                          )")) {
                echo "<p >Access code succssfully updated.</p>";

            }
            break;
        default:
            break;
    }
}

// TODO: Documentation
// Returns HTML code for a report
function showReport ($reportRecords) {
    echo   "<table class='reportTable'>
    <thead>
        <tr>
            <td>Class Date</td>
            <td>Student ID</td>
            <td>Last Name</td>
            <td>First Name</td>
            <td>Time</td>
            <td>Late</td>
        </tr>
    </thead>
    <tbody>";
    foreach($reportRecords["Records"] as $reportRecord) {
        echo "
        <tr class='reportData'>
            <td rowspan='".count($reportRecord["AttendDates"])."' class='reportClassDateCell'>
                <p>".
            "<span class='reportDetail'>Class ID: </span>".$reportRecord["ClassID"]."<br>".
            "<span class='reportDetail'>Course Name: </span>".$reportRecord["CourseName"]."<br>".
            "<span class='reportDetail'>Class Date: </span>".$reportRecord["ClassDate"]."<br>".
            "<span class='reportDetail'>Start Time: </span>".$reportRecord["ClassStart"]."<br>".
            "<span class='reportDetail'>End Time: </span>".$reportRecord["ClassEnd"]."<br>".
            "<span class='reportDetail'>Canceled: </span>".($reportRecord['Canceled'] == 0 ? "Canceled: No" : "Canceled: Yes")."<br>".
                "</p>
            </td>";
            $first = true;
        foreach ($reportRecord["AttendDates"] as $attendDate) {
            if (!$first) {
                echo "<tr>";
                $first = false;
            }
            foreach ($attendDate as $attendField) {
                echo "<td>$attendField</td>";
            }
            echo "</tr>";
        }
    }
    echo "</tbody></table>";
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

    echo "<div class='overlay' onclick='closeWindow()'></div>
        <div id='attendClassWindow' class='window'>
            <div id='attendClassWindowCloseButton' class='windowCloseButton' onclick='closeWindow()'></div>
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

// TODO: Documentation
function changePassword ($oldpassword, $newpassword) {
    global $auth;
    global $userID;

    $userStmt = executeQuery("SELECT PasswordHash FROM stlcc.Users WHERE UserID = $userID");
    $currPwHash = incrementQuery($userStmt, 1)[0];

    if (password_verify($oldpassword, $currPwHash)){
        $newPwHash = password_hash($newpassword, PASSWORD_ARGON2I);
        executeQuery("UPDATE stlcc.Users SET PasswordHash = '$newPwHash' WHERE UserID = $userID");
        echo "<p>Password Successfully Changed</p>";
    } else {
        echo "<p>Current Password Incorrect!</p>";
    }
    return;
}

// TODO: Documentation
function getStudentsByClass ($classID) {
    global $auth;
    switch ($auth) {
        case "1":
            $studentStmt = executeQuery("SELECT e.UserID, u.FirstName, u.LastName FROM stlcc.Enrollment e
            JOIN stlcc.Users u ON u.UserID = e.UserID
            WHERE e.ClassID = $classID
            GROUP BY e.UserID, u.FirstName, u.LastName
            ORDER BY e.UserID");
            
            echo "<option value='none'>Please select a student</option>";        
            while ($studentRecord = incrementQuery($studentStmt)){
                echo "<option value='".$studentRecord['UserID']."'>".$studentRecord['UserID'].": ".$studentRecord['LastName'].", ".$studentRecord['FirstName']."</option>";
            }
            break;
        default:
            return;
    }

}

// TODO: Documentation
function getClassDatesByClass ($classID) {
    global $auth;
    switch ($auth) {
        case "1":
            $classDateStmt = executeQuery("SELECT cd.ClassDate FROM stlcc.ClassDates cd
                                         WHERE cd.ClassID = $classID");
            echo "<option value='none'>Please select a class date</option>";
            while ($classDateRecord = incrementQuery($classDateStmt)){
                echo "<option value='".$classDateRecord['ClassDate']."'>".$classDateRecord['ClassDate']."</option>";
            }
            break;
        default:
            return;
    }
}

// TODO: Documentation
function editClassCanceled ($data) {
    global $auth;
    switch ($auth) {
        case "1":
            echo "<div class='manageXInputContainer'>
                    <input autocomplete='off' data-extra='This One' type='checkbox' data-record='$data' class='classCanceledArea' id='classCanceled' ".($data ? "checked": "")."/>
                    <label class='manageXCanceledLabel' for='classCanceled'> Canceled</label></input>
                  </div>";
            break;
        default:
            return;
    }
}

//TODO: Documentation
function getAccessCode ($classID) {
    global $auth;
    switch ($auth) {
        case "1":
            $accessCodeStmt = executeQuery("SELECT ac.AccessCode FROM stlcc.AccessCodes ac
                                            JOIN stlcc.Classes c ON c.CodeID = ac.CodeID
                                            WHERE c.ClassID = $classID");
            $accessCodeRecord = incrementQuery($accessCodeStmt, 1);
            echo "
                <div class='manageXDetailResultBox' id='accessCode'><p>".$accessCodeRecord[0]."</p></div>";
            break;
        default:
            break;
    }
}

// TODO: Documentation
function getClassCanceled ($classID, $date) {
    global $auth;
    switch ($auth) {
        case "1":
            $classDateStmt = executeQuery("SELECT cd.Canceled FROM stlcc.ClassDates cd
                                         WHERE cd.ClassID = $classID
                                         AND cd.ClassDate = '$date'");
            $classDateRecord = incrementQuery($classDateStmt, 1);
                echo "
                <div class='manageXDetailResultBox' id='classCanceled' data-record='".$classDateRecord[0]."'><p>Canceled: ".($classDateRecord[0] ? "Yes" : "No")."</p></div>
                <div class='manageXEditButton editButtonEdit'></div>";
            break;
        default:
            return;
    }
}

// TODO: Documentation
function getAttendanceDates ($classID, $userID) {
    global $auth;
    switch ($auth) {
        case "1":
            $attendanceStmt = executeQuery("SELECT CAST(AttendanceDateTime AS DATE) AS AttendDate FROM stlcc.Attendance a
                                           WHERE UserID = $userID
                                           AND ClassID = $classID");
            echo "<option value='none'>Please select a class date</option>";
            while ($attendanceRecord = incrementQuery($attendanceStmt)){
                echo "<option value='".$attendanceRecord['AttendDate']."'>".$attendanceRecord['AttendDate']."</option>";
            }
            break;
        default:
            return;
    }
}

// TODO: Documentation
function getCourseDetail($courseID, $detail) {
    $courseStmt = executeQuery("SELECT CourseName, CourseDescription FROM stlcc.Courses
                                WHERE CourseID = $courseID");
    $courseDetails = incrementQuery($courseStmt);
    

    switch ($detail) {
        case "courseName":
            echo "
            <div class='manageXDetailResultBox' id='courseName' data-record='".$courseDetails['CourseName']."'><p>".$courseDetails['CourseName']."</p></div>
            <div class='manageXEditButton editButtonEdit'></div>";
            break;
        case "courseDescription":
            echo "
            <div class='manageXDetailResultBox courseDescriptionBox' id='courseDescription' data-record='".$courseDetails['CourseDescription']."'><p>".$courseDetails['CourseDescription']."</p></div>
            <div class='manageXEditButton editButtonEdit'></div>";
            break;
    }
}

//TODO: Documentation
function getAttendanceTime ($classID, $userID, $date) {
    $attendanceStmt = executeQuery("SELECT CAST(AttendanceDateTime AS TIME(0)) AS AttendTime
                                    FROM stlcc.Attendance
                                    WHERE UserID = '$userID'
                                    AND ClassID = '$classID'
                                    AND CAST(AttendanceDateTime AS DATE) = '$date'");
    
    $attendanceTime = incrementQuery($attendanceStmt)['AttendTime'];

    echo "
    <div class='manageXDetailResultBox' id='attendanceTime' data-record='$attendanceTime'><p>$attendanceTime</p></div>
    <div class='manageXEditButton editButtonEdit'></div>";
}

// getStudentsForEnrollment(string $classID)
// Returns: HTML code for a select list of students
// to be enrolled in a given class. Does not return
// students already enrolled
function getStudentsForEnrollment ($classID) {
    global $auth;

    switch ($auth) {
        case "1":
            $enrollmentStmt = executeQuery("SELECT UserID, FirstName, LastName from stlcc.Users
                                            WHERE UserID NOT IN (
                                                SELECT UserID FROM stlcc.Enrollment
                                                WHERE ClassID = $classID
                                            )
                                            AND UserTypeID = '4'
                                            ORDER BY LastName");
            echo "<option value='none'>Please select a student</option>";
            while ($enrollmentRecord = incrementQuery($enrollmentStmt)){
                echo "<option value='".$enrollmentRecord['UserID']."'>".$enrollmentRecord['UserID'].": ".$enrollmentRecord['LastName'].", ".$enrollmentRecord['FirstName']."</option>";
            }
            break;
        default:
            break;
    }
}

// TODO: Documentation
function manageAttendance () {
    global $auth;

    switch ($auth) {
        case "1":
            $classStmt = executeQuery("SELECT c.ClassID, co.CourseName FROM stlcc.Classes c
            JOIN stlcc.Courses co ON c.CourseID = co.CourseID");

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Edit Course Details</h2></div>";
            echo "
            <fieldset class='manageXFieldset fieldset1'>
                <legend class='manageXHeading'><p>Edit Attendance Time</p></legend>
                <table class='manageXTable userDetails'>
                    <tbody>
                    <tr>
                    <td>
                        <p class='manageXInputTitle'>Class:</p>
                        <div class='manageXInputContainer'>
                            <select id='classSelectBox' data-detail='classID'>
                                <option value='none'>Please select a class</option>";
            while ($classRecord = incrementQuery($classStmt)){
                echo           "<option value='".$classRecord['ClassID']."'>".$classRecord['ClassID'].": ".$classRecord['CourseName']."</option>";
            }
            echo           "</select>
                            </div>
                        </td>
                        <td>Select a class.</td>
                    </tr>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Student:</p>
                            <div class='manageXInputContainer'>
                            <select id='studentSelectBox' data-detail='student'>
                                <option value='none'>Please select a student</option>
                            </select>
                            </div>
                        </td>
                        <td>Select a student.</td>
                    </tr>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Attendance Date:</p>
                            <div class='manageXInputContainer'>
                            <select id='attendanceDateSelectBox' data-detail='attendanceDate'>
                                <option value='none'>Please select an attendance date</option>
                            </select>
                            </div>
                        </td>
                        <td>Select an attendance date.</td>
                    </tr>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Attendance Time:</p>
                            <div class='manageXEditBox'>
                                <div class='manageXDetailResultBox' id='attendanceTime' data-record=''><p>Attendance Time</p></div>
                            </div>
                        </td>
                        <td>Edit the attendance time.</td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='changeXDetailResultBox'></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>";
    }
}

// TODO: Documentation
function manageClasses () {
    global $auth;

    switch ($auth) {
        case "1":
            $classStmt = executeQuery("SELECT c.ClassID, co.CourseName FROM stlcc.Classes c
            JOIN stlcc.Courses co ON c.CourseID = co.CourseID");

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Edit Course Details</h2></div>";
            echo "
            <fieldset class='manageXFieldset fieldset1'>
                <legend class='manageXHeading'><p>Edit Class Details</p></legend>
                <table class='manageXTable userDetails'>
                    <tbody>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Class:</p>
                            <div class='manageXInputContainer'>
                                <select id='classSelectBox' data-detail='classID'>
                                    <option value='none'>Please select a class</option>";
                                    while ($classRecord = incrementQuery($classStmt)){
            echo                    "<option value='".$classRecord['ClassID']."'>".$classRecord['ClassID'].": ".$classRecord['CourseName']."</option>";
                                    }
            echo                "</select>
                            </div>
                        </td>
                        <td>Select a class.</td>
                    </tr>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Access Code:</p>
                            <div class='manageXEditBox'>
                                <div class='manageXInputContainer'>
                                    <div class='manageXDetailResultBox' id='accessCode'><p>Access Code</p></div>
                                </div>
                            </div>
                            <button id='generateAccessCodeButton'>Generate New Code</button>
                        </td>
                        <td>Generate a new access code.</td>
                    </tr>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Class Date:</p>
                            <div class='manageXInputContainer'>
                                <select id='classDateSelectBox'>
                                    <option>Please select a class date</option>
                                </select></br>
                                <div class='manageXEditBox'>
                                    <div class='manageXDetailResultBox' id='classCanceled' data-record=''><p>Canceled Status</p></div>
                                </div>
                            </div>
                        </td>
                        <td>Select a class date.</td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='changeXDetailResultBox'></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>";
    }
}

// TODO: Documentation
function manageCourses () {
    global $auth;

    switch ($auth) {
        case "1":
            $courseStmt = executeQuery("SELECT * FROM stlcc.Courses");

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Edit Course Details</h2></div>";
            echo "
            <fieldset class='manageXFieldset fieldset1'>
                <legend class='manageXHeading'><p>Edit Course Details</p></legend>
                <table class='manageXTable userDetails'>
                    <tbody>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Class:</p>
                            <div class='manageXInputContainer'>
                                <select id='courseSelectBox' data-detail='courseID'>
                                    <option value='none'>Please select a course</option>";
            while ($courseRecord = incrementQuery($courseStmt)){
                echo               "<option value='".$courseRecord['CourseID']."'>".$courseRecord['CourseID'].": ".$courseRecord['CourseName']."</option>";
            }
            echo               "</select>
                            </div>
                        </td>
                        <td>Select a course.</td>
                    </tr>
                    <tr>
                        <td>
                            <p class='manageXInputTitle'>Course Name:</p>
                            <div class='manageXEditBox'>
                                <div class='manageXDetailResultBox' id='courseName' data-record=''><p>Course Name</p></div>
                            </div>
                        </td>
                        <td>Change the Course Name.</td>
                    </tr>
                    <tr>
                        <td class='descriptionCell'>
                            <p class='manageXInputTitle'>Course Description:</p>
                            <div class='manageXEditBox descriptionEditBox'>
                                <div class='manageXDetailResultBox'  id='courseDescription' data-record=''><p>Course Description.</p></div>
                            </div>
                        </td>
                        <td>Change the Course Description.</td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='changeXDetailResultBox'></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>";
    }
}

// TODO: Documentation
function addAttendance () {
    global $auth;
    $classStmt = executeQuery("SELECT c.ClassID, co.CourseName FROM stlcc.Classes c
                               JOIN stlcc.Courses co ON c.CourseID = co.CourseID");

    switch ($auth) {
        case "1":

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Add Attendance</h2></div>";
            echo "
            <fieldset class='addXFieldset'>
                <legend class='addXHeading'><p>Enter Attendance Details</p></legend>
                <table class='addXTable userDetails'>
                    <tbody>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Class:</p>
                                <div class='addXInputContainer'>
                                    <select id='classSelectBox'>
                                        <option value='none'>Please select a class</option>";
            while ($classRecord = incrementQuery($classStmt)){
                echo                    "<option value='".$classRecord['ClassID']."'>".$classRecord['ClassID'].": ".$classRecord['CourseName']."</option>";
            }
            echo                   "</select>
                                </div>
                            </td>
                            <td>Select a class.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Student:</p>
                                <div class='addXInputContainer'>
                                <select id='studentSelectBox'>
                                    <option>Please select a student</option>
                                </select>
                                </div>
                            </td>
                            <td>Select a student.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Class Date:</p>
                                <div class='addXInputContainer'>
                                <select id='classDateSelectBox'>
                                    <option>Please select a class date</option>
                                </select>
                                </div>
                            </td>
                            <td>Select a class date.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Atendance Time:</p>
                                <div class='addXInputTimeContainer'>
                                    <input type='time' id='attendanceTimeField' step='1'/>
                                </div>
                            </td>
                            <td>Enter an attendance time.</td>
                        </tr>
                        <tr class='addXSubmitRow'>
                            <td colspan='2'>
                                <button class='addXSubmitButton'>Submit</button>
                                <div id='addXResultBox'></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>";
    }
}

function addEnrollment () {
    global $auth;

    $classStmt = executeQuery("SELECT c.ClassID, co.CourseName, u.FirstName, u.LastName FROM stlcc.Classes c
                               JOIN stlcc.Courses co ON co.CourseID = c.CourseID
                               JOIN stlcc.Instruction i ON i.ClassID = c.ClassID
                               JOIN stlcc.Users u ON u.UserID = i.UserID");

    switch ($auth) {
        case "1":

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Add Enrollment</h2></div>";
            echo "
            <fieldset class='addXFieldset'>
                <legend class='addXHeading'><p>Enter Enrollment Details</p></legend>
                <table class='addXTable userDetails'>
                    <tbody>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Select Class:</p>
                                <div class='addXInputContainer'>
                                    <select id='classSelectBox'>
                                        <option>Please select a class</option>";
            while ($classRecord = incrementQuery($classStmt)){
                echo                   "<option value='".$classRecord['ClassID']."'>".$classRecord['ClassID'].": ".$classRecord['CourseName']."</option>";
            }
            echo                   "</select>
                                </div>
                            </td>
                            <td>Select a class.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Select Student:</p>
                                <div class='addXInputContainer'>
                                    <select id='studentSelectBox'>
                                        <option>Please select a student</option>
                                    </select>
                                </div>
                            </td>
                            <td>Select a student.</td>
                        </tr>
                        <tr class='addXSubmitRow'>
                            <td colspan='2'>
                                <button class='addXSubmitButton'>Submit</button>
                                <div id='addXResultBox'></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>";
    }
}

function addClass () {
    global $auth;

    $courseStmt = executeQuery("SELECT CourseID, CourseName FROM stlcc.Courses");
    $instructorStmt = executeQuery("SELECT UserID, FirstName, LastName FROM stlcc.Users
                                    WHERE UserTypeID = 2");

    switch ($auth) {
        case "1":

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Add Class</h2></div>";
            echo "
            <fieldset class='addXFieldset'>
                <legend class='addXHeading'><p>Enter Class Details</p></legend>
                <table class='addXTable userDetails'>
                    <tbody>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Select Course:</p>
                                <div class='addXInputContainer'>
                                    <select id='courseSelectBox'>
                                        <option>Please select a course</option>";
            while ($courseRecord = incrementQuery($courseStmt)){
                echo                   "<option value='".$courseRecord['CourseID']."'>".$courseRecord['CourseID'].": ".$courseRecord['CourseName']."</option>";
            }
            echo                   "</select>
                                </div>
                            </td>
                            <td>Enter the course name.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Select Instructor:</p>
                                <div class='manageXInputContainer'>
                                    <select id='instructorSelectBox'>
                                        <option>Please select an instructor</option>";
        while ($instructorRecord = incrementQuery($instructorStmt)){
            echo                        "<option value='".$instructorRecord['UserID']."'>".$instructorRecord['UserID'].": ".$instructorRecord['LastName'].", ".$instructorRecord['FirstName']."</option>";
        }
            echo                   "</select>
                                </div>
                            </td>
                            <td>Enter the course name.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Start/End Date:</p>
                                <div class='addXInputDateContainer'>
                                    <input type='date' id='startDateField'/>
                                    <span>-</span>
                                    <input type='date' id='endDateField'/>
                                </div>
                            </td>
                            <td>Select a start and end date.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Start/End Time:</p>
                                <div class='addXInputTimeContainer'>
                                    <input type='time' id='startTimeField'/>
                                    <span>-</span>
                                    <input type='time' id='endTimeField'/>
                                </div>
                            </td>
                            <td>Select a start and end time.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Days:</p>
                                <div class='addXInputDaysContainer'>
                                    <input class='daysCheckbox' id='sundayCheckbox' type='checkbox' value='sunday'>
                                    <label for='sundayCheckbox'>Sun.</label>
                                    <input class='daysCheckbox' id='mondayCheckbox' type='checkbox' value='monday'>
                                    <label for='mondayCheckbox'>Mon.</label>
                                    <input class='daysCheckbox' id='tuesdayCheckbox' type='checkbox' value='tuesday'>
                                    <label for='tuesdayCheckbox'>Tue.</label>
                                    <input class='daysCheckbox' id='wednesdayCheckbox' type='checkbox' value='wednesday'>
                                    <label for='wednesdayCheckbox'>Wed.</label>
                                    <input class='daysCheckbox' id='thursdayCheckbox' type='checkbox' value='thursday'>
                                    <label for='thursdayCheckbox'>Thu.</label>
                                    <input class='daysCheckbox' id='fridayCheckbox' type='checkbox' value='friday'>
                                    <label for='fridayCheckbox'>Fri.</label>
                                    <input class='daysCheckbox' id='saturdayCheckbox' type='checkbox' value='saturday'>
                                    <label for='saturdayCheckbox'>Sat.</label>
                                </div>
                            </td>
                            <td>Select the days of the week.</td>
                        </tr>
                        <tr class='addXSubmitRow'>
                            <td colspan='2'>
                                <button class='addXSubmitButton'>Submit</button>
                                <div id='addXResultBox'></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>";
    }
}

function addCourse () {
    global $auth;

    switch ($auth) {
        case "1":

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Add Course</h2></div>";
            echo "
            <fieldset class='addXFieldset'>
                <legend class='addXHeading'><p>Enter Course Details</p></legend>
                <table class='addXTable userDetails'>
                    <tbody>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Course ID:</p>
                                <div class='addXInputContainer'>
                                    <input type='text' id='courseIDField' placeholder='Course ID'/>
                                </div>
                            </td>
                            <td>Enter a new course ID.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Course Name:</p>
                                <div class='addXInputContainer'>
                                    <input type='text' id='courseNameField' placeholder='Course Name'/>
                                </div>
                            </td>
                            <td>Enter the course name.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Course Description:</p>
                                <div class='addXInputContainer'>
                                    <textarea class='addXTextArea' id='courseDescriptionField' placeholder='Course Description'></textarea
                                </div>
                            </td>
                            <td>Enter the course description.</td>
                        </tr>
                        <tr class='addXSubmitRow'>
                            <td colspan='2'>
                                <button class='addXSubmitButton'>Submit</button>
                                <div id='addXResultBox'></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>";
    }
}

function addUser () {
    global $auth;

    switch ($auth) {
        case "1":
            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Add User</h2></div>
            <fieldset class='addXFieldset fieldset1'>
                <legend class='addXHeading'><p>Enter User Details</p></legend>
                <table class='addXTable userDetails'>
                    <tbody>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Username:</p>
                                <div class='addXInputContainer'>
                                    <input type='text' id='usernameField' placeholder='Username'/>
                                </div>
                            </td>
                            <td>Enter the account's username.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>First Name:</p>
                                <div class='addXInputContainer'>
                                    <input type='text' id='firstNameField' placeholder='First Name'/>
                                </div>
                            </td>
                            <td>Enter the user's first name.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Last Name:</p>
                                <div class='addXInputContainer'>
                                    <input type='text' id='lastNameField' placeholder='Last Name'/>
                                </div>
                            </td>
                            <td>Enter the user's last name.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Email:</p>
                                <div class='addXInputContainer'>
                                    <input type='text' id='emailField' placeholder='Email'/>
                                </div>
                            </td>
                            <td>Enter the user's email.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Phone:</p>
                                <div class='addXInputContainer'>
                                    <input type='text' id='phoneField' class='phoneArea' size='3' placeholder='1 (555) 123-4567'/>
                                </div
                            </td>
                            <td>Enter the user's phone number.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Account Type</p>
                                <div class='addXInputContainer'>
                                    <select id='userTypeSelectBox'>
                                        <option value='1'>Administrator</option>
                                        <option value='2'>Instructor</option>
                                        <option value='3'>Staff</option>
                                        <option value='4'>Student</option>
                                    </select>
                                </div>
                            </td>
                            <td>Set the user's account type.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Account Status:</p>
                                <div class='addXInputContainer'>
                                    <input autocomplete='off' type='checkbox' id='accountDisabled' class='accountDisabledArea' id='accountDisabled'>
                                    <label class='addXDisabledLabel' for='accountDisabled'> Disabled</label></input>
                                </div>
                            </td>
                            <td>Set the user's account status.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Pasword:</p>
                                <input class='addXPasswordField' type='password' id='userPasswordField' autocomplete='off' placeholder='Enter Password'></input>
                            </td>
                            <td>Enter the user's new password</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='addXInputTitle'>Confirm Pasword:</p>
                                <input class='addXPasswordConfirmField' type='password' id='confirmPasswordField' placeholder='Confirm Password' autocomplete='off' onkeyup='$(this).css(\"background-color\", \"white\")'></input>
                            </td>
                            <td>Confirm the user's new password</td>
                    </tr>
                    <tr class='addXSubmitRow'>
                        <td colspan='2'>
                            <button class='addXSubmitButton'>Submit</button>
                            <div id='addXResultBox'></div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </fieldset>";
    }
}

// TODO: Documentation
function manageUser ($userID) {
    global $auth;

    switch ($auth) {
        case "1":
            $userStmt = executeQuery("SELECT Username, FirstName, LastName, Email, Phone, AccountDisabled FROM stlcc.Users WHERE UserID = $userID");
            $userRecord = incrementQuery($userStmt, 1);

            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Details For User: $userID</h2></div>";
            echo "
            <fieldset class='manageUserDetailsFieldset fieldset1' data-userid='$userID'>
                <legend class='manageUserDetailsHeading'><p>Edit User Details</p></legend>
                <table class='manageUserDetailsTable userDetails'>
                    <tbody>
                        <tr>
                            <td>
                                <p class='manageUserInputTitle'>Username:</p>
                                <div class='manageUserEditBox'>
                                    <div class='manageUserDetailResultBox' id='username' data-record='".$userRecord[0]."'><p>".$userRecord[0]."</p></div>
                                    <div class='manageUserEditButton editButtonEdit'></div>
                                </div>
                            </td>
                            <td>Change the account's username.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='manageUserInputTitle'>First Name:</p>
                                <div class='manageUserEditBox'>
                                    <div class='manageUserDetailResultBox'  id='fName' data-record='".$userRecord[1]."'><p>".$userRecord[1]."</p></div>
                                    <div class='manageUserEditButton editButtonEdit'></div>
                                </div>
                            </td>
                            <td>Change the user's first name.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='manageUserInputTitle'>Last Name:</p>
                                <div class='manageUserEditBox'>
                                    <div class='manageUserDetailResultBox' id='lName' data-record='".$userRecord[2]."'><p>".$userRecord[2]."</p></div>
                                    <div class='manageUserEditButton editButtonEdit'></div>
                                </div>
                            </td>
                            <td>Change the user's last name.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='manageUserInputTitle'>Email:</p>
                                <div class='manageUserEditBox'>
                                    <div class='manageUserDetailResultBox' id='email' data-record='".$userRecord[3]."'><p>".$userRecord[3]."</p></div>
                                    <div class='manageUserEditButton editButtonEdit' onclick=''></div>
                                </div>
                            </td>
                            <td>Change the user's email.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='manageUserInputTitle'>Phone:</p>
                                <div class='manageUserEditBox'>
                                    <div class='manageUserDetailResultBox' id='phone' data-record='".$userRecord[4]."'><p>".$userRecord[4]."</p></div>
                                    <div class='manageUserEditButton editButtonEdit'>
                                </div>
                            </td>
                            <td>Change the user's phone number.</td>
                        </tr>
                        <tr>
                            <td>
                                <p class='manageUserInputTitle'>Account Status:</p>
                                <div class='manageUserEditBox'>
                                    <div class='manageUserDetailResultBox' id='accountDisabled' data-record='".$userRecord[5]."'><p>Account: ".($userRecord[5] ? "Disabled" : "Enabled")."</p></div>
                                    <div class='manageUserEditButton editButtonEdit'></div>
                                </div>
                            </td>
                            <td>Change the user's account status.</td>
                        </tr>
                        <tr>
                            <td colspan='2'>
                                <div class='changeUserDetailResultBox'></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
            <fieldset class='manageUserDetailsFieldset fieldset2'>
                <legend class='manageUserDetailsHeading'><p>Update User Password</p></div></legend>
                <table class='manageUserDetailsTable userPassword'>
                    <tbody>
                        <tr>
                            <td><input class='manageUserPasswordField' type='password' id='newPassword' placeholder='New Password'></input></td>
                            <td>Enter the user's new password</td>
                        </tr>
                        <tr>
                            <td><input class='manageUserPasswordConfirmField' type='password' id='confirmPassword' placeholder='Confirm Password' onkeyup='$(this).css(\"background-color\", \"white\")'></input></td>
                            <td>Confirm the user's new password</td>
                    </tr>
                    <tr class='changePasswordSubmitRow'>
                        <td colspan='2'>
                            <button class='changeUserPasswordSubmitButton'>Update Password</button>
                            <div class='changeUserPasswordResultBox'></div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </fieldset>";
    }
}

// TODO: Documentation
function showChangePassword () {
    global $auth;

    echo "
        <div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Change My Password</h2></div>            
        <fieldset class='changePasswordFieldset'>
            <legend class='changePasswordHeading'><p>Update Password</p></div></legend>
            <table class='changePasswordTable userPassword'>
                <tbody>
                    <tr>
                        <td><input type='password' id='oldPassword' placeholder='Current Password' onkeyup='$(this).css(\"background-color\", \"white\")'></input></td>
                        <td>Enter your current password</td>
                    </tr>
                    <tr>
                        <td><input type='password' id='newPassword' placeholder='New Password' onkeyup='$(this).css(\"background-color\", \"white\")'></input></td>
                        <td>Enter a new password</td>
                    </tr>
                    <tr>
                        <td><input type='password' id='confirmPassword' placeholder='Confirm Password' onkeyup='$(this).css(\"background-color\", \"white\")'></input></td>
                        <td>Confirm the new password</td>
                </tr>
                <tr class='changePasswordSubmitRow'>
                    <td colspan='2'>
                        <button id='changePasswordSubmitButton' onclick='changePassword($(\"#oldPassword\").val(), $(\"#newPassword\").val(), $(\"#confirmPassword\").val())'>Update Password</button>
                        <div id='changePasswordResultBox'></div>
                    </td>
                </tr>
                </tbody>
            </table>
        </fieldset>";
}


// TODO: Documentation
function showAdministration() {
    global $auth, $userID;

    switch ($auth) {
        case "1":
            echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Administration</h2></div>
           <div class='adminNavBox'>
                <div class='adminColumn adminEditColumn'>
                    <div class='adminLinkBox adminEditUsersBox' data-operation='manageUsers'><div class='adminImage editUserImage'></div><p>Edit Users</p></div>
                    <div class='adminLinkBox adminEditClassesBox' data-operation='manageClasses'><div class='adminImage editClassImage'></div><p>Edit Classes</p></div>
                    <div class='adminLinkBox adminEditCoursesBox' data-operation='manageCourses'><div class='adminImage editCourseImage'></div><p>Edit Courses</p></div>
                    <div class='adminLinkBox adminEditAttendanceBox' data-operation='manageAttendance'><div class='adminImage editAttendanceImage'></div><p>Edit Attendance Records</p></div>
                </div>
                <div class='adminColumn adminAddColumn'>
                    <div class='adminLinkBox adminAddUsersBox' data-operation='addUser'><div class='adminImage addUserImage'></div><p>Add User</p></div>
                    <div class='adminLinkBox adminAddClassesBox' data-operation='addClass'><div class='adminImage addClassImage'></div><p>Add Class</p></div>
                    <div class='adminLinkBox adminAddCoursesBox' data-operation='addCourse'><div class='adminImage addCourseImage'></div><p>Add Course</p></div>
                    <div class='adminLinkBox adminAddAttendanceBox' data-operation='addAttendance'><div class='adminImage addAttendanceImage'></div><p>Add Attendance Record</p></div>
                </div>
            </div>
            <div class='adminLinkBox adminAddEnrollmentBox' data-operation='addEnrollment'><div class='adminImage addEnrollmentImage'></div><p>Add Enrollment</p></div>";

            break;
        case "2":
        case "3":
        case "4":
        default:
            echo "<h3 style='color: red'>Unauthorized</h3>";
            break;
        }
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
    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>Classes</h2></div>
    <div class='resultsBox'>";
    switch($auth){
        case "1":
        case "3":
            $classStmt = executeQuery("SELECT c.ClassID, CONCAT(c.CourseID, ': ', co.CourseName) AS Course, concat(u.LastName, ', ', u.FirstName)
            AS Instructor, c.StartDate, c.EndDate, c.StartTime, c.EndTime, ac.AccessCode, c.[Days] FROM stlcc.Classes c
            JOIN stlcc.Instruction i ON i.ClassID = c.ClassID
            JOIN stlcc.Users u ON u.UserID = i.UserID
            JOIN stlcc.AccessCodes ac ON ac.CodeID = c.CodeID
            JOIN stlcc.Courses co ON co.CourseID = c.CourseID");
            echo 
            "<table class='infoTable'>
                <thead>
                    <tr>
                        <td>Class ID</td>
                        <td>Course</td>
                        <td>Instructor</td>
                        <td>Start Date</td>
                        <td>End Date</td>
                        <td>Start Time</td>
                        <td>End Time</td>
                        <td>AccessCode</td>
                        <td>Days<span style='color: red'>*</span></td>
                    </tr>
                </thead>
                    <tbody>";
            break;
        case "2":
            $classStmt = executeQuery("SELECT c.ClassID, c.CourseID, c.StartDate, c.EndDate, c.StartTime, c.EndTime, ac.AccessCode, c.[Days]
            FROM stlcc.Classes c
            JOIN stlcc.Instruction i ON c.ClassID = i.ClassID
            JOIN stlcc.AccessCodes ac ON ac.CodeID = c.CodeID
            WHERE i.UserID = '$userID'");
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
                        <td>Access Code</td>
                        <td>Days<span style='color: red'>*</span></td>
                    </tr>
                </thead>
                    <tbody>";
            break;
        case "4":
            $classStmt = executeQuery("SELECT Classes.ClassID,
            CONCAT(Classes.CourseID, ': ', co.CourseName) AS Course,
            CONCAT(Users.LastName, ', ', Users.FirstName) AS Instructor,
            Classes.StartDate,
            Classes.EndDate,
            Classes.StartTime,
            Classes.EndTime,
            Classes.[Days]
            FROM stlcc.Classes
            JOIN stlcc.Instruction ON Instruction.ClassID = Classes.ClassID
            JOIN stlcc.Courses co ON co.CourseID = Classes.CourseID
            JOIN stlcc.Enrollment ON Enrollment.ClassID = Classes.ClassID
            JOIN stlcc.Users ON Users.UserID = Instruction.UserID
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
                        <td>Days<span style='color: red'>*</span></td>
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
            if (($i == 7 && ($auth == "4" || $auth == "2")) || ((($auth == "1" || $auth == "3") && $i == 8))) {
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
    </table>
    </div>
    <p style='margin: 0; color: rgb(71, 71, 71); text-align: center'><span style='color: red'>*</span> S = Sunday, M = Monday, T = Tuesday, W = Wednesday, TH = Thursday, F = Friday, SA = Saturday</p>";
    return;
}

// TODO: Documentation
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
            echo     "<div class='attendGridItem' id='attendGridItem5'><p class='attendGridItemHeading'>Upcoming/Ongoing Classes</p>
                        <div class='latestClassesBox'>";
            showLatestClasses(getLatestClasses());
            echo "
                        </div>
                    </div>   
                    <div class='attendGridItem' id='attendGridItem6'><p class='attendGridItemHeading'>Report Center</p>
                        <div id='reportCenterBox'>
                            <div id='reportControlBox'>
                                <div class='reportControlContainer'>
                                    <p>Report Type</p>
                                    <select class='reportType'>
                                        <option value='all' selected>All Students</option>
                                        <option value='student'>By Student</option>
                                        <option value='instructor'>By Instructor</option>
                                        <option value='class'>By Class</option>
                                    </select>
                                </div>
                                <div class='reportControlContainer reportQueryContainer' id='reportQueryDiv'>
                                    <p>Search Query</p>
                                    <select class='reportQuery'disabled><option value='0'>Query</option></select>
                                </div>
                                <div class='reportControlContainer'>
                                    <p>Start Date</p>
                                    <input class='reportStart' type='date' value='2023-10-10' min='2023-10-10' max='".date('Y-m-d')."' />
                                </div>
                                <div class='reportControlContainer'>
                                    <p>End Date</p>
                                    <input class='reportEnd' type='date' value='".date('Y-m-d')."' min='2023-10-10' max='".date('Y-m-d')."' />
                                </div>
                                <div class='reportControlContainer reportButtonContainer'>
                                    <p>Generate Report</p>
                                    <div class='reportButtonSubContainer'>
                                        <button class='reportGenerateButton'>Generate</button>
                                        <div class='reportDownloadButton'></div>
                                    </div>
                                </div>
                            </div>
                            <div id='reportBox'>
                            <p class='reportPlaceholderText'>Please click 'Generate' to generate a report.</p>
                            </div>
                        </div>
                    </div>";
            break;
        case "2":
            echo "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'><p class='attendGridItemHeading'>Student Attendance Percentage</p><p class='attendGridItemResult'>".number_format($avgClassesAttendedPerc, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'><p class='attendGridItemHeading'>Student Late Percentage</p><p class='attendGridItemResult'>".number_format($avgLatePercentage, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'><p class='attendGridItemHeading'>Average Absences / Student</p><p class='attendGridItemResult'>".number_format($avgAbsences, 2)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'><p class='attendGridItemHeading'>Average Lates / Student</p><p class='attendGridItemResult'>".number_format($avgLates, 2)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'><p class='attendGridItemHeading'>My Upcoming/Ongoing Classes</p>
            <div class='latestClassesBox'>";
            showLatestClasses(getLatestClasses());
            echo "
            </div>
        </div> 
            <div class='attendGridItem' id='attendGridItem6'><p class='attendGridItemHeading'>Report Center</p>
                <div id='reportCenterBox'>
                    <div id='reportControlBox'>
                        <div class='reportControlContainer'>
                            <p>Report Type</p>
                            <select class='reportType'>
                                <option value='all' selected>All Students</option>
                                <option value='student'>By Student</option>
                                <option value='class'>By Class</option>
                            </select>
                        </div>
                        <div class='reportControlContainer reportQueryContainer' id='reportQueryDiv'>
                            <p>Search Query</p>
                            <select class='reportQuery'disabled><option value='0'>Query</option></select>
                        </div>
                        <div class='reportControlContainer'>
                            <p>Start Date</p>
                            <input class='reportStart' type='date' value='2023-10-10' min='2023-10-10' max='".date('Y-m-d')."' />
                        </div>
                        <div class='reportControlContainer'>
                            <p>End Date</p>
                            <input class='reportEnd' type='date' value='".date('Y-m-d')."' min='2023-10-10' max='".date('Y-m-d')."' />
                        </div>
                        <div class='reportControlContainer reportButtonContainer'>
                            <p>Generate Report</p>
                            <div class='reportButtonSubContainer'>
                                <button class='reportGenerateButton'>Generate</button>
                                <div class='reportDownloadButton'></div>
                            </div>
                        </div>
                    </div>
                    <div id='reportBox'>
                    <p class='reportPlaceholderText'>Please click 'Generate' to generate a report.</p>
                    </div>
                </div>
            </div>";
            break;
        case "4":
            echo     "<div id='attendanceBox'>";
            echo     "<div class='attendGridItem' id='attendGridItem1'><p class='attendGridItemHeading'>My Attendance Percentage</p><p class='attendGridItemResult'>".number_format($avgClassesAttendedPerc, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem2'><p class='attendGridItemHeading'>My Late Percentage</p><p class='attendGridItemResult'>".number_format($avgLatePercentage, 2)."%</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem3'><p class='attendGridItemHeading'>My Absences</p><p class='attendGridItemResult'>".number_format($avgAbsences, 0)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem4'><p class='attendGridItemHeading'>My Lates</p><p class='attendGridItemResult'>".number_format($avgLates, 0)."</p></div>";
            echo     "<div class='attendGridItem' id='attendGridItem5'><p class='attendGridItemHeading'>My Upcoming/Ongoing Classes</p>
                        <div class='latestClassesBox'>";
            showLatestClasses(getLatestClasses());
            echo "
                        </div>
                    </div> 
            <div class='attendGridItem' id='attendGridItem6'><p class='attendGridItemHeading'>Report Center</p>
                <div id='reportCenterBox'>
                    <div id='reportControlBox'>
                        <div class='reportControlContainer'>
                            <p>Report Type</p>
                            <select class='reportType'>
                                <option value='all' selected>All Classes</option>
                                <option value='class'>By Class</option>
                            </select>
                        </div>
                        <div class='reportControlContainer reportQueryContainer' id='reportQueryDiv'>
                            <p>Search Query</p>
                            <select class='reportQuery'disabled><option value='0'>Query</option></select>
                        </div>
                        <div class='reportControlContainer'>
                            <p>Start Date</p>
                            <input class='reportStart' type='date' value='2023-10-10' min='2023-10-10' max='".date('Y-m-d')."' />
                        </div>
                        <div class='reportControlContainer'>
                            <p>End Date</p>
                            <input class='reportEnd' type='date' value='".date('Y-m-d')."' min='2023-10-10' max='".date('Y-m-d')."' />
                        </div>
                        <div class='reportControlContainer reportButtonContainer'>
                            <p>Generate Report</p>
                            <div class='reportButtonSubContainer'>
                                <button class='reportGenerateButton'>Generate</button>
                                <div class='reportDownloadButton'></div>
                            </div>
                        </div>
                    </div>
                    <div id='reportBox'>
                    <p class='reportPlaceholderText'>Please click 'Generate' to generate a report.</p>
                    </div>
                </div>
            </div>";
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

    $userTypes = [
        "1"=>"Administrator",
        "2"=>"Instructor",
        "3"=>"Staff",
        "4"=>"Student",
    ];

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>".$userTypes[$auth]." Dashboard</h2></div>
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

// TODO: Documentation
function showUserPage() {
    global $auth;
    global $userID;

    $userStmt = executeQuery("SELECT UserID, Username, FirstName, LastName, Email, Phone, UserTypeID FROM stlcc.Users WHERE UserID = $userID");
    $userRecord = incrementQuery($userStmt, 1);

    $userTypes = [
        "1" => "Administrator",
        "2" => "Instructor",
        "3" => "Staff",
        "4" => "Student"
    ];

    echo "<div class='contentBoxHeadingContainer'><h2 class='contentBoxHeading'>My User Details</h2></div>";
    echo "
    <fieldset class='userDetailsFieldset'>
    <legend>My Details</legend>
        <table class='userDetails'>
            <tbody>
                <tr>
                    <td>
                        <div class='userDetailContainer'>
                            <div class='userDetailBox'><p>".$userRecord[0]."</p></div>
                        </div>
                    </td>
                    <td>My User ID.</td>
                </tr>
                <tr>
                    <td>
                        <div class='userDetailContainer'>
                            <div class='userDetailBox'><p>".$userRecord[1]."</p></div>
                        </div>
                    </td>
                    <td>My Username.</td>
                </tr>
                <tr>
                    <td>
                        <div class='userDetailContainer'>
                            <div class='userDetailBox'><p>".$userRecord[2]."</p></div>
                        </div>
                    </td>
                    <td>My First Name.</td>
                </tr>
                <tr>
                    <td>
                        <div class='userDetailContainer'>
                            <div class='userDetailBox'><p>".$userRecord[3]."</p></div>
                        </div>
                    </td>
                    <td>My Last Name.</td>
                </tr>
                <tr>
                    <td>
                        <div class='userDetailContainer'>
                            <div class='userDetailBox'><p>".$userRecord[4]."</p></div>
                        </div>
                    </td>
                    <td>My Email.</td>
                </tr>
                <tr>
                    <td>
                        <div class='userDetailContainer'>
                            <div class='userDetailBox'><p>".$userRecord[5]."</p></div>
                        </div>
                    </td>
                    <td>My Phone.</td>
                </tr>
                <tr>
                    <td>
                        <div class='userDetailContainer'>
                            <div class='userDetailBox'><p>".$userTypes[$userRecord[6]]."</p></div>
                        </div>
                    </td>
                    <td>My Account Type.</td>
                </tr>
                <tr>
                    <td colspan='2'>
                        <button id='changePasswordButton'>Change My Password</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>";
}


// TODO: Documentation
function main() {
    #if (isset($_POST['operation'])){$operation = $_POST['operation'];} else {$operation = "none";}
    $operation = isset($_POST['operation']) ? $_POST['operation'] : "none";
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
        case "administration";
            showAdministration();
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
        case "manageCourses":
            manageCourses();
            break;
        case "manageClasses":
            manageClasses();
            break;
        case "manageAttendance":
            manageAttendance();
            break;
        case "showUserDetail":
            if (isset($_POST['detail'])){$detail = $_POST['detail'];} else {break;}
            if (isset($_POST['data'])){$data = $_POST['data'];} else {break;}
            showUserDetail($detail, $data);
            break;
        case "editClassDetail":
            if (isset($_POST['detail'])){$detail = $_POST['detail'];} else {break;}
            if (isset($_POST['data'])){$data = $_POST['data'];} else {break;}
            editClassDetail($classID, $detail);
            break;
        case "getAttendanceDates":
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {break;}
            if (isset($_POST['editUserID'])){$editUserID = $_POST['editUserID'];} else {break;}
            getAttendanceDates($classID, $editUserID);
            break;
        case "getAttendanceTime":
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {break;}
            if (isset($_POST['editUserID'])){$editUserID = $_POST['editUserID'];} else {break;}
            if (isset($_POST['date'])){$date = $_POST['date'];} else {break;}
            getAttendanceTime($classID, $editUserID, $date);
            break;
        case "showChangePassword":
            showChangePassword();
            break;
        case "changePassword":
            if (isset($_POST['oldpw'])){$oldpw = $_POST['oldpw'];} else {break;}
            if (isset($_POST['newpw'])){$newpw = $_POST['newpw'];} else {break;}
            changePassword($oldpw, $newpw);
            break;
        case "submitManageUserChange":
            if (isset($_POST['detail'])){$detail = $_POST['detail'];} else {break;}
            if (isset($_POST['data'])){$data = $_POST['data'];} else {break;}
            if (isset($_POST['manageUserID'])){$manageUserID = $_POST['manageUserID'];} else {break;}
            submitManageUserChange($detail, $data, $manageUserID);
            break;
        case "submitManageXChange":
            if (isset($_POST['detail'])){$detail = $_POST['detail'];} else {break;}
            if (isset($_POST['data'])){$data = $_POST['data'];} else {break;}
            if (isset($_POST['detailType'])){$detailType = $_POST['detailType'];} else {break;}
            if (isset($_POST['manageUserID'])){$manageUserID = $_POST['manageUserID'];} else {$manageUserID = null;}
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {$classID = null;}
            if (isset($_POST['courseID'])){$courseID = $_POST['courseID'];} else {$courseID = null;}
            if (isset($_POST['date'])){$date = $_POST['date'];} else {$date = null;}
            submitManageXChange($detail, $data, $detailType, $manageUserID, $classID, $courseID, $date);
            break;
        case "updateAccessCode":
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {$classID = null;}
            updateAccessCode($classID);
            break;
        case "showReport":
            if (isset($_POST['reportType'])){$reportType = $_POST['reportType'];} else {break;}
            if (isset($_POST['reportQuery'])){$reportQuery = $_POST['reportQuery'];} else {$reportQuery = null;}
            if (isset($_POST['start'])){$start = $_POST['start'];} else {$start = null;}
            if (isset($_POST['stop'])){$stop = $_POST['stop'];} else {$stop = null;}
            showReport(generateReport($reportType, $reportQuery, $start, $stop));
            break;
        case "showReportQuery":
            if (isset($_POST['reportType'])){$reportType = $_POST['reportType'];} else {break;}
            showReportQuery($reportType);
            break;
        case "downloadReport":
            if (isset($_POST['reportType'])){$reportType = $_POST['reportType'];} else {break;}
            if (isset($_POST['reportQuery'])){$reportQuery = $_POST['reportQuery'];} else {$reportQuery = null;}
            if (isset($_POST['start'])){$start = $_POST['start'];} else {$start = null;}
            if (isset($_POST['stop'])){$stop = $_POST['stop'];} else {$stop = null;}
            generateReportPDF(generateReport($reportType, $reportQuery, $start, $stop));
            break;
        case "addUser":
            addUser();
            break;
        case "addCourse":
            addCourse();
            break;
        case "addClass":
            addClass();
            break;
        case "addEnrollment":
            addEnrollment();
            break;
        case "submitAddUser":
            if (isset($_POST['username']) || $_POST['username'] != ""){$username = $_POST['username'];} else {break;}
            if (isset($_POST['firstName']) || $_POST['firstName'] != ""){$firstName = $_POST['firstName'];} else {break;}
            if (isset($_POST['lastName']) || $_POST['lastName'] != ""){$lastName = $_POST['lastName'];} else {break;}
            if (isset($_POST['phone']) || $_POST['phone'] != ""){$phone = $_POST['phone'];} else {break;}
            if (isset($_POST['email']) || $_POST['email'] != ""){$email = $_POST['email'];} else {break;}
            if (isset($_POST['userType']) || $_POST['userType'] != ""){$userType = $_POST['userType'];} else {break;}
            if (isset($_POST['accountDisabled']) || $_POST['accountDisabled'] != ""){$accountDisabled = $_POST['accountDisabled'];} else {break;}
            if (isset($_POST['password']) || $_POST['password'] != ""){$password = $_POST['password'];} else {break;}
            submitAddUser($username, $firstName, $lastName, $phone, $email, $userType, $accountDisabled, $password);
            break;
        case "submitAddClass":
            if (isset($_POST['coourseID']) || $_POST['courseID'] != ""){$courseID = $_POST['courseID'];} else {break;}
            if (isset($_POST['userID']) || $_POST['userID'] != ""){$addUserID = $_POST['userID'];} else {break;}
            if (isset($_POST['startDate']) || $_POST['startDate'] != ""){$startDate = $_POST['startDate'];} else {break;}
            if (isset($_POST['endDate']) || $_POST['endDate'] != ""){$endDate = $_POST['endDate'];} else {break;}
            if (isset($_POST['startTime']) || $_POST['startTime'] != ""){$startTime = $_POST['startTime'];} else {break;}
            if (isset($_POST['endTime']) || $_POST['endTime'] != ""){$endTime = $_POST['endTime'];} else {break;}
            if (isset($_POST['days']) || $_POST['days'] != ""){$days = $_POST['days'];} else {break;}
            submitAddClass($courseID, $addUserID, $startDate, $endDate, $startTime, $endTime, $days);
            break;
        case "submitAddCourse":
            if (isset($_POST['courseID']) || $_POST['courseID'] != ""){$courseID = $_POST['courseID'];} else {break;}
            if (isset($_POST['courseName']) || $_POST['courseName'] != ""){$courseName = $_POST['courseName'];} else {break;}
            if (isset($_POST['courseDescription']) || $_POST['courseDescription'] != ""){$courseDescription = $_POST['courseDescription'];} else {break;}
            submitAddCourse($courseID, $courseName, $courseDescription);
            break;
        case "submitAddEnrollment":
            if (isset($_POST['classID']) || $_POST['classID'] != ""){$classID = $_POST['classID'];} else {break;}
            if (isset($_POST['userID']) || $_POST['userserID'] != ""){$addUserID = $_POST['userID'];} else {break;}
            submitAddEnrollment($classID, $addUserID);
            break;
        case "submitAddAttendance":
            if (isset($_POST['classID']) || $_POST['classID'] != ""){$classID = $_POST['classID'];} else {break;}
            if (isset($_POST['userID']) || $_POST['userserID'] != ""){$addUserID = $_POST['userID'];} else {break;}
            if (isset($_POST['classDate']) || $_POST['classDate'] != ""){$classDate = $_POST['classDate'];} else {break;}
            if (isset($_POST['attendanceTime']) || $_POST['attendanceTime'] != ""){$attendanceTime = $_POST['attendanceTime'];} else {break;}
            submitAddAttendance($classID, $addUserID, $classDate, $attendanceTime);
            break;
        case "addAttendance":
            addAttendance();
            break;
        case "getStudentsByClass":
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {break;}
            getStudentsByClass($classID);
            break;
        case "getStudentsForEnrollment":
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {break;}
            getStudentsForEnrollment($classID);
            break;
        case "getClassDatesByClass":
            if (isset($_POST['classID'])){$classID = $_POST['classID'];} else {break;}
            getClassDatesByClass($classID);
            break;
        case "getClassCanceled":
            if (isset($_POST['classID']) || $_POST['classID'] != ""){$classID = $_POST['classID'];} else {break;}
            if (isset($_POST['date']) || $_POST['date'] != ""){$date = $_POST['date'];} else {break;}
            getClassCanceled($classID, $date);
            break;
        case "editClassCanceled":
            if (isset($_POST['data']) || $_POST['data'] != ""){$data = $_POST['data'];} else {break;}
            editClassCanceled($data);
            break;
        case "getAccessCode":
            if (isset($_POST['classID']) || $_POST['classID'] != ""){$classID = $_POST['classID'];} else {break;}
            getAccessCode($classID);
            break;
        case "getCourseDetail":
            if (isset($_POST['detail'])){$detail = $_POST['detail'];} else {break;}
            if (isset($_POST['courseID'])){$courseID = $_POST['courseID'];} else {break;}
            getCourseDetail($courseID, $detail);
            break;
        case "editCourseDetail":
            if (isset($_POST['detail'])){$detail = $_POST['detail'];} else {break;}
            if (isset($_POST['data'])){$data = $_POST['data'];} else {break;}
            editCourseDetail($detail, $data);
            break;
        case "editAttendanceTime":
            if (isset($_POST['data'])){$data = $_POST['data'];} else {break;}
            editAttendanceTime($data);
            break;
        default:
            echo "<p>".((!(strcmp($operation, "none"))) ?  "No operation provided." : "Unknown operation: '$operation'" )."</p>";
            break;
    } 

    return 0;
}

main();