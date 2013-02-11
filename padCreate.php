<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
// Load up the LTI Support code
require_once 'ims-blti/blti.php';
// Initialize, all secrets are 'secret', do not set session, and do not redirect
$context = new BLTI("SECRET", false, false);
// Include the Fun-ctions Class for making pads
include 'fun-ctions.php';
//Include the cookie code
require_once('../inc/code.php');
//Connect to the database
mysql_connect("localhost", "USERNAME", "PASSWORD") or die(mysql_error());
mysql_select_db("etherpad-lite") or die(mysql_error());
header('Content-Type: text/html; charset=utf-8'); 
//Start the session via Sessions
session_start();
//The remote tool key as set in the referrer for BTLI
$remoteKey="12345";	
//Set the username variable either via blti or ldap or die	
//Make sure the private(ish) key passed from Sakai is correct
if ($context->valid===TRUE){
	//Check to see if the set cookie is good and return the current user
	$username = $_POST['lis_person_sourcedid'];
	//echo $context->info[custom_toolurl]
}
else{
	$username = security();
	$fromCookie = TRUE;
	//Check to see if the user is logging in with a student ID 
	//Get the third character of the username (which would be a number in a student CAMPUS ID)
	$threeChar = substr($username, 2, 1);
	$isStudent=FALSE;
	//Check if it's a number
	if (is_numeric($threeChar)){
		$isStudent=TRUE;
	}
}
//Output the Header if they arn't in Sakai (no BLTI connection)
if ($context->valid===FALSE){
	require_once('/var/www/html/common/config/common_html.php');
	$title = "CPI Pad Creation Service";
	$context->info['context_title'] = "the  Request Facility";
	html_head_start($title);
}
?>		
<!--Import jQuery UI and create tabs -->
<script type="text/javascript" language="javascript" src="https://ctletURL/events-new/includes/ctlet/media/js/jquery.js"></script>
<link rel="stylesheet" href="https://URL/request/basic/css/blitzer/jquery-ui-1.9.1.custom.min.css" />
<script>
    $(function() {
        $( "#tabs" ).tabs();
    });
</script>
<!--Datatables include and config -->
<style type="text/css" title="currentStyle">
		@import "table.css";
		body{margin: 0px !important;};
	</style>
	<script type="text/javascript" language="javascript" src="https://ctletURL/events-new/includes/ctlet/media/js/jquery.dataTables.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js"></script>	
	<!--Edittable includes-->
	<script src="jquery.validate.js" type="text/javascript"></script>
    <script src="jquery.DataTables.editable.js" type="text/javascript"></script>
	<script src="jquery.jeditable.js" type="text/javascript"></script>

	<script type="text/javascript" charset="utf-8">
	//Give the columns the proper table names, make url un-editable, make the table editable and point when to make calls
		$(document).ready(function() {
			$('#fancyTable').dataTable({ "aLengthMenu": [
            [25, 50, 100, 200, -1],
            [25, 50, 100, 200, "All"]], 
			"iDisplayLength" : -1, "aoColumns": [{sName: "padName", tooltip:'Click to Edit'}, {sName: "password"}, {sName: "padAuthor", sReadOnlyCellClass: "read_only"}, {sName:"url"}], sReadOnlyCellClass: "read_only"})
					.makeEditable({
					sUpdateURL: "update.php"				
                });
		} );
		$(document).ready(function() {
			$('#fancyTable2').dataTable({ "aLengthMenu": [
            [25, 50, 100, 200, -1],
            [25, 50, 100, 200, "All"]], 
			"iDisplayLength" : -1 });
		} );
</script>
<script src="ether_javascript.js" type="text/javascript"></script>
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE" />
<title>Etherpad Listings</title>

<?php
if ($context->valid===FALSE){

	html_head_stop();
	html_body_start('',$title);
}
//Create a session with the user information only after being passed from LTI

if (isset($context->info['roles'])){

	createSession($username, $context->info['context_title'], $context->info['roles'], $context->info['lis_person_sourcedid'], $context->info['lis_person_name_full'], "blti");
	
	unset($_POST['roles']);
}
//Create a session from the cookie passed information
elseif (isset($fromCookie)){
	createSession($username, $username, "Instructor", $_COOKIE['username'], $_COOKIE['authorName'], "cookie");

}
$instance = new EtherpadLiteClient('API-KEY',$GLOBALS['server_url']);
//$password = $instance->setPassword("g.bbpaAOMyvWFHfoMB$54", "");
//If the user is an instructor let them create new pads
if ($context->info['roles'] == "Instructor" || $fromCookie == TRUE){
	//If a pad is not being created output the users data
	echo "\n\n<!-- Hello '".$username."' -->\n\n";
	//If they are a student that logged into the  facility (so $fromCookie was True) 
	if ($isStudent==TRUE){
		echo "<br /><h2 style=\"text-align:center\">Thank you for your interest in the Etherpad system</h2><br />";
		echo "<p>Unfortunately students are not able to create new Etherpad's at this time. <br />If you would like a new
		Pad created for course related uses please contact the CPI at <a href=\"mailto:edtech@brocku.ca?Subject=Etherpad - Student Request\">edtech@brocku.ca</a></p>";
		echo "<a href=\"https://URL/request/options.php\">Return to Collaborative Writing Tools Request Page</a>";
	}	
	//Otherwise they are staff and show them the creation screen
	else{
	//Output the form to allow new etherpad-lite creation
	//echo '<span style="color:#C00"><a href="#create">	Create a New Pad</a> | <a href="#you">Pads Created by You</a> | <a href="#other">Alls Pads Created in this Course</a></span>';	
	//Create a wrapper for the tabs
	echo '<div id="tabs" style="border-color:white;">
	<ul>
        <li><a href="#tabs-1">Create a Pad</a></li>
        <li><a href="#tabs-2">Pads Created by You</a></a></li>
        <li><a href="#tabs-3">All Pads Created in this Course</a></li>
    </ul>	';
	
	//echo my_decode('6BYLUnK_xfHhtyA1b_UMPPhBLa7Mbn1E5kOOLtjbbfY', 'ak876dgeyyatH98UBNCwyhs73UYAS');
	
	echo '<div id="tabs-1">
	<h2>Create a new Etherpad</h2>
	<p>Create a new Pad. By default Pads will be accessible to anyone with the link. You can choose to add a password to your Pad for added protection.</p><br />
	<div id="functionFormDiv">
		<form name="functionForm" action="">
			<label for="nickname">Enter name for new Etherpad:</label><br />
			<input type="text" name="nickname" id="nickname" size="30"/><br /> 
			<label class="error" for="nickname" id="nickname_error" style="color:red;">This field is required.<br /></label>
			<label for="password">Enter pad password (optional):</label><br />
			<input type="text" name="password" id="password" /><br /> 
			<label class="error" for="password" id="password_error" style="color:red;">This field is required.<br /></label>
			<input type="submit" name="padCreate" value="Create Pad" class="button"/>
		</form>	
	</div>
	<div id="createStatus"></div>'; 
	
	echo "</div>";   
	echo '<div id="tabs-2"><h2>Pads Created by You:</h2>';
	echo "<p>A listing of all pads created by you in all courses.</p>";
	echo "<p>Double click a cell in the <b>Pad Name</b> or <b>Password</b> columns to edit it. Enter saves your changes.</p>";
	//Grab all the pads that the user is the owner of 
	$padsQuery=mysql_query("SELECT * FROM `etherpad-lite`.`padOwners` WHERE `authorID`='".$_SESSION['Etherpad-authorID-cpi']."'");
	echo '<div id="listPads"><ul style="padding-left:0px">';	  
	//Build the table
	echo "<table id=\"fancyTable\" style=\"width:100%\">";
	echo '<thead><tr><th width="20%">Pad Name</th><th width="10%">Password</th><th width="10%">Author</th><th width="40%">Pad Url</th></tr></thead>';
	echo "<tbody>";
	//Output each row's data
	while ($padsResult=mysql_fetch_assoc($padsQuery)){
		echo "<tr id=\"".$padsResult['padID']."\">";
		//Edit etherpad settings
		//echo "<td><a href=\"".$_SERVER['PATH_INFO']."?padID=".$padsResult['padID']."\"><img src=\"https://lmsURL/library/image/silk/cog.png\"</a></td>";
		//Name of the pad
		echo "<td>".$padsResult['padName']."</td>";
		//Password of the pad
		echo "<td>".my_decode($padsResult['password'], 'ak876dgeyyatH98UBNCwyhs73UYAS')."</td>";	
		//Author of the pad
		echo "<td class=\"read_only\">".$padsResult['authorName']."</td>";
		//URL of the pad
		echo "<td class=\"read_only\"><a target=\"_new\" href=\"https://URL/pad?pID=".$padsResult['padID']."\"> https://URL/pad?pID=".$padsResult['padID']." </a></td>";
		echo "</tr>";
	
	}
	//Close the table
	echo "</tbody></table>";
	echo '<ul></div></div>';
	//Pads that are related to this course
	echo '<div id="tabs-3">';
	echo "<h2 id=\"other\">All Pads Created in ".$context->info['context_title'].":</h2>";
	echo "<p>A listing of all pads created in this course regardless of whether or not you created them.</p><br />";
	//Grab all the pads that the user is the owner of
	$padsQuery=mysql_query("SELECT * FROM `etherpad-lite`.`padOwners` WHERE `groupID`='".$_SESSION['Etherpad-groupID-cpi']."'");
	echo '<div id="listPads"><ul style="padding-left:0px">';	 
	//Build the table
	echo "<table id=\"fancyTable2\" style=\"width:100%\">";
	echo '<thead><tr><th width="20%">Pad Name</th><th width="10%">Author</th><th width="40%">Pad Url</th></tr></thead>';
	echo "<tbody>";
	//Output each row's data
	while ($padsResult=mysql_fetch_assoc($padsQuery)){
		echo "<tr>";
		//Edit etherpad settings
		//echo "<td><a href=\"".$_SERVER['PATH_INFO']."?padID=".$padsResult['padID']."\"><img src=\"https://lmsURL/library/image/silk/cog.png\"</a></td>";
		//Name of the pad
		echo "<td>".$padsResult['padName']."</td>";
		//Author of the pad
		echo "<td>".$padsResult['authorName']."</td>";
		//URL of the pad
		echo "<td><a target=\"_new\" href=\"https://URL/pad?pID=".$padsResult['padID']."\"> https://URL/pad?pID=".$padsResult['padID']." </a></td>";
		echo "</tr>";
	}
	//Close the table
	echo "</tbody></table>";
	echo '<ul></div></div>';
	}
	//End the tab wrapper 
	echo '</div>';
}	
//If they are not an instructor
else{
	echo "\n\n<!-- Hello '".$username."' -->\n\n";
	//echo "Only Instructors can create new pads.";
	//If a pad is not being created output the users data
	echo "\n\n<!-- Hello '".$username."' -->\n\n";
	echo "<h2>Listing of Etherpads for ".$_SESSION['Etherpad-group-cpi']."</h2>";
	//TESTING for Etherpad_Access and Etherpad_Create
	if($_SESSION['Etherpad-groupID-cpi'] == "g.no1JWWlW02MCnoRN"){
		$padsQuery=mysql_query("SELECT * FROM `etherpad-lite`.`padOwners` WHERE `groupID`='g.QRx9IMtFMkoP00ks'");
	}
	else{
	//Grab all the pads for the course that the student is a part of
	$padsQuery=mysql_query("SELECT * FROM `etherpad-lite`.`padOwners` WHERE `groupID`='".$_SESSION['Etherpad-groupID-cpi']."'");
	}
	echo '<div id="listPads"><ul>';		
	while($padsResult=mysql_fetch_assoc($padsQuery)){
		
			echo "<li>".$padsResult['padName'].": <a href=https://URL/pad?pID=".$padsResult['padID'] .">https://URL/pad?pID=".$padsResult['padID']."</a></li><br />";
	}
	echo '</ul></div>';	
}
//If the user is not in Sakai give them the footer
if ($context->valid===FALSE){
	html_body_stop ();
	}
?>
</html>