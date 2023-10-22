/* 
 *
 * Christian H - All code
 *
 */

function operation(option) { // Make asyncronous calls to content.php
    
    $.ajax({
        url:"api.php",
        type: "POST",
        dataType: 'html',
        data: {operation: option},
        success:function(result){
             // Update contentBox with the results of the function
            document.getElementById("contentBox").innerHTML = result;
        }
    });
}

// execute php function "showDashboard()" on page load
$(window).on("load",function() {
    operation("dashboard");
});