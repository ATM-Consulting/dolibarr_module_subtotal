<?php

	if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
	if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);

	require '../config.php';

	dol_include_once('/subtotal/lib/subtotal.lib.php');
	dol_include_once('/subtotal/class/subtotal.class.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/commande/class/commande.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	dol_include_once('/fourn/class/fournisseur.commande.class.php');
	dol_include_once('/supplier_proposal/class/supplier_proposal.class.php');
	dol_include_once('/fourn/class/fournisseur.facture.class.php');

	$get=GETPOST('get', 'none');
	$set=GETPOST('set', 'none');

	switch ($get) {
		default:
			break;
	}

	switch ($set) {
		case 'updateLineNC': // Gestion du Compris/Non Compris via les titres et/ou lignes
			echo json_encode( _updateLineNC(GETPOST('element', 'none'), GETPOST('elementid', 'none'), GETPOST('lineid', 'none'), GETPOST('subtotal_nc', 'none')) );

			break;
		default:
			break;
	}
