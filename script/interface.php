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
		case 'updateLine':
			
			echo json_encode( _updateLine(GETPOST('element'), GETPOST('elementid'), GETPOST('lineid')) );
			
			break;
		default:
			break;
	}
	
/**
 * Maj du bloc pour forcer le total_tva et total_ht à 0 et recalculer le total du document
 * 
 * @param	$lineid			= title lineid
 * @param	$subtotal_nc	0 = "Compris" prise en compte des totaux des lignes; 1 = "Non compris" non prise en compte des totaux du bloc; null = update de toutes les lignes 
 */
function _updateLineNC($element, $elementid, $lineid, $subtotal_nc=null)
{
	global $db,$langs;
	
	$db->begin();
		
	$error = 0;
	if (empty($element)) $error++;
	
	if (!$error)
	{
		$classname = ucfirst($element);
		$object = new $classname($db); // Propal | Commande | Facture
		$res = $object->fetch($elementid);
		if ($res < 0) $error++;
	}
	
	if (!$error)
	{
		foreach ($object->lines as &$line)
		{
			if ($line->id == $lineid && !is_null($subtotal_nc))
			{
				$line->array_options['options_subtotal_nc'] = $subtotal_nc;
				$res = TSubtotal::doUpdateLine($object, $line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->product_type, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options);
				if ($res <= 0) $error++;
			}
			elseif (!TSubtotal::isTitle($line) && !TSubtotal::isSubtotal($line))
			{
				$type_update = 'doUpdateLine';
				$TTitle = TSubtotal::getAllTitleFromLine($line);
				foreach ($TTitle as &$line_title)
				{
					if (!empty($line_title->array_options['options_subtotal_nc']))
					{
						$type_update = 'update';
						break;
					}
				}
				
				$total_ht = (double) $line->total_ht;
				if ($type_update == 'doUpdateLine')
				{
					if (empty($total_ht)) 
					{
						$res = TSubtotal::doUpdateLine($object, $line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->product_type, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
						if ($res <= 0) $error++;
					}
				}
				else // update
				{
					if (!empty($total_ht))
					{
						$line->total_ht = $line->total_tva = $line->total_ttc = $line->total_localtax1 = $line->total_localtax2 = 
							$line->multicurrency_total_ht = $line->multicurrency_total_tva = $line->multicurrency_total_ttc = 0;

						$res = $line->update();
						if ($res <= 0) $error++;
					}
				}
			}

			$res = $object->update_price(1);
			if ($res <= 0) $error++;

			if ($error) break;
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

function _updateLine($element, $elementid, $lineid)
{
	_updateLineNC($element, $elementid, $lineid);
}