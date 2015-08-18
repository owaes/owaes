<?php
	include "inc.default.php"; // should be included in EVERY file 

	$oSecurity = new security(FALSE); 
	
	$strKey = $_GET["k"]; 
	$iUser = intval($_GET["u"]); 
	
	$oUser = user($iUser); 
	$oUser->validateEmail($strKey); 
	
	if (!$oUser->algemenevoorwaarden()) {
		if ($oUser->dienstverlener()->id() > 0) { // dienstverlener geselecteerd
		// ER 	echo "dwel diensteverlernerne"; 
			$oDienstverlener = $oUser->dienstverlener()->admin();  
			$oAction = new action($oDienstverlener->id()); 
			$oAction->type("validateuser");  
			$oAction->data("user", $iUser); 
			$oAction->tododate(owaestime()); 
			$oAction->update();  
	
			$oMail = new email(); 
				$oDienstverlener->unlocked(TRUE); 
				$oMail->setTo($oDienstverlener->email(), $oDienstverlener->getName());
				$oDienstverlener->unlocked(FALSE); 
				$oMail->template("mailtemplate.html"); 
				
				if (intval($oUser->data("stagemarkt")) > 0) {
					$strMailBody = $oUser->HTML("mail.stagemarkt.html"); 
					$oDB = new database(); 
					$oDB->execute("select * from tblStagemarkt where id = " . intval($oUser->data("stagemarkt")) . ";"); 
					$strMailBody = str_replace("[groepsnaam]", $oDB->get("groepsnaam"), $strMailBody);  
					$strMailBody = str_replace("[groepsdescription]", $oDB->get("description"), $strMailBody);  
					$strMailBody = str_replace("[interesse]", $oDB->get("interesse"), $strMailBody);  
					$strMailBody = str_replace("[logo]", (($oDB->get("logo")!="")?"<a href='" . fixpath($oDB->get("logo"), TRUE) . "' target='_blank'>bekijk hier</a>":"niet toegevoegd"), $strMailBody);  
				} else {
					$strMailBody = $oUser->HTML("mail.clientingeschreven.html"); 
					$strMailBody = str_replace("[dienstverlener]", $oUser->dienstverlener()->naam(), $strMailBody); 
				} 
				
				$oMail->setBody($strMailBody);   
				$oMail->setSubject("nieuwe OWAES inschrijving via " . $oUser->dienstverlener()->naam()); 
			$oMail->send(); 
			//vardump($oMail); 
		} else {
			$arAdmins = new userlist(); 
			$arAdmins->filter("admin"); 
			foreach ($arAdmins->getList() as $oAdmin) {
				$oAction = new action($oAdmin->id()); 
				$oAction->type("validateuser");  
				$oAction->data("user", $iUser); 
				$oAction->tododate(owaestime()); 
				$oAction->update();  
			} 
		}
	}
	
	redirect("index.php"); 