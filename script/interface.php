<?php
/**
* SPDX-License-Identifier: GPL-3.0-or-later
* This file is part of Dolibarr module Subtotal
*/


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
	require_once __DIR__ . '/../class/subTotalJsonResponse.class.php';

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
			$jsonResponse = new SubTotalJsonResponse();
			_updateHideBlockData($jsonResponse);
			echo $jsonResponse->getJsonResponse();
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





/**
 * @param SubTotalJsonResponse $jsonResponse
 * @return bool|void
 */
function _updateHideBlockData($jsonResponse) {
	global  $db, $langs;

	$data = GETPOST('data', 'array');

	$element = $data['element'];
	$element_id = $data['element_id'];

	if(empty($element)){
		$jsonResponse->msg = $langs->trans('ElementMissing');
		$jsonResponse->result = 0;
		return false;
	}

	if(empty($element_id)){
		$jsonResponse->msg = $langs->trans('ElementIdMissing');
		$jsonResponse->result = 0;
		return false;
	}

	$titleStatusList = $data['titleStatusList'];


	if(!empty($titleStatusList)){
		$object = new $element($db); // TODO : repris du dev de base mais il faut ajouter de la vérification ça c'est pas normale

		if($object->fetch($element_id) <= 0){
			$jsonResponse->msg = $langs->trans('ErrorFetchingElement');
			$jsonResponse->result = 0;
			return false;
		}

		if($object->fetch($element_id) >0 && !empty($object->lines)) {
			foreach ($object->lines as $line) {
				if ($line->product_type != 9) { // si ce n'est pas du sous total, skip
					continue;
				}

				foreach($titleStatusList as $lineStatus){
					if ($line->id = $lineStatus['id']) {
						$line->fetch_optionals();
						$line->array_options['options_hideblock'] = intval($lineStatus['status']);
						$line->insertExtraFields();
					}
				}
			}
		}
	}

	$jsonResponse->result = 1;
}
