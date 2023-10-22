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
            console.log(result + " " + option);
             // Update contentBox with the results of the function
            document.getElementById("contentBox").innerHTML = result;
        }
    });
  }

