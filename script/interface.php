<?php
	require '../config.php';
	
	dol_include_once('/subtotal/lib/subtotal.lib.php');
	dol_include_once('/subtotal/class/subtotal.class.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/commande/class/commande.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	
	$get=GETPOST('get');
	$set=GETPOST('set');
	
	switch ($get) {
		default:
			break;
	}
	
	switch ($set) {
		case 'updateLineNCFromLine': // Gestion du Compris/Non Compris via les lignes directement
			
			echo json_encode( _updateLineNCFromLine(GETPOST('element'), GETPOST('elementid'), GETPOST('lineid'), GETPOST('subtotal_nc')) );
			
			break;
		case 'updateLineNC': // Gestion du Compris/Non Compris via les titres
			
			echo json_encode( _updateLineNC(GETPOST('element'), GETPOST('elementid'), GETPOST('lineid'), GETPOST('subtotal_nc')) );
			
			break;
		case 'updateLine':
			
			echo json_encode( _updateLine(GETPOST('element'), GETPOST('elementid'), GETPOST('lineid')) );
			
			break;
		default:
			break;
	}
