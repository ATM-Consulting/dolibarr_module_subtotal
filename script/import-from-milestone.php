<?php

	require('../config.php');

	$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet 
		SET special_code=104777
		, qty=1
	 WHERE special_code=1790";
	 
	 print $sql.";<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet 
		SET fk_parent_line=NULL
	 WHERE 1";
	 
	 print $sql.";<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet 
		SET special_code=104777
		,qty=1
	 WHERE special_code=1790";
	 
	 print $sql.";<br />";

	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet 
		SET fk_parent_line=NULL
	 WHERE 1";
	 
	 print $sql.";<br />";

	
	$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet 
		SET special_code=104777
		,qty=1
	 WHERE special_code=1790";
	 
	 print $sql.";<br />";
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet 
		SET fk_parent_line=NULL
	 WHERE 1";


	$res = $db->query("SELECT DISTINCT(fk_propal) as fk_propal FROM ".MAIN_DB_PREFIX."propaldet WHERE special_code=104777");
	while($obj = $db->fetch_object($res)) {
		$resLine = $db->query("SELECT rowid FROM  ".MAIN_DB_PREFIX."propaldet WHERE fk_propal=".$obj->fk_propal." ORDER BY rang ");
		$k = 1;
		while($objLine = $db->fetch_object($resLine)) {

			$sql="UPDATE ".MAIN_DB_PREFIX."propaldet SET rang=".$k.",fk_parent_line=NULL WHERE rowid=".$objLine->rowid;

			 print $sql.";<br />";
			$k++;
		}

	}


        $res = $db->query("SELECT DISTINCT(fk_facture) as fk_facture FROM ".MAIN_DB_PREFIX."facturedet WHERE special_code=104777");
        while($obj = $db->fetch_object($res)) {
                $resLine = $db->query("SELECT rowid FROM  ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$obj->fk_propal." ORDER BY rang ");
                $k = 1;
                while($objLine = $db->fetch_object($resLine)) {

                        $sql="UPDATE ".MAIN_DB_PREFIX."facturedet SET rang=".$k.",fk_parent_line=NULL WHERE rowid=".$objLine->rowid;

                         print $sql.";<br />";
                        $k++;
                }

        }

        $res = $db->query("SELECT DISTINCT(fk_commande) as fk_commande FROM ".MAIN_DB_PREFIX."commandedet WHERE special_code=104777");
        while($obj = $db->fetch_object($res)) {
                $resLine = $db->query("SELECT rowid FROM  ".MAIN_DB_PREFIX."commandedet WHERE fk_commande=".$obj->fk_propal." ORDER BY rang ");
                $k = 1;
                while($objLine = $db->fetch_object($resLine)) {

                        $sql="UPDATE ".MAIN_DB_PREFIX."commandedet SET rang=".$k.",fk_parent_line=NULL WHERE rowid=".$objLine->rowid;

                         print $sql.";<br />";
                        $k++;
                }

        }


	 print $sql.";<br />";
	
