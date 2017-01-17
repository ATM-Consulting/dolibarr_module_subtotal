<?php
	require '../config.php';
	
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
		case 'updateLineNC':
			
			echo json_encode( _updateLineNC(GETPOST('element'), GETPOST('elementid'), GETPOST('lineid'), GETPOST('subtotal_nc')) );
			
			break;
		default:
			break;
	}
	
/**
 * Maj du bloc pour forcer le total_tva et total_ht à 0 et recalculer le total du document
 * 
 * @param	$lineid			= title lineid
 * @param	$subtotal_nc	0 = "Compris" prise en compte des totaux des lignes; 1 = "Non compris" non prise en compte des totaux du bloc
 */
function _updateLineNC($element, $elementid, $lineid, $subtotal_nc)
{
	global $db,$langs;
	
	$error = 0;
	$classname = ucfirst($element);
	$object = new $classname($db); // Propal | Commande | Facture
	$object->fetch($elementid);
	
	$db->begin();
	
	foreach ($object->lines as &$line)
	{
		if ($line->id == $lineid)
		{
			$line->array_options['options_subtotal_nc'] = $subtotal_nc;
			$res = TSubtotal::doUpdateLine($object, $line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->product_type, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options);
			if ($res <= 0) $error++;
			break;
		}
	}
	
	if (!$error)
	{
		$TLine = TSubtotal::getLinesFromTitleId($object, $lineid, false);
		if (!empty($TLine))
		{
			foreach ($TLine as &$line)
			{
				if (!empty($subtotal_nc))
				{
					$line->total_ht = $line->total_tva = $line->total_ttc = $line->total_localtax1 = $line->total_localtax2 = 
					$line->multicurrency_total_ht = $line->multicurrency_total_tva = $line->multicurrency_total_ttc = 0;
				
					$res = $line->update();
				}
				else
				{
					$res = TSubtotal::doUpdateLine($object, $line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->product_type, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
				}
				
				if ($res <= 0) $error++;
			}

			$res = $object->update_price(1);
			if ($res <= 0) $error++;
		}	
	}
	
	if (!$error)
	{
		setEventMessage($langs->trans('subtotal_update_nc_success'));
		$db->commit();
	}
	else
	{
		setEventMessage($langs->trans('subtotal_update_nc_error'), 'errors');
		$db->rollback();
	}
}