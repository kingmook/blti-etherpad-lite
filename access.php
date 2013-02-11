<?PHP
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

/****************************************************
Server configuration:
Alias /pad /var/www/request/basic/access.php
Alias /etherpad /var/www/request/basic/access.php
Alias /p /var/www/request/basic/access.php

Preferred URLs https://URL/pad?
****************************************************/
$GLOBALS['server_url']="https://URL:9000/api";
//Set the message of the day if any and set if you want it on
$motdStatus=TRUE;
$motd="MESSAGE OF THE DAY GOES HERE";


//Include the cookie code
require_once('/var/www/html/common/config/common_html.php');

$codeName = security(false,true);

// Include the Etherpad-Api Class
include 'etherpad-lite-client.php';

// Load up the LTI Support code
require_once 'ims-blti/blti.php';
$context = new BLTI($etherpad_blti_tool_secret, false, false);

//Set the timezone since mktime is so darn picky 
date_default_timezone_set('America/Toronto');

//Connect to the database
mysql_connect("localhost", "USERNAME", "PASSWORD") or die(mysql_error());
mysql_select_db("etherpad-lite") or die(mysql_error());

//Get passed padID
$padID = $_GET['pID'];

//Start the session
session_start();

$title="Etherpad";

$giantHead = '<html>
<head>'.html_head_start($title, true).'
<title>Etherpad</title>

<style type="text/css" media="screen">
	'.file_get_contents('style.css').'
</style>
<style type="text/css" media="print">
	'.file_get_contents('print.css').'
</style>
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE" />';
$giantHead .= "\n</head>\n<body onLoad=\"document.getElementById('pad').focus()\" >\n".html_head_stop(true)."";
$giantHead .= "<div id=\"print-notes\">
<h2>This etherpad-based web page cannot be printed</h2><p>You have two options to print the contents of this etherpad</p>
<ol>
<li><strong>Copy and Paste:</strong> please select the contents of this pad, copy the contents, and paste them into an on-line or off-line word processor and use its print options.  You may want to <em>paste and match style</em> or <em>paste as plain text</em> to avoid author colours being pasted and possibly printed.</li>
<li><strong>Export:</strong> Choose the <strong>Export <img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAaCAMAAACTisy7AAAAQlBMVEVQUFD39/fm5ubW1taZmZlsbGy8vLz////v7++EhISurq7e3t7MzMxmZmaJiYl8fHxaWlqysrKlpaXHx8eVlZV2dna2UfC+AAAACXBIWXMAAArwAAAK8AFCrDSYAAAAqUlEQVQoka3Q0RaCIAyAYbYpYrCg0vd/1YaDQsNOF/13+snZ0AAAngTm1Dw5IwZTNy/oXd/+i84dELb0VYhYngVdxfI9pblogxMOyyBduaqiHvQj1+iIAOQltIltO1NP2kEXYlsXwrztZoFnvUC1Bh9jWm+5S4A65oVLYk45s0xvLDMppkgUJA8tok5YdWY9t0OgO+xr8aOCrtsP2LcN8UwzilI/+fFfegJHARA9tKf9SAAAAABJRU5ErkJggg==\" width=\"28\" height=\"26\" /></span></strong> option from the top right of the pad and select <em>HTML</em> or <em>Plain text</em></strong>.  You can then open these files on your computer and print them in the exported format.</li>
</ol>
<p>This etherpad  is available on-line at <a href=\"https://$_SERVER[HTTP_HOST]/$_SERVER[REQUEST_URI]\">$_SERVER[HTTP_HOST]/$_SERVER[REQUEST_URI]</a></p>
</div>\n";
$giantHead .= "<div id=\"pad-wrap\">\n";

//Grab the author ID from the database to ensure it exists
$authorQuery = mysql_query("SELECT `authorID` FROM `padOwners` WHERE `padID` = '".mysql_real_escape_string($padID)."'");
$authorResult = mysql_fetch_assoc($authorQuery);

$infoQuery = mysql_query("SELECT `authorName`,`padName` FROM `padOwners` WHERE `padID` = '".mysql_real_escape_string($padID)."'");
$infoResult = mysql_fetch_assoc($infoQuery);
 
//If the user is coming from BLTI, has entered a prompt name or has a cookie don't prompt for name
if (isset($_POST['lis_person_sourcedid']) || isset($_POST['name']) || isset($_COOKIE['userName']) || isset($codeName) || isset($_SESSION['Etherpad-user-cpi'])){	
	$_POST['userpass'] = "yes";
}
//Else get a username to pass
else{
	if ($authorResult !== FALSE){
	//Print out the head
	echo $giantHead;	
	
	//Print out the body 
	//html_body_start("", "Etherpad Service");
	echo '<h3 class="centre">The pad you are trying to access is titled: '.$infoResult['padName'].'</h3>';
	
	//Show the name form and re-submit to the page
	echo $_SESSION['Etherpad-user-cpi'];
	echo'<div class="p_one"><form name="namePass" action="pad?pID='.$padID.'" class="namePass" method="POST">
			<p>Please enter the name you\'d like to be know as.</p>
			<label for="name">CAMPUS ID/Full Name</label>
			<input type="text" name="name" id="name" size="65"/><br /><br />
			<input type="submit" name="nameSub" value="Enter Pad" class="button"/>
		</form></div>
		<div class="p_one">&nbsp;</div>
		';
	
	
	}
}

//If there is a return on the URL (and therefore the pad exists)
if ($authorResult !== FALSE){
	
	//If the page has passed the username
	if ($_POST['userpass'] == "yes"){
	
		//Strip the pad name from the groupID
		$namePos = strpos($padID, "$");
		$groupID = substr($padID, 0, $namePos);
		
		//Make the etherpad connection to the API
		$instance = new EtherpadLiteClient('API-KEY',$GLOBALS['server_url']);
		//$instance = new EtherpadLiteClient('47ydhsg6d5taycbdgf',$GLOBALS['server_url']);


		//Default pad is not in sakai
		$fromSakai=FALSE;
		//$_POST['lis_person_name_full'] will need to get changed to 'lis_person_sourcedid' in Isaak/Sakai for campusID
		if (isset($_POST['lis_person_sourcedid']) && $context->valid===TRUE){	
		
			//If the username is passed via BTLI
			$authorName = $_POST['lis_person_name_full'];
			echo "\n<!--Authentication Type: 1-->\n";
			$fromSakai=TRUE;
		}
		else if (isset($_SESSION['Etherpad-user-cpi']) && ($_SESSION['Etherpad-from']=="blti")){
			//If the user is coming from the group listing page
			$authorName = $_SESSION['Etherpad-user-cpi'];
			echo "\n<!--Authentication Type: 2-->\n";
			$fromSakai=TRUE;
		}		
		else if (isset($_POST['name'])){
			//If the username is passed via Prompt page
			$authorName = $_POST['name'];
			echo "\n<!--Authentication Type: 3-->\n";
		}
		else if (isset($codeName)){
			//If the username is passed via security function
			$authorName = $codeName;
			echo "\n<!--Authentication Type: 4-->\n";
		}
		else if (isset($_COOKIE['userName'])){
			//If the username is passed via Cookie
			$authorName = $_COOKIE['userName'];
			echo "\n<!--Authentication Type: 5-->\n";
		}	
		else{
			//No username passed, create an anoyomous user
			$authorName = "Anon".rand(0,999)."-".date('Y/m/d');
			echo "\n<!--Authentication Type: 6-->\n";
		}
		
		//Set the cookie depending on which username was set
		setCookie("userName",$authorName, time() +3600*24, "/");
			
		//If the username is not passed via BLTI create an anoyomous user (time+rand)
		//Create the user account from passed LTI campus ID
		$author = $instance->createAuthorIfNotExistsFor($authorName, $authorName);
		$authorID = $author->authorID;
		
		//Create the session
		$validUntil = mktime(0, 0, 0, date("m"), date("d")+1, date("y")); // One day in the future
		$sessionID = $instance->createSession($groupID, $authorID, $validUntil);
		$sessionID = $sessionID->sessionID;
		
		//Create the access cookie
		setCookie("sessionID",$sessionID, time() +3600*24, "/");
		
		//Print out the header
		echo $giantHead;
		
		//Print out the  header if we are not in Sakai
		/*if ($fromSakai!==TRUE){
				html_body_start();
		}*/
		
		//Check if it's IE 6/7/8
		$IE6 = (ereg('MSIE 6',$_SERVER['HTTP_USER_AGENT'])) ? true : false;
		$IE7 = (ereg('MSIE 7',$_SERVER['HTTP_USER_AGENT'])) ? true : false;
		$IE8 = (ereg('MSIE 8',$_SERVER['HTTP_USER_AGENT'])) ? true : false;
		
		if (($IE6 == 1) || ($IE7 == 1) || ($IE8 == 1)) {
			echo "<center>Unfortunately Internet Explorer 8 and lower are not fully supported. You may encounter problems. <br />Supported Browsers are: <a href='http://www.mozilla.org/en-US/firefox/new/'>Firefox</a>, <a href='https://www.google.com/intl/en/chrome/browser/'>Chrome</a>, <a href='http://www.apple.com/safari/'>Safari</a> and <a href='http://windows.microsoft.com/en-US/internet-explorer/downloads/ie-9/worldwide-languages'>IE 9</a>.<br /><br /></center>";
		}
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		if(preg_match('/MSIE/i',$u_agent))
		{$browser = "ie";}
		
		//Print out the Message of the Day if it has been enabled (see top of file)
		if ($motdStatus == TRUE){
			echo '<div class="centre"><span class="motd">Message of The Day: </span><span>'.$motd.'</span></div>';
		}
		
		//Wrap the frame and it's components
		echo '<div id="pad-iframe" style="height:document.documentElement.clientHeight !important">';
		
		//Print out the name of the pad creator	
		echo '<div id="pad-creator"><p class="padAuthor"><strong>Pad creator:</strong> '.$infoResult['authorName'].'</p></div>';
		//echo '<p class="padTitle"><strong>Pad Name: </strong>'.$infoResult['padName'].'</p></div>';
		
		//Print out the Logout if we are not in Sakai
		if ($fromSakai!==TRUE){
			echo '<div id="logout"><a href="https://URL/request/logoff.php"> Logout </a></div>';
		}
		
		//Frame the Etherpad if it's IE
		if($browser == "ie"){
			echo "<br /><br />";
		}
		
		//Check if they are using a mobile browser and if so make the iframe scroll
		if(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'android')) {
			//DO mobile stuff
		}
		
		$newlocation = "https://URL:9000/p/".$padID."";
		//echo '<div id="pad-iframe">';
		echo '<iframe id="pad" src="'.$newlocation.'" frameborder="0" width="100%" height="100%" scrolling="auto" title="Etherpad" accesskey="p" ';
		if ($fromSakai) echo ' class="pad-sakai" ';
		echo ">Etherpad</iframe>\n<NOFRAMES>This interactive web page requires the use of frames.</NOFRAMES>\n</div>\n";
		

		
		//Add pad creator to page title - help disambiguate tabs 
		if (strlen($infoResult['authorName']) > 1) {
			echo "\n".'<script type="text/javascript">var TITLE = document.title;	document.title = TITLE + " - " + "'.$infoResult['padName'].'";	</script>';
			//echo '<script type="text/javascript">$("#pad-iframe").resizable({alsoResize : \'#pad\'});</script>';
		}
		
		
	}
}
//If there is no return on the URL (and the pad doesn't exist)
else{
	//Print out the header
	echo $giantHead;
	
	//Print out the error notification
	echo "<center><br /><h1>Nothing is here.</h1> <br />The URL you have entered is not valid. It may have been entered incorrectly or it may no longer exist.<br /><br /><br /></center></body></html>";
}

//Printout the footer if you're not in Sakai
if ($fromSakai!=TRUE){
	//Footer
	echo '<div id="footer">
		<div class="footerExtNav-etherpad">
		<div class="share-left">&nbsp;';
	
	if (!isset($_SERVER['HTTP_DNT'])){ //Share this
		echo'
		<script type="text/javascript" src="https://s7.addthis.com/js/200/addthis_widget.js"></script>
		<script type="text/javascript">var addthis_pub="mattclare";</script>
			<a href="https://www.addthis.com/bookmark.php?v=20" onmouseover="return addthis_open(this, \'\', \'[URL]\', \'[TITLE]\')" onmouseout="addthis_close()" onclick="return addthis_sendto()"><img src="/common/images/lg-share-en.gif" width="125" height="16" alt="Bookmark and Share" style="border:0;margin-left:6px;"/></a>';
			}
	else echo '<!-- You asked not to be tracked. We are not tracking you, or allowing others to -->';
			
	echo "\n</div>\n";
	condensed_footer();
}

echo "\n</body></html>";
?>
