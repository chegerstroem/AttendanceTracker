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
function operation (option) {
    
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
                }
                // Update contentBox with the results of the function
                document.getElementById("contentBox").innerHTML = result;
                if (option == "manageUsers") {
                    $(".userMgmtBox > table > tbody > tr").on('click', function(){manageUser(this.dataset.userid);});
                }
                return;
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
            }
            document.getElementById("contentBox").innerHTML = result;
            $('#phone-area').keyup(function () {if (this.value.length >= 3){$('#phone-prefix').focus();}});
            $('#phone-prefix').keyup(function () {if (this.value.length >= 3){$('#phone-line').focus();}});
            return;
        }
    });
}

function closeAttendWindow () {
    document.getElementById("attendClassWindow").remove();
    document.getElementsByClassName("overlay")[0].remove();
}

// retreive the dashboard on page load
$(window).on("load",function() {
    $(".headerButtons div, .headerNavButton").on("click", function(){operation(this.id);});
    operation("dashboard");
});
