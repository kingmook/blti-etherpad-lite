<?php
//Functions to invoke the PHP API(written by TomNomNom) for Etherpad-Lite (by Pita) to create new Etherpads
$GLOBALS['server_url']="https://URL:9000/api";
// Include the Etherpad-Api Class
include 'etherpad-lite-client.php';

//-----FUN-ctions-----
//Create a session that holds the author name and ID as well as the group name and ID
function createSession($authorName, $groupName, $roles, $userName, $authorName, $from){

	//Commented out until the etherpad server is actuall running - 
	//Create the API connection
	$instance = new EtherpadLiteClient('API-Key',$GLOBALS['server_url']);
	//Create the user account from passed LTI campus ID
	$author = $instance->createAuthorIfNotExistsFor($authorName, "1");
	$authorID = $author->authorID;
	
	//Create a group for the username from passed LTI course name
	$mappedGroup = $instance->createGroupIfNotExistsFor($groupName);
	//Group ID is the unique identifier for the user
	$groupID = $mappedGroup->groupID;
	
	//Set the session - this needs to be consolidated into one session that can be exploded
	$_SESSION['Etherpad-user-cpi']=$userName;
	$_SESSION['Etherpad-author-cpi']=$authorName;
	$_SESSION['Etherpad-group-cpi']=$groupName;
	$_SESSION['Etherpad-authorID-cpi']=$authorID;
	$_SESSION['Etherpad-groupID-cpi']=$groupID;
	$_SESSION['Etherpad-roles']=$roles;
	$_SESSION['Etherpad-from']=$from;
}

//Create a new passed word protected group etherpad 
function makePad($authorID, $groupID, $password, $padName, $authorName, $userName){	 

	//Set the base host for the link printout
	$host = "https://URL:9000";
	
	//Set the timezone since mktime is so darn picky 
	date_default_timezone_set('America/Toronto'); 
	
	//Make the etherpad connection to the API
	$instance = new EtherpadLiteClient('API-KEY',$GLOBALS['server_url']);
	//$instance = new EtherpadLiteClient('47ydhsg6d5taycbdgf',$GLOBALS['server_url']);

	//Create the session
	$validUntil = mktime(0, 0, 0, date("m"), date("d")+1, date("y")); // One day in the future
	$sessionID = $instance->createSession($groupID, $authorID, $validUntil);
	$sessionID = $sessionID->sessionID;
	//The sessionID identifier, the generated session token, good for a day, and applies to everything on this domain
	setCookie("sessionID",$sessionID, time() +3600*24, "/");
	
	//Default passed content
	$contents="DEFAULT CONTENT GOES HERE";
	//Grab the current ennumeration from the Database
	$numQuery = mysql_query("SELECT `padNum` FROM `trackingTable`");
	$numResult = mysql_fetch_assoc($numQuery);
	
	//Set the name of the Pad
	$name = ($numResult['padNum']+1);
	
	//Ennumerate the Database
	mysql_query("UPDATE `trackingTable` SET `padNum`=".$name."");
	//Actually create the page and push the user to it
	$newPad = $instance->createGroupPad($groupID,$name,$contents);
	$padID = $newPad->padID;

	//Put a password on the pad if one was set	
	if (isset($password)){
		$sql_password = my_encode($password, 'SALT');
		$password = $instance->setPassword($padID, $password);
		//Put the encrypted blob into the database along with all the user and pad data
		mysql_query("INSERT INTO `padOwners`(`authorID`, `padID`, `padName`, `password`, `groupID`, `authorName`, `username`) VALUES ('".$authorID."','".$padID."','".$padName."','".$sql_password."','".$groupID."','".$authorName."', '".$userName."')");
	}
	else{
		//Assign this pad to this user
		mysql_query("INSERT INTO `padOwners`(`authorID`, `padID`, `padName`, `groupID, `authorName`, `username`) VALUES ('".$authorID."','".$padID."','".$padName."','".$groupID."','".$authorName."', '".$userName."')");
	}
	
	//Print out the URL for the pad
	$newlocation = "https://URL/pad?pID=".$padID.""; // redirect to the new padID location
	echo $newlocation;

}

//Encryption and base64 encoding functions 
//via http://stackoverflow.com/questions/1289061/best-way-to-use-php-to-encrypt-and-decrypt (credit to pagewil)
function safe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
}

function safe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
}

function my_encode($value, $skey){ 
        if(!$value){return false;}
        $text = $value;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $skey, $text, MCRYPT_MODE_ECB, $iv);
        return trim(safe_b64encode($crypttext)); 
}

 function my_decode($value, $skey){
        if(!$value){return false;}
        $crypttext = safe_b64decode($value); 
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $skey, $crypttext, MCRYPT_MODE_ECB, $iv);
        return trim($decrypttext);
}

?>
