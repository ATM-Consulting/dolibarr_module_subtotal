<?php
/**
* SPDX-License-Identifier: GPL-3.0-or-later
* This file is part of Dolibarr module Subtotal
*/


	require('../config.php');

	$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet 
		SET special_code=104777
		, label=(SELECT label FROM ".MAIN_DB_PREFIX."milestone WHERE elementtype='propal' AND fk_element=".MAIN_DB_PREFIX."propaldet.rowid)
		, qty=1
	 WHERE special_code=1790";
	 
	 print $sql.";<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet 
		SET fk_parent_line=0
	 WHERE 1";
	 
	 print $sql.";<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet 
		SET special_code=104777
		, label=(SELECT label FROM ".MAIN_DB_PREFIX."milestone WHERE elementtype='facture' AND fk_element=".MAIN_DB_PREFIX."facturedet.rowid)
		,qty=1
	 WHERE special_code=1790";
	 
	 print $sql.";<br />";

	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet 
		SET fk_parent_line=0
	 WHERE 1";
	 
	 print $sql.";<br />";

	
	$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet 
		SET special_code=104777
		, label=(SELECT label FROM ".MAIN_DB_PREFIX."milestone WHERE elementtype='commande' AND fk_element=".MAIN_DB_PREFIX."commandedet.rowid)
		,qty=1
	 WHERE special_code=1790";
	 
	 print $sql.";<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet 
		SET fk_parent_line=0
	 WHERE 1";
	 
	 print $sql.";<br />";
	
