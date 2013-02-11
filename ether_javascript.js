//Holds the Javascript for form validation as well as the .ajax for the makepad pass to callBack.php

$(function etherScript () {
  	 //Hide the error messages
    $('.error').hide();
    //When the create button is pressed
    $(".button").click(function() {
      // validate the name input field      
      $('.error').hide();
  	  var name = $("input#nickname").val();
  		if (name == "") {
  			//show the error if the field is empty
        $("label#nickname_error").show();
        $("input#nickname").focus();
        return false;
      }
      // validate the password input field
  		var password = $("input#password").val();

      //create the POST values to pass to callBack.php
      var dataString = 'nickname=' + name + '&password=' + password + '&padCreate=' + "go";  		
		$.ajax({  
		  type: "POST",  
		  //url: "callBack.php", 
		  url: "callBack.php",	
		  data: dataString, 
		  cache: false,
		  dataType: 'html', 
		  error: function(jqXHR, exception) {
            if (jqXHR.status === 0) {
                alert('Not connect.\n Verify Network.');
            } else if (jqXHR.status == 404) {
                alert('Requested page not found. [404]');
            } else if (jqXHR.status == 500) {
                alert('Internal Server Error [500].');
            } else if (exception === 'parsererror') {
                alert('Requested JSON parse failed.');
            } else if (exception === 'timeout') {
                alert('Time out error.');
            } else if (exception === 'abort') {
                alert('Ajax request aborted.');
            } else {
                alert('Uncaught Error.\n' + jqXHR.responseText);
            }
        },
		  success: function(response) {
		  		//Print out the makePad response (a link to the pad) and the password if there was one;
				if (typeof password !== "undefined" && password) {	
						document.getElementById('createStatus').innerHTML+='<li>'+name+': <a href='+response+'>'+response+'</a> <span style="color:red"> Pass: '+password+'</span></li><br />';
						//$('#listPads').prepend('<li>'+name+': <a href='+response+'>'+response+'</a> <span style="color:red"> Pass: '+password+'</span></li><br />');

				}
				else{
						document.getElementById('createStatus').innerHTML+='<li>'+name+': <a href='+response+'>'+response+'</a><span style="color:green"> Pass: NONE </span></li><br />'
						//$('#listPads').prepend('<li>'+name+': <a href='+response+'>'+response+'</a><span style="color:green"> Pass: NONE </span></li><br />');
    			} 
    		}
		});  
		return false;
      
    });
});
  
