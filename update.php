<?php
//Include fun-ctions since we need to use my_encode
include 'fun-ctions.php';
//Set the server url for the etherpad connection
$GLOBALS['server_url']="https://URL:9000/api";
//Capture the data pushed by the makeEditable request
$id = $_REQUEST['id'] ;
$value = $_REQUEST['value'] ;
$column = $_REQUEST['columnName'] ;
//Connect to the database for the prepared statement
$dbConnection = new PDO('mysql:dbname=etherpad-lite;host=localhost;charset=utf8', 'etherpad-lite', 'puu6rtg376n');
$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//If the user is making a change to to the padName
if ($column=="padName"){
	//Build the PDO statement and execute it
	$statement = "UPDATE padOwners SET padName=? WHERE padID=?";
	$exec = $dbConnection->prepare($statement);
	$exec->execute(array($value,$id));
}
//If the user is making a change to to the password
if ($column=="password"){
	//Encode the password before storing it
	$sql_value= my_encode($value, 'SALT');
	
	//Update the password in the database
	$statement = "UPDATE padOwners SET password=? WHERE padID=?";
	$exec = $dbConnection->prepare($statement);
	$exec->execute(array($sql_value,$id));
	
	//Update the stored password for the pad in the value pair table
	$instance = new EtherpadLiteClient('API-KEY',$GLOBALS['server_url']);
	$instance->setPassword($id, $value);
}
//Print out the value passed - this will fill the edited cell
echo $value;
?>