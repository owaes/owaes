<?php
	include "inc.default.php"; // should be included in EVERY file 

	$oSecurity = new security(FALSE); 
	$oMe = user(me()); 
	$strMelding = ""; 
	
	$strModal = template("modal.mailnotverified.html");
	//if (!$oMe->algemenevoorwaarden()) $strMelding = "<p>U kan pas deelnemen aan het platform van zodra een OWAES-medewerker een ondertekend exemplaar van de gebruikersvoorwaarden ontvangen heeft. </p>"; 
	
	$strModal->tag("email", $oMe->email()); 
	
	echo $strModal->html(); 
  
?>
