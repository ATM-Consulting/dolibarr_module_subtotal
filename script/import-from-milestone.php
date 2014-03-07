<?php

	require('../config.php');

	$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet 
		SET special_code=104777
		, label=(SELECT label FROM ".MAIN_DB_PREFIX."milestone WHERE elementtype='propal' AND fk_element=".MAIN_DB_PREFIX."propaldet.rowid)
	 WHERE special_code=1790";
	 
	 print $sql."<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet 
		SET special_code=104777
		, label=(SELECT label FROM ".MAIN_DB_PREFIX."milestone WHERE elementtype='facture' AND fk_element=".MAIN_DB_PREFIX."facturedet.rowid)
	 WHERE special_code=1790";
	 
	 print $sql."<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet 
		SET special_code=104777
		, label=(SELECT label FROM ".MAIN_DB_PREFIX."milestone WHERE elementtype='commande' AND fk_element=".MAIN_DB_PREFIX."commandedet.rowid)
	 WHERE special_code=1790";
	 
	 print $sql."<br />";
	
	