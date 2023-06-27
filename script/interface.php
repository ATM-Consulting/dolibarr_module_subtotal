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
		//récupération des lignes contenues dans un titre sous total en fonction d'un élément et de la ligne de titre concernée
		case 'getLinesFromTitle':

			global $db;

			$element = GETPOST('element', 'none');
			$element_id = GETPOST('elementid', 'none');
			$id_line = GETPOST('lineid', 'int');

			$object = new $element($db);
			$object->fetch($element_id);

			if(!empty($object->lines)) {
				$TRes = array();

				foreach ($object->lines as $line) {
					if ($line->id == $id_line) {
						$title_line = $line;
						$subline_line = TSubtotal::getSubLineOfTitle($object, $title_line->rang);
						break;
					}
				}

				foreach ($object->lines as $line) {

					$parent_line = TSubtotal::getParentTitleOfLine($object, $line->rang);

					if(!empty($subline_line)) {
						if ($line->product_type != 9 && $line->rang > $title_line->rang && $line->rang < $subline_line->rang) {
							$TRes[$parent_line->id][] = $line->id;
						}
					} else {
						if ($line->product_type != 9 && $line->rang > $title_line->rang) {
							$TRes[$parent_line->id][] = $line->id;
						}
					}
				}
			}

			echo json_encode($TRes);
			break;
		default:
			break;
	}

	switch ($set) {
		case 'updateLineNC': // Gestion du Compris/Non Compris via les titres et/ou lignes
			echo json_encode( _updateLineNC(GETPOST('element', 'none'), GETPOST('elementid', 'none'), GETPOST('lineid', 'none'), GETPOST('subtotal_nc', 'none')) );

			break;

		//Mise à jour de la donnée "hideblock" sur une ligne titre afin de savoir si le bloc doit être caché ou pas
		case 'update_hideblock_data':

			global $db;

			$id_line = GETPOST('lineid', 'int');
			$element = GETPOST('element', 'alphanohtml');
			$element_id = GETPOST('elementid', 'int');
			$value = GETPOST('value', 'int');

			$object = new $element($db);
			$object->fetch($element_id);

			if(!empty($object->lines)) {
				foreach ($object->lines as $line) {
					if ($line->id = $id_line) {
						$line->fetch_optionals();
						$line->array_options['options_hideblock'] = $value;
						$line->insertExtraFields();
					}
				}
			}

			echo json_encode($id_line);
			break;

		case 'updateall_hideblock_data' :
			$element = GETPOST('element', 'alphanohtml');
			$element_id = GETPOST('elementid', 'int');
			$value = GETPOST('value', 'int');

			$object = new $element($db);
			$object->fetch($element_id);

			if(!empty($object->lines)) {
				foreach ($object->lines as $line) {
					if ($line->product_type == 9) {
						$line->fetch_optionals();
						$line->array_options['options_hideblock'] = $value;
						$line->insertExtraFields();
					}
				}
			}

			break;
		default:
			break;
	}
