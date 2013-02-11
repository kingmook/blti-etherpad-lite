<html>
<script src="ether_javascript.js" type="text/javascript"></script>
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load up the LTI Support code
require_once 'ims-blti/blti.php';

// Initialize, all secrets are 'secret', do not set session, and do not redirect
$contexts = new BLTI("SECRET", false, false);

// Include the Fun-ctions Class for making pads
include 'fun-ctions.php';

//Connect to the database
mysql_connect("localhost", "USERNAME", "PASSWORD") or die(mysql_error());
mysql_select_db("etherpad-lite") or die(mysql_error());
header('Content-Type: text/html; charset=utf-8'); 

//Start the session via Sessions
session_start();

//Set the username variable either via blti or ldap or die	
//Make sure the private(ish) key passed from Sakai is correct
if ($contexts->valid===TRUE){
	//Check to see if the set cookie is good and return the current user
	$username = $_POST['lis_person_sourcedid'];
}
else{
	echo "This page can only be accessed from within Isaak/Sakai";
}

//Create a session with the user information only after being passed from LTI
if (isset($_POST['roles'])){

	createSession($username, $_POST['context_title'], $_POST['roles']);

	unset($_POST['roles']);
}

//If a pad is not being created output the users data
echo "Hello '".$username."'";


echo "<h2>Listing of Etherpads for ".$_SESSION['Etherpad-group-cpi']."</h2>";
//Grab all the pads that the user is the owner of
$padsQuery=mysql_query("SELECT * FROM `etherpad-lite`.`padOwners` WHERE `groupID`='".$_SESSION['Etherpad-groupID-cpi']."'");
echo '<div id="listPads"><ul>';		
while($padsResult=mysql_fetch_assoc($padsQuery)){
	
		echo "<li>".$padsResult['padName'].": <a href=https://URL/request/basic/access.php?pID=".$padsResult['padID'] .">https://URL/request/basic/access.php?pID=".$padsResult['padID']."</a></li><br />";
}
echo '</ul></div>';

?>
</html>
