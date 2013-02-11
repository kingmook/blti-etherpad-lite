<?php	
//Receives the AJAX calls from ether_javascript to create the etherpad
// Include the Fun-ctions Class for making pads
include 'fun-ctions.php';

//Connect to the database
mysql_connect("localhost", "USERNAME", "PASSWORD") or die(mysql_error());
mysql_select_db("etherpad-lite") or die(mysql_error());

//Start the PHP session
session_start();

//Strip out single and double quotes from the pad name to avoid problems
$_POST['nickname'] = htmlspecialchars($_POST['nickname'], ENT_QUOTES);

//Actually make the pad
makePad($_SESSION['Etherpad-authorID-cpi'], $_SESSION['Etherpad-groupID-cpi'], $_POST['password'], $_POST['nickname'], $_SESSION['Etherpad-author-cpi'], $_SESSION['Etherpad-user-cpi']);
?>
