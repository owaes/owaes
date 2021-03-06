<?php
	include "inc.default.php"; // should be included in EVERY file  
	$oSecurity = new security(TRUE); 
	$oLog = new log("page visit", array("url" => $oPage->filename())); 
	
	$iFixed = 5; // er worden direct x slots vastgelegd per student
	 
	$oList = new grouplist(); 
        
	$oDB = new database(); 
	
	$oDB->execute("select count(student) as aantal from tblStagemarktStudInschrijvingen where student = " . me() . ";"); 
	if ($oDB->get("aantal") > 0) redirect ("stagemarkt-info.php"); 
	
	if (isset($_POST["save"])) {
		
		$arSave = array(); 
		for ($i=1; $i<=5; $i++) $arSave["k$i"] = isset($_POST["k$i"]) ? intval($_POST["k$i"]) : 0; 
	
		$arOK = array();  
		$iCheck = 1;  
		
		for ($iCheck = 1; $iCheck <=5; $iCheck++) {
			if (count($arOK) < $iFixed){
				$iKeuze = $arSave["k" . $iCheck];
				$arSlots = array(1,2,3,4,5,6,7,8);
				$oDB->execute("select slot from tblStagemarktDates where bedrijf = $iKeuze; "); 
				while ($oDB->nextRecord()) { 
					if(($iKey = array_search($oDB->get("slot"), $arSlots)) !== false)  array_splice ($arSlots, $iKey, 1);  
				}
				foreach ($arOK as $iSlot=>$iBedrijf) if(($iKey = array_search($iSlot, $arSlots)) !== false) array_splice ($arSlots, $iKey, 1); 

				if (count($arSlots)>0)  $arOK[$arSlots[0]] = $iKeuze;  
				if (count($arSlots)<=1) $oDB->execute("insert ignore into tblStagemarktVolzet (bedrijfsid) values($iKeuze); ");  
			}
		}

		$arSave["student"] = me();     
		if (count(array_keys($arOK)) >= 1) {
			$oDB->execute("insert into tblStagemarktStudInschrijvingen (" . implode(",", array_keys($arSave)) . ") values (" . implode(",", array_values($arSave)) . "); ");
			foreach ($arOK as $iSlot=>$iBedrijf) {
				$oDB->execute("insert into tblStagemarktDates (bedrijf, student, slot) values(" . $iBedrijf . ", " . me() . ", " . $iSlot . "); "); 
			} 
			echo "Uw inschrijving werd opgeslaan. Onderstaande afspraken werden vastgelegd: ";
			foreach ($arOK as $iSlot=>$iBedrijf) {
				echo "<br />- Tijdslot $iSlot: " . group($iBedrijf)->naam(); 	
			}
			echo "<br />Let op: afhankelijk van het aantal inschrijvingen kunnen extra afspraken toegevoegd worden. De uiteindelijke planning kun je vinden op <a href=\"http://start.owaes.org/stagemarkt-info.php\">start.owaes.org/stagemarkt-info.php</a>"; 
		} else echo ("Uw inschrijving is niet gelukt. Refresh de pagina om opnieuw te proberen"); 
		exit(); 
	} 
	
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <?php
        	echo $oPage->getHeader(); 
		?>
        <style>
			.bedrijf { display: block; float: left; height: 100px; width: 50%; text-overflow: ellipsis; }
			.bedrijf .h100 {height: 90px; overflow: hidden; }
			.bedrijf h2 {font-size: 110%; font-weight: bold; margin: 0; display: inline-block;  }
			.bedrijf p.website {display: inline; margin-left: 15px; }
			.bedrijf p {margin: 0; }
			div.keuze {height: 65px; font-size: 35px; border: 2px dashed gray; display: inline-block; margin: 1.3%; padding: 10px; width: 17.4%; overflow: hidden; }
			div.keuze.full {font-size: 100%; font-weight: bold; background: white; }
			div.keuze.full img {width: 40px; float: right; }
			div.addknop img {opacity: 0.4; filter: alpha(opacity=40);  }
			.bedrijf.hover.ok div.addknop img {opacity: 1; filter: alpha(opacity=100);  }
			.bedrijf.hover.ok {cursor: pointer; } 
			div.info {padding: 0 15px; display: block; overflow: auto; }
			a.opslaan {float: right; }
			a.opslaan.disabled {color: #aaa; }
		</style>
        <script>
			$(document).ready(function(e) {
                $(".bedrijf").mouseover(function(){
					$(this).addClass("hover");	
				}).mouseout(function(){
					$(this).removeClass("hover"); 	
				})
				$(".bedrijf a.link").mouseover(function(){
					$(this).parentsUntil(".bedrijf").parent().removeClass("ok");	
				}).mouseout(function(){
					$(this).parentsUntil(".bedrijf").parent().addClass("ok"); 
				})
				$(".bedrijf.ok").click(function() {
					iID = $(this).attr("rel"); 
					if ($(this).hasClass("gekozen")) { 
						removeBedrijf(iID); 
					} else {
						addBedrijf(iID); 
					}
				})
				$("a.opslaan").click(function(){  
					arQRY = {"save": 1}; 
					$("div.keuze").each(function(){ 
						arQRY["k" + $(this).attr("nr")] = $(this).attr("rel");
					}) 
					$("#ModalOpgeslaan").modal({
						show: true, 
						keyboard: false
					}); 
					$("#ModalOpgeslaan .modal-body").load("stagemarktkeuze.php", arQRY); 
					return false; 
				})
            });
			
			function removeBedrijf(iID) {
				oBedrijf = $("div#group-" + iID); 
				$(".keuze[rel=" + iID + "]").html("").removeClass("full"); 
				$(oBedrijf).removeClass("gekozen"); 
				$(oBedrijf).find(".addknop img").attr("src", "img/plus.png"); 
				for (i=1; i<5; i++){
					if (!$(".keuze:eq(" + (i-1) + ")").hasClass("full") && $(".keuze:eq(" + i + ")").hasClass("full")) {
						$(".keuze:eq(" + (i-1) + ")").addClass("full").html($(".keuze:eq(" + i + ")").html()).attr("rel", $(".keuze:eq(" + i + ")").attr("rel")); 
						$(".keuze:eq(" + i + ")").removeClass("full").html(i+1);  
					}
				}
				$(".keuze:not(.full)").each(function(){$(this).html($(this).attr("nr"));});
				$("a.opslaan").addClass("disabled");  
			}
			
			function addBedrijf(iID) {
				oBedrijf = $("div#group-" + iID);  
				oHTML = $("<div />").append($("<img />").attr("src", $(oBedrijf).find("img").attr("src")).attr("alt", $(oBedrijf).find("h2").text())).append($(oBedrijf).find("h2").text());
				if ($("div.keuze:not(.full)").length > 0) { 
					$("div.keuze:not(.full):first").html(oHTML).addClass("full").attr("rel", iID); 
					$(oBedrijf).addClass("gekozen"); 
					$(oBedrijf).find(".addknop img").attr("src", "img/min.png");
				}
				
				if ($("div.keuze:not(.full)").length == 0) {
					$("a.opslaan").removeClass("disabled");
				}

			}
		</script>
    </head>
    <body id="users">
        <?php echo $oPage->startTabs(); ?> 
    	<div class="body content content-lists-users container">
        	
            	<div class="row">
					<?php  
                    echo $oSecurity->me()->html("user.html");
                    ?>
                </div>
                <div class="usersfromlist row sidecenterright"> 
                	<div class="info">
                    	<h2>Inschrijving Stagemarkt Roeselare 16 september 9 - 16u</h2>
                        <a href="#save" class="btn btn-default disabled opslaan">opslaan</a>
                    	<p>Selecteer 5 bedrijven voor een persoonlijk gesprek (in volgorde van voorkeur).</p>
                    </div>
					<?php  
                        for ($iKeuze = 1; $iKeuze <= 5; $iKeuze ++) {
                            echo ("<div class=\"keuze\" nr=\"$iKeuze\">$iKeuze</div>"); 	
                        } 
						
						$oDB = new database(); 
						$arLijst = array(); 
						$oDB->execute("select bedrijfsid from tblStagemarktVolzet; ");
						while ($oDB->nextRecord()) $arLijst[] = $oDB->get("bedrijfsid");  
						
                        foreach ($oList->getList() as $oGroep) { 
							if (!in_array($oGroep->id(), $arLijst)) echo "<div id=\"group-" . $oGroep->id() . "\" class=\"bedrijf ok\" rel=\"" . $oGroep->id() . "\">" . $oGroep->HTML("group_stagemarktkeuze.html") . "</div>";   
                        }
                    ?> 
                </div>
        	<?php echo $oPage->endTabs(); ?>
        </div>

		<div class="modal fade" id="ModalOpgeslaan">
            <form method="post" class="form-horizontal"> 
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"> 
                    <h4 class="modal-title">Stagemarkt inschrijving</h4>
                  </div>
                  <div class="modal-body">
                    <p>Bedankt, uw keuze wordt opgeslagen. </p> 
                  </div>
                  <div class="modal-footer"> 
                  	<a href="main.php" class="btn btn-default">OK</a>
                  </div>
                </div><!-- /.modal-content -->
              </div><!-- /.modal-dialog -->
         </form>
        </div><!-- /.modal -->
        <div class="footer">
        	<?php echo $oPage->footer(); ?>
        </div>
    </body>
</html>
