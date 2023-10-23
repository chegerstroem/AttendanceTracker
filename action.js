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
function operation(option) {
    
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: option},
        success:function(result){
            if (result.startsWith("<!--xKy89Rt-->")) { // If the result starts with this string, it's the login page; Redirect on the clientside
                $.redirect('login.php');
            } else {
                // Update contentBox with the results of the function
                document.getElementById("contentBox").innerHTML = result;
            }
        }
    });
}

// execute php function "showDashboard()" on page load
$(window).on("load",function() {
    operation("dashboard");
});