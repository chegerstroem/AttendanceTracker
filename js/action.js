/* 
 * Christian H - All code
 */

/* operation(string option)
 * 
 * This function is responsible for calling api.php and
 * returning the results asynchronously. The callback 
 * function handles expired sessions by redirecting users
 * to the login page.
 */
function operation (option, popstate = false) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: option},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                $.redirect('login.php');
            } else {
                if (option == "showAttendWindow"){
                    document.body.insertAdjacentHTML("afterbegin", result);
                    return;
                } else {
                    if (!popstate) {
                        state = {function: "operation", option: option};
                        let urlOption = option[0].toUpperCase() + (option.slice(1));
                        history.pushState(state, "", "#"+urlOption+"Page");
                    }
                }
                // Update contentBox with the results of the function
                document.getElementById("contentBox").innerHTML = result;
                if (option == "administration"){
                    $('.adminLinkBox').on("click", function () {
                        operation($(this).data('operation'));
                    });
                }
                if (option == "manageUsers") {
                    $(".userMgmtBox > table > tbody > tr").on('click', function(){manageUser(this.dataset.userid);});
                }
                if (option == "attendance") {
                    $(".reportGenerateButton").on("click", function () {
                        showReport($('.reportType').val(), $('.reportQuery').val(), $('.reportStart').val(), $('.reportEnd').val());
                    });
                    $(".reportDownloadButton").on("click", function () {
                        downloadReport($('.reportType').val(), $('.reportQuery').val(), $('.reportStart').val(), $('.reportEnd').val());
                    });
                    $(".reportType").on("change", function () {
                        showReportQuery(this.value);
                    });
                }
                if (option == "user") {
                    $('#changePasswordButton').off('click').on('click', function (event) {
                        event.preventDefault()
                        operation("showChangePassword");
                    })
                }
                if (option == "addAttendance"){
                    $('#classSelectBox').on("change", function () {
                        if (!(this.value === "none")) {
                            getStudentsByClass(this.value);
                            getClassDatesByClass(this.value);
                        }
                    });
                    $(".addXSubmitButton").on("click", function () {
                        submitAddX( 
                            "Attendance", 
                            {operation: "submitAddAttendance",
                            classID: $("#classSelectBox").val(),
                            userID: $("#studentSelectBox").val(),
                            classDate: $("#classDateSelectBox").val(),
                            attendanceTime: $("#attendanceTimeField").val()}
                            );
                        }
                    );
                }
                if (option == "addUser"){
                    $(".addXSubmitButton").on("click", function () {
                        submitAddX( 
                            "user", 
                            {operation: "submitAddUser",
                            username: $("#usernameField").val(),
                            firstName: $("#firstNameField").val(),
                            lastName: $("#lastNameField").val(),
                            phone: $("#phoneField").val(),
                            email: $("#emailField").val(),
                            userType: $("#userTypeSelectBox").val(),
                            accountDisabled: $("#accountDisabled").val(),
                            password: $("#userPasswordField").val(),
                            confirmPassword: $("#confirmPasswordField").val()}
                            );
                        }
                    );
                }
                if (option == "addCourse") {
                    $(".addXSubmitButton").on("click", function () {
                        submitAddX( 
                            "course", 
                            {operation: "submitAddCourse",
                            courseID: $("#courseIDField").val(),
                            courseName: $("#courseNameField").val(),
                            courseDescription: $("#courseDescriptionField").val()}
                            );
                        }
                    );
                }
                if (option == "addClass") {
                    $(".addXSubmitButton").on("click", function () {
                        daysArray = [];
                        $(".daysCheckbox:checked").each(function (day) {
                            daysArray.push(this.value);
                        });
                        submitAddX( 
                            "class", 
                            {operation: "submitAddClass",
                            courseID: $("#courseSelectBox").val(),
                            userID: $("#instructorSelectBox").val(),
                            startDate: $("#startDateField").val(),
                            endDate: $("#endDateField").val(),
                            startTime: $("#startTimeField").val() + ":00",
                            endTime: $("#endTimeField").val() + ":00",
                            days: daysArray}
                            );
                        }
                    );
                }
                if (option == "manageAttendance"){
                    $('#classSelectBox').on("change", function () {
                        if (!(this.value === "none")) {
                            getStudentsByClass(this.value);
                        }
                    });
                    $('#studentSelectBox').on("change", function () {
                        if (!(this.value === "none")) {
                            getAttendanceDates( $('#classSelectBox').val(), this.value);
                        }
                    });
                }
                if (option == "manageCourses") {
                    $('#courseSelectBox').on("change", function () {
                        if (!(this.value === "none")) {
                            getCourseDetail(this.value, "courseName");
                            getCourseDetail(this.value, "courseDescription");
                        }
                    });
                }
                if (option == "manageClasses") {
                    $('#classSelectBox').off("change").on("change", function () {
                        if (this.value !== "none") {
                            getAccessCode(this.value);
                            getClassDatesByClass(this.value);
                            $('#classDateSelectBox').off("change").on("change", function () {
                                if (this.value !== "none") {
                                    getClassCanceled($('#classSelectBox').val(), this.value);
                                }
                            });
                            $('#generateAccessCodeButton').off("click").on("click", function () {
                                updateAccessCode($('#classSelectBox').val());
                            });
                        }
                    });
                }
                if (option == "addEnrollment") {
                    $("#classSelectBox").off("change").on("change", function () {
                        getStudentsForEnrollment($(this).val());
                    });
                }
                return;
            }
        }
    });
}

function showReportQuery (reportType = null) {
    if (reportType === null) {reportQuery == "all";}
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "showReportQuery",
               reportType: reportType},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("reportQueryDiv").outerHTML = result;
                $(".reportQuery").on("keyup", function () {
                    $(this).css("background-color", "white");
                });
                return;
            }
        }
    });
}

function showReport (reportType, reportQuery = null, start = null, stop = null) {
    if (reportQuery == "0") {reportQuery == null}
    if (reportType != "all"){
        var listItem = $("#optionList").find("option[value='" + reportQuery + "']");
        if (listItem == null || listItem.length == 0) {
            $(".reportQuery").css("background-color", "#FFCCCB");
            // TODO: Proper error handling
            return;
        }
    }
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "showReport",
        reportType: reportType,
        reportQuery: reportQuery,
        start: start,
        stop: stop},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("reportBox").innerHTML = result;
                return;
            }
        }
    });
}

function downloadReport (reportType, reportQuery = null, start = null, stop = null) {
    if (reportQuery == "0") {reportQuery == null}
    if (reportType != "all"){
        var listItem = $("#optionList").find("option[value='" + reportQuery + "']");
        if (listItem == null || listItem.length == 0) {
            $(".reportQuery").css("background-color", "#FFCCCB");
            // TODO: Proper error handling
            return;
        }
    }
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: "text",
        data: {operation: "downloadReport",
        reportType: reportType,
        reportQuery: reportQuery,
        start: start,
        stop: stop},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                var currTime = (new Date().toLocaleString("en-US", {hour12: false})).replace(/\/|:/g, "-").replace(", ", "_");

                // https://stackoverflow.com/questions/16245767/creating-a-blob-from-a-base64-string-in-javascript
                byteCharacters = atob(result);
                byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                byteArray = new Uint8Array(byteNumbers);
                //
                
                blob = new Blob([byteArray], {type: "application/pdf"});
                var url = URL.createObjectURL(blob);
                var download = document.createElement('a');
                download.href = url;
                download.download = 'STLCC_Attendance_Report_' + reportType + "_" + currTime + ".pdf";
                download.click();
                delete download;
            }
        }
    });
}

function usersSearch (search, role){
    if (typeof role == 'undefined' || role == "") {
        role = "";
        $(".searchRoleOptions > input:checked").each((index, element) => {
            role += element.value;
        });
    }
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: { operation: "userSearch",
                search: search,
                role: role},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                // Update userMgmtBox with the results of the function
                document.getElementById("userSearchResultsBox").innerHTML = result;
                $(".userMgmtBox > table > tbody > tr").on('click', function(){manageUser(this.dataset.userid)});
            }
        }
    });
}

function attendClass (classID, accessCode){
    document.getElementById("attendWindowMessageBox").innerHTML = ""
    if($("#classChoice").val() == "D"){
        elem = document.getElementById("attendWindowMessageBox").appendChild(document.createElement("p"));
        elem.style.color = "red";
        elem.textContent = "Error: No Class Selected.";
        return;
    }
    if ($("#accessCode").val() == ""){
        elem = document.getElementById("attendWindowMessageBox").appendChild(document.createElement("p"));
        elem.style.color = "red";
        elem.textContent = "Error: No Code Entered.";
        return;
    }

    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "attendClass",
               classID: classID,
               accessCode: accessCode},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            }
            document.getElementById("attendWindowMessageBox").innerHTML = result;
            return;
        }
    });
}

function manageUser (userID) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "manageUser",
               userID: userID},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("contentBox").innerHTML = result;
                $('.manageUserEditButton').click( function () {showUserDetailField(this, $(this).siblings('.manageUserDetailResultBox').attr('id') ,$(this).siblings('.manageUserDetailResultBox').attr('data-record'), userID)});
                $('.changeUserPasswordSubmitButton').click( function () {submitManageUserPassword($('.manageUserPasswordField').val(), $('.manageUserPasswordConfirmField').val(), userID)});
                return;
            }
        }
    });
}

function getClassDatesByClass (classID) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getClassDatesByClass",
               classID: classID},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("classDateSelectBox").innerHTML = result;
                return;
            }
        }
    });
}

function getStudentsByClass (classID) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getStudentsByClass",
               classID: classID},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("studentSelectBox").innerHTML = result;
                return;
            }
        }
    });
}

function getStudentsForEnrollment (classID) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getStudentsForEnrollment",
               classID: classID},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("studentSelectBox").innerHTML = result;
                $(".addXSubmitButton").on("click", function () {
                    submitAddX( 
                        "course", 
                        {operation: "submitAddEnrollment",
                        classID: $("#classSelectBox").val(),
                        userID: $("#studentSelectBox").val()}
                        );
                    }
                );
                return;
            }
        }
    });
}

function getAccessCode (classID) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getAccessCode",
               classID: classID},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("accessCode").outerHTML = result;
                return;
            }
        }
    });
}

function getAttendanceDates (classID, userID) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getAttendanceDates",
               classID: classID,
               editUserID: userID},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("attendanceDateSelectBox").innerHTML = result;
                $("#attendanceDateSelectBox").off("change").on("change", function () {
                    getAttendanceTime($('#classSelectBox').val(), $('#studentSelectBox').val(), this.value);
                });
                return;
            }
        }
    });
}

function getAttendanceTime (classID, userID, date) {
    $('.editButtonEdit').remove();
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getAttendanceTime",
               classID: classID,
               editUserID: userID,
               date: date},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("attendanceTime").outerHTML = result;
                $('.editButtonEdit').off("click").on("click", function () {editAttendanceTime(this, $(this).siblings('.manageXDetailResultBox').attr('id') ,$(this).siblings('.manageXDetailResultBox').attr('data-record'))});
                return;
            }
        }
    });
}

function getCourseDetail (courseID, detail) {
    $('.editButtonEdit').remove();
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getCourseDetail",
               courseID: courseID,
               detail: detail},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById(detail).outerHTML = result;
                $('.editButtonEdit').off("click").on("click", function () {editCourseDetail(this, $(this).siblings('.manageXDetailResultBox').attr('id') ,$(this).siblings('.manageXDetailResultBox').attr('data-record'))});
                return;
            }
        }
    });
}

function getClassCanceled (classID, date) {
    $('.editButtonEdit').remove();
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "getClassCanceled",
               classID: classID,
               date: date},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("classCanceled").outerHTML = result;
                $('.editButtonEdit').off("click").on("click", function () {editClassCanceled(this, $(this).siblings('.manageXDetailResultBox').attr('id'), $(this).siblings('.manageXDetailResultBox').attr('data-record'))});
                return;
            }
        }
    });
}

function editAttendanceTime (button, detail, data, detailType) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "editAttendanceTime",
                data: data},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById(detail).outerHTML = result;
                $(button).off('click').on('click', function () {cancelAttendanceEdit(this, detail, data);});
                $(button).addClass('editButtonClose').removeClass('editButtonEdit');
                newButton = document.createElement('div');
                $(newButton).off("click").on('click', function () {showManageXSubmitWindow(button, detail, data, $(button).siblings('.manageXInputContainer').children('input').val(), "attendance", 
                $('#studentSelectBox').val(), $('#classSelectBox').val(), null, $('#attendanceDateSelectBox').val())});
                $(button).parent().css('padding-right', '0');
                $(newButton).addClass('manageXConfirmButton').insertAfter(button);
                return;
            }
        }
    });
}

function editClassCanceled (button, detail, data) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "editClassCanceled",
                data: data},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("classCanceled").outerHTML = result;
                ;
                $(button).off('click').on('click', function () {cancelClassEdit(this, detail, data);});
                $(button).addClass('editButtonClose').removeClass('editButtonEdit');
                newButton = document.createElement('div');
                $(newButton).off("click").on('click', function () {showManageXSubmitWindow(button, detail, data, $(button).siblings('.manageXInputContainer').children('input').is(":checked") ? 1 : 0, "class", 
                null, $("#classSelectBox").val(), null, $('#classDateSelectBox').val())});
                $(button).parent().css('padding-right', '0');
                $(newButton).addClass('manageXConfirmButton').insertAfter(button);
                return;
            }
        }
    });
}

function editCourseDetail (button, detail, data) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "editCourseDetail",
                detail: detail,
                data: data},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById(detail).outerHTML = result;
                $(button).off('click').on('click', function () {cancelCourseEdit(this, detail, data);});
                $(button).addClass('editButtonClose').removeClass('editButtonEdit');
                newButton = document.createElement('div');
                
                if (detail == 'courseName'){
                    $(newButton).off("click").on('click', function () {showManageXSubmitWindow(button, "courseName", data, $(button).siblings('.manageXInputContainer').children('input').val(), "course", null, null, 
                    $('#courseSelectBox').val())})
                } else {
                    $(newButton).off("click").on('click', function () {showManageXSubmitWindow(button, "courseDescription", data, $(button).siblings('.manageXInputContainer').children('textarea').val(), "course", null, null, 
                    $('#courseSelectBox').val())});
                }
                
                $(button).parent().css('padding-right', '0');
                $(newButton).addClass('manageXConfirmButton').insertAfter(button);
                return;
            }
        }
    });
}

function showUserDetailField (button, detail, data) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "showUserDetail",
                detail: detail,
                data: data},
        cache: false,
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById(detail).outerHTML = result;
                if (detail === "accountDisabled") {
                    $('.accountDisabledArea').val(Number($('.accountDisabledArea')[0].checked));
                    $('.accountDisabledArea').change(function(){this.value = (Number(this.checked));});
                }
                $(button).off('click').on('click', function () {cancelEdit(this, detail, data);});
                $(button).addClass('editButtonClose').removeClass('editButtonEdit');
                newButton = document.createElement('div');
                $(newButton).on('click', function () {showManageUserSubmitWindow(detail, data, $(button).siblings('.manageUserInputContainer').children('input').val(), button)});
                $(button).parent().css('padding-right', '0');
                $(newButton).addClass('manageUserConfirmButton').insertAfter(button);
                return;
            }
        }
    });
}

function changePassword (oldpw, newpw, confirmpw) {
    if (oldpw == "" || typeof oldpw === 'undefined'){
        $('#oldPassword').css("background-color", "#FFCCCB")
        return;
    }
    if (newpw == "" || typeof oldpw === 'undefined'){
        $('#newPassword').css('background-color', '#FFCCCB');
        return;
    }
    if (confirmpw == "" || typeof oldpw === 'undefined'){
        $('#confirmPassword').css('background-color', '#FFCCCB');
        return;
    }
    if (confirmpw !== newpw){
        $('#confirmPassword').css('background-color', '#FFCCCB');
        return;
    }
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "changePassword",
                oldpw: oldpw,
                newpw: newpw},
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                
                $.redirect('login.php');
            } else {
                document.getElementById("changePasswordResultBox").innerHTML = result;
                return;
            }
        }
    });
}

function submitManageUserPassword (newPassword, confirmPassword, userID) {
    if (newPassword !== confirmPassword) {
        $('.changeUserPasswordResultBox')[0].innerHTML = "<p>Passwords do not match.</p>";
        $('.manageUserPasswordConfirmField').css('background-color', '#FFCCCB');
        $('.manageUserPasswordConfirmField').css('border', '1px solid gray');
        $('.manageUserPasswordConfirmField').css('border-radius', '5px;');
        return;
    }
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "submitManageUserChange",
                detail: "passwordHash",
                data: newPassword,
                manageUserID: userID},
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                $.redirect('login.php');
            } else {
                $('.changeUserPasswordResultBox')[0].innerHTML = result;
                return;
            }
        }
    });
}

function submitManageUserChange (detail, data, newData, userID, button) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "submitManageUserChange",
                detail: detail,
                data: newData,
                manageUserID: userID},
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                $.redirect('login.php');
            } else {
                $('.changeUserDetailResultBox')[0].innerHTML = result;
                cancelEdit(button, detail, newData);
                $("#" + detail).attr("data-record", data);
                closeWindow();
            }
        }
    });
}

function submitManageXChange (button, detail, data, detailType, userID = null, classID = null, courseID = null, date = null) {
    
    
    
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "submitManageXChange",
                detail: detail,
                data: data,
                manageUserID: userID,
                detailType: detailType,
                classID: classID,
                courseID: courseID,
                date: date},
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                $.redirect('login.php');
            } else {
                $('.changeXDetailResultBox').html(result);
                if (detailType == "course"){
                    cancelCourseEdit(button, detail, data);
                } else if (detailType == "class") {
                    cancelClassEdit(button, detail, data);
                } else {
                    cancelAttendanceEdit(button, detail, date + " " + data);
                }
                $("#" + detail).attr("data-record", data);
                closeWindow();
                return;
            }
        }
    });
}

function submitAddX (addType, data) {
    switch (addType) {
        case "user":
            // Handle a little validation here, but most will be done on the server side
            if (data.password != data.confirmPassword) {
                document.getElementById('addXResultBox').innerHTML = "<p style='color: red'>Passwords do not match</p>";
                $('.addXPasswordConfirmField').css('background-color', '#FFCCCB');
                $('.addXPasswordConfirmField').css('border', '1px solid gray');
                $('.addXPasswordConfirmField').css('border-radius', '5px;');
                return;
            }
            data.accountDisabled = $("#accountDisabled").is(':checked') ? "1" : "0" ;
            break;
        case "class":
            data.days = createDaysByte(data.days);
            break;
    }
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: data,
        success: function (result) {
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                $.redirect('login.php');
            } else {
                document.getElementById('addXResultBox').innerHTML = result;
            }
        } 
    });
}

function updateAccessCode (classID) {
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: "updateAccessCode",
            classID: classID},
        success: function (result) {
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                $.redirect('login.php');
            } else {
                document.getElementsByClassName('changeXDetailResultBox')[0].innerHTML = result;
                getAccessCode($('#classSelectBox').val());
            }
        } 
    });
}

function createDaysByte(dayArray) {
    dayStringToBit = {"sunday": 1 << 6,
                      "monday": 1 << 5,
                      "tuesday": 1<< 4,
                      "wednesday": 1 << 3,
                      "thursday": 1 << 2,
                      "friday": 1 << 1,
                      "saturday": 1 << 0};
    daysByte = 0;
    dayArray.forEach(function (day) {
        daysByte = daysByte | dayStringToBit[day];
    });

    return daysByte;
}

function showManageUserSubmitWindow (detail, data, newData, button) {
    newOverlayDiv = document.createElement('div');
    newOverlayDiv.classList.add('overlay');
    newOverlayDiv.setAttribute('onclick', 'closeWindow()');
    newWindowDiv = document.createElement('div');
    newWindowDiv.classList.add('window');
    newWindowDiv.classList.add('manageUserSubmitWindow');
    newCloseButtonDiv = document.createElement('div');
    newCloseButtonDiv.classList.add("windowCloseButton");
    newCloseButtonDiv.setAttribute('onclick', 'closeWindow()');
    newWindowDiv.appendChild(newCloseButtonDiv);
    newDescriptionDiv = document.createElement('div');
    newDescriptionDiv.classList.add("manageUserSubmitDescription");
    newDescriptionP = document.createElement('p');
    newDescriptionP.innerHTML = "Are you sure you want to submit the following change?";
    newResultP1 = document.createElement('p');
    newResultP2 = document.createElement('p');
    newResultP3 = document.createElement('p');
    newResultP1.innerHTML = "Change '" + detail + "'";
    if (detail == "accountDisabled") {
        newResultP2.innerHTML += "Current: '" + ((data == 1) ? "Disabled" : "Enabled") + "'";
        newResultP3.innerHTML += "New: '" + ((newData == 1) ? "Disabled" : "Enabled") + "'";
    } else {
        newResultP2.innerHTML += "Current: '" + data + "'";
        newResultP3.innerHTML += "New: '" + newData + "'";
    }
    newDescriptionDiv.appendChild(newDescriptionP);
    newDescriptionDiv.appendChild(newResultP1);
    newDescriptionDiv.appendChild(newResultP2);
    newDescriptionDiv.appendChild(newResultP3);
    newWindowDiv.appendChild(newDescriptionDiv);
    newMessageDiv = document.createElement('div');
    newMessageDiv.classList.add('manageUserSubmitMessageBox');
    newWindowDiv.appendChild(newMessageDiv);
    newSubmitDiv = document.createElement("div");
    newSubmitDiv.classList.add("manageUserSubmitBox");
    newSubmitButton = document.createElement("button");
    newSubmitButton.classList.add("manageUserSubmitButton");
    newSubmitButton.innerHTML = "Submit Change";
    newSubmitDiv.appendChild(newSubmitButton);
    newWindowDiv.appendChild(newSubmitDiv);

    document.body.insertAdjacentHTML("afterbegin", newOverlayDiv.outerHTML);
    document.body.insertAdjacentHTML("afterbegin", newWindowDiv.outerHTML);

    $('.manageUserSubmitButton').on('click', function () { submitManageUserChange(detail, data, newData, $(".manageUserDetailsFieldset.fieldset1").attr("data-userid"), button)});
}

function showManageXSubmitWindow (button, detail, data, newData, detailType, userID = null, classID = null, courseID = null, date = null) {
    
    newOverlayDiv = document.createElement('div');
    newOverlayDiv.classList.add('overlay');
    newOverlayDiv.setAttribute('onclick', 'closeWindow()');
    newWindowDiv = document.createElement('div');
    newWindowDiv.classList.add('window');
    newWindowDiv.classList.add('manageXSubmitWindow');
    newCloseButtonDiv = document.createElement('div');
    newCloseButtonDiv.classList.add("windowCloseButton");
    newCloseButtonDiv.setAttribute('onclick', 'closeWindow()');
    newWindowDiv.appendChild(newCloseButtonDiv);
    newDescriptionDiv = document.createElement('div');
    newDescriptionDiv.classList.add("manageXSubmitDescription");
    newDescriptionP = document.createElement('p');
    newDescriptionP.innerHTML = "Are you sure you want to submit the following change?";
    newResultP1 = document.createElement('p');
    newResultP2 = document.createElement('p');
    newResultP3 = document.createElement('p');
    newResultSpan1 = document.createElement('span');
    newResultSpan2 = document.createElement('span');
    newResultSpan3 = document.createElement('span');
    newResultSpan1.classList.add('submitXWindowAccentSpan');
    newResultSpan2.classList.add('submitXWindowAccentSpan');
    newResultSpan3.classList.add('submitXWindowAccentSpan');
    newResultSpan1.innerHTML = "Change: ";
    newResultSpan2.innerHTML = "Current: ";
    newResultSpan3.innerHTML = "New: ";
    newResultP1.appendChild(newResultSpan1);
    newResultP2.appendChild(newResultSpan2);
    newResultP3.appendChild(newResultSpan3);
    newResultP1.innerHTML += "'" + detail + "'";
    if (detail == "classCanceled") {
        newResultP2.innerHTML += "'" + (data == 1 ? "Yes" : "No") + "'";
        newResultP3.innerHTML += "'" + (newData == 1 ? "Yes" : "No") + "'";
    } else {
        newResultP2.innerHTML += "'" + data + "'";
        newResultP3.innerHTML += "'" + newData + "'";
    }
    newDescriptionDiv.appendChild(newDescriptionP);
    newDescriptionDiv.appendChild(newResultP1);
    newDescriptionDiv.appendChild(newResultP2);
    newDescriptionDiv.appendChild(newResultP3);
    newWindowDiv.appendChild(newDescriptionDiv);
    newMessageDiv = document.createElement('div');
    newMessageDiv.classList.add('manageXSubmitMessageBox');
    newWindowDiv.appendChild(newMessageDiv);
    newSubmitDiv = document.createElement("div");
    newSubmitDiv.classList.add("manageXSubmitBox");
    newSubmitButton = document.createElement("button");
    newSubmitButton.classList.add("manageXSubmitButton");
    newSubmitButton.innerHTML = "Submit Change";
    newSubmitDiv.appendChild(newSubmitButton);
    newWindowDiv.appendChild(newSubmitDiv);

    document.body.insertAdjacentHTML("afterbegin", newOverlayDiv.outerHTML);
    document.body.insertAdjacentHTML("afterbegin", newWindowDiv.outerHTML);
    
    $('.manageXSubmitButton').on('click', function () { submitManageXChange(button, detail, newData, detailType, userID, classID, courseID, date)});
}

function closeWindow () {
    document.getElementsByClassName("window")[0].remove();
    document.getElementsByClassName("overlay")[0].remove();
}

function cancelEdit (button, detail, data) {
    newDiv = document.createElement('div');
    newDiv.id = detail;
    newDiv.setAttribute('data-record', data);
    newDiv.className = 'manageUserDetailResultBox';
    newP = document.createElement('p');
    if (detail == "accountDisabled") {
        ;
        newP.innerHTML = ((data == 1) ? "Account: Disabled" : "Account: Enabled");
    } else {
        newP.innerHTML = data;
    }
    newDiv.appendChild(newP);
    $(button).siblings('.manageUserInputContainer').replaceWith($(newDiv));
    $(button).off('click').on('click', function () {showUserDetailField(this, $(this).siblings('.manageUserDetailResultBox').attr('id') ,$(this).siblings('.manageUserDetailResultBox').attr('data-record'))});
    $(button).addClass('editButtonEdit').removeClass('editButtonClose').siblings('.manageUserConfirmButton').remove();
    $(button).parent().css('padding-right', '24px');
}

function cancelCourseEdit (button, detail, data) {
    newDiv = document.createElement('div');
    newDiv.id = detail;
    newDiv.setAttribute('data-record', data);
    newDiv.className = 'manageXDetailResultBox';
    if (detail == "courseDescription") {
        newDiv.className += ' courseDescriptionBox';
    }
    newP = document.createElement('p');
    newP.innerHTML = data;
    newDiv.appendChild(newP);
    $(button).siblings('.manageXInputContainer').replaceWith($(newDiv));
    $(button).off("click").on("click", function () {editCourseDetail(this, $(this).siblings('.manageXDetailResultBox').attr('id') ,$(this).siblings('.manageXDetailResultBox').attr('data-record'))});
    $(button).addClass('editButtonEdit').removeClass('editButtonClose').siblings('.manageXConfirmButton').remove();
    $(button).parent().css('padding-right', '24px');
}

function cancelClassEdit (button, detail, data) {
    newDiv = document.createElement('div');
    newDiv.id = "classCanceled";
    ;
    newDiv.setAttribute('data-record', data);
    newDiv.className = 'manageXDetailResultBox';
    newP = document.createElement('p');
    newP.innerHTML = (data == 1) ? "Canceled: Yes" : "Canceled: No";
    newDiv.appendChild(newP);
    $(button).siblings('.manageXInputContainer').replaceWith($(newDiv));
    $(button).off("click").on("click", function () {editClassCanceled(this, $(this).siblings('.manageXDetailResultBox').attr('id'), $(this).siblings('.manageXDetailResultBox').attr('data-record'))});
    $(button).addClass('editButtonEdit').removeClass('editButtonClose').siblings('.manageXConfirmButton').remove();
    $(button).parent().css('padding-right', '24px');
}

function cancelAttendanceEdit (button, detail, data) {
    newDiv = document.createElement('div');
    newDiv.id = detail;
    newDiv.setAttribute('data-record', data);
    newDiv.className = 'manageXDetailResultBox';
    newP = document.createElement('p');
    newP.innerHTML = data;
    newDiv.appendChild(newP);
    $(button).siblings('.manageXInputContainer').replaceWith($(newDiv));
    $(button).off("click").on("click", function () {editAttendanceTime(this, $(this).siblings('.manageXDetailResultBox').attr('id') ,$(this).siblings('.manageXDetailResultBox').attr('data-record'))});
    $(button).addClass('editButtonEdit').removeClass('editButtonClose').siblings('.manageXConfirmButton').remove();
    $(button).parent().css('padding-right', '24px');
}

// retreive the dashboard on page load
// Enable nav functionality
$(window).on("load",function() {
    window.scrollTo(0, 0);
    $(".headerButtons div, .headerNavButton").on("click", function(){operation(this.id);});
    operation("attendance");
});

// Add naviagtional functionality to the main pages.
addEventListener("popstate", (event) => {
    event.preventDefault();
    switch (event.state.function){
        case "operation":
            operation(event.state.option, true);
            break;
        default:
            break;
    }
});