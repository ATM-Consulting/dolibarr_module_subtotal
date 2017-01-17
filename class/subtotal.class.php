<?php


class TSubtotal {
	
	static $module_number = 104777;
	
	static function addSubTotalLine(&$object, $label, $qty, $rang=-1) {
		
		if( (float)DOL_VERSION <= 3.4 ) {
			/**
			 * @var $object Facture
			 */

			if($object->element=='facture') $res =  $object->addline($object->id, $label, 0,$qty,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, TSubtotal::$module_number);
			/**
			 * @var $object Propal
			 */
			else if($object->element=='propal') $res =  $object->addline($object->id,$label, 0,$qty,0,0,0,0,0,'HT',0,0,9,-1, TSubtotal::$module_number);
			/**
			 * @var $object Commande
			 */
			else if($object->element=='commande') $res =  $object->addline($object->id,$label, 0,$qty,0,0,0,0,0,0,0,'HT',0,'','',9,-1, TSubtotal::$module_number);

		}
		else {
			/**
			 * @var $object Facture
			 */

			if($object->element=='facture') $res =  $object->addline($label, 0,$qty,0,0,0,0,0,'','',0,0,'','HT',0,9,$rang, TSubtotal::$module_number);
			/**
			 * @var $object Propal
			 */
			else if($object->element=='propal') $res = $object->addline($label, 0,$qty,0,0,0,0,0,'HT',0,0,9,$rang, TSubtotal::$module_number);
			/**
			 * @var $object Commande
			 */
			else if($object->element=='commande') $res =  $object->addline($label, 0,$qty,0,0,0,0,0,0,0,'HT',0,'','',9,$rang, TSubtotal::$module_number);
		}
	
	}

	public static function addTitle(&$object, $label, $level, $rang=-1)
	{
		self::addSubTotalLine($object, $label, $level, $rang);
	}
	
	public static function addTotal(&$object, $label, $level, $rang=-1)
	{
		self::addSubTotalLine($object, $label, (100-$level), $rang);
	}

	public static function getAllTitleFromDocument(&$object)
	{
		$TRes = array();
		if (!empty($object->lines))
		{
			foreach ($object->lines as $k => &$line)
			{
				if ($line->product_type == 9 && $line->qty < 10)
				{
					$TRes[] = $line;
				}
			}
		}
		
		return $TRes;
	}
	
	
	public static function getOrderIdFromLineId(&$db, $fk_commandedet)
	{
		if (empty($fk_commandedet)) return false;
		
		$sql = 'SELECT fk_commande FROM '.MAIN_DB_PREFIX.'commandedet WHERE rowid = '.$fk_commandedet;
		$resql = $db->query($sql);
		
		if ($resql && ($row = $db->fetch_object($resql))) return $row->fk_commande;
		else return false;
	}
	
	public static function getLastLineOrderId(&$db, $fk_commande)
	{
		if (empty($fk_commande)) return false;
		
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commandedet WHERE fk_commande = '.$fk_commande.' ORDER BY rang DESC LIMIT 1';
		$resql = $db->query($sql);
		
		if ($resql && ($row = $db->fetch_object($resql))) return $row->rowid;
		else return false;
	}
	
	public static function getParentTitleOfLine(&$object, $i)
	{
		if ($i <= 0) return false;
		
		$skip_title = 0;
		// Je parcours les lignes précédentes
		while ($i--)
		{
			$line = &$object->lines[$i];
			// S'il s'agit d'un titre
			if ($line->product_type == 9 && $line->qty <= 10 && $line->qty >= 1)
			{
				if ($skip_title)
				{
					$skip_title--;
					continue;
				}
				
				//@INFO J'ai ma ligne titre qui contient ma ligne, par contre je check pas s'il y a un sous-total
				return $line;
				break;
			}
			elseif ($line->product_type == 9 && $line->qty >= 90 && $line->qty <= 99)
			{
				// Il s'agit d'un sous-total, ça veut dire que le prochain titre théoriquement doit être ignorer (je travail avec un incrément au cas ou je croise plusieurs sous-totaux)
				$skip_title++;
			}
		}

		return false;
	}
	
	public static function isTitle(&$line)
	{
		return $line->special_code == self::$module_number && $line->product_type == 9 && $line->qty >= 1 && $line->qty <= 9;
	}
	
	public static function isSubtotal(&$line)
	{
		return $line->special_code == self::$module_number && $line->product_type == 9 && $line->qty <= 99 && $line->qty >= 90;
	}

	public static function duplicateLines(&$object, $lineid, $withBlockLine=false)
	{
		global $db,$user,$conf;

		if ($object->statut == 0  && $user->rights->{$object->element}->creer && !empty($conf->global->SUBTOTAL_ALLOW_DUPLICATE_BLOCK))
		{
			$TLine = self::getLinesFromTitleId($object, $lineid, $withBlockLine);

			if (!empty($TLine))
			{
				$object->db->begin();
				$res = 1;

				foreach ($TLine as $line)
				{
					// TODO refactore avec un doAddLine sur le même schéma que le doUpdateLine
					switch ($object->element) {
						case 'propal':
							//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $fk_product=0, $remise_percent=0.0, $price_base_type='HT', $pu_ttc=0.0, $info_bits=0, $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=0, $pa_ht=0, $label='',$date_start='', $date_end='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0)
							$res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, 'HT', 0, $line->info_bits, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->origin, $line->origin_id);
							break;
						case 'commande':
							//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0, $date_start='', $date_end='', $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0)
							$res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->date_start, $line->date_end, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->array_options, $line->fk_unit, $line->origin, $line->origin_id);
							break;
						case 'facture':
							//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id='', $fk_unit = null
							$res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->date_start, $line->date_end, 0, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->product_type, -1, $line->special_code, $line->origin, $line->origin_id, $line->fk_parent_line, $line->fk_fournprice, $line->pa_ht, $line->label, $line->array_options, $line->situation_percent, $line->fk_prev_id, $line->fk_unit);
							break;
					}

					// Error from addline
					if ($res <= 0) break;
				}

				if ($res > 0)
				{
					$object->db->commit();
					return count($TLine);
				}
				else
				{
					$object->db->rollback();
					return -1;
				}
			}

			return 0;
		}
	}
	
	public static function getLinesFromTitle(&$object, $key_trad, $level=1, $under_title='', $withBlockLine=false, $key_is_id=false)
	{
		global $langs;
		
		// Besoin de comparer sur les 2 formes d'écriture
		if (!$key_is_id) $TTitle_search = array($langs->trans($key_trad), $langs->transnoentitiesnoconv($key_trad));
		
		$TTitle_under_search = array();
		if (!empty($under_title)) $TTitle_under_search = array($langs->trans($under_title), $langs->transnoentitiesnoconv($under_title));
		
		$TLine = array();
		$add_line = false;
		$under_title_found=false;
		
		foreach ($object->lines as $key => &$line)
		{
			if (!$under_title_found && !empty($TTitle_under_search))
			{
				if ($line->product_type == 9 && (in_array($line->desc, $TTitle_under_search) || in_array($line->label, $TTitle_under_search)) ) $under_title_found = true;
			}
			else
			{
				if ( ($key_is_id && $line->id == $key_trad) || (!$key_is_id && $line->product_type == 9 && $line->qty == $level && (in_array($line->desc, $TTitle_search) || in_array($line->label, $TTitle_search) )))
				{
					if ($key_is_id) $level = $line->qty;
					
					$add_line = true;
					if ($withBlockLine) $TLine[] = $line;
					continue;
				}
				elseif ($add_line && $line->product_type == 9 && (100 - $line->qty == $level) ) // Si on tombe sur un sous-total, il faut que ce soit un du même niveau que le titre
				{
					if ($withBlockLine) $TLine[] = $line;
					break;
				}
				
				if ($add_line)
				{
					if (!$withBlockLine && (self::isTitle($line) || self::isSubtotal($line)) ) continue;
					else $TLine[] = $line;
				}
			}
		}
		
		return $TLine;
	}
	
	public static function getLinesFromTitleId(&$object, $lineid, $withBlockLine=false)
	{
		return self::getLinesFromTitle($object, $lineid, '', '', $withBlockLine, true);
	}
	
	public static function doUpdateLine(&$object, $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $type, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_options=0, $situation_percent=0, $fk_unit = null)
	{
		$res = 0;
		$object->db->begin();
		
		switch ($object->element) 
		{
			case 'propal':
				$res = $object->updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $desc, $price_base_type, $info_bits, $special_code, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $type, $date_start, $date_end, $array_options, $fk_unit);
				break;
			
			case 'commande':
				$res = $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $date_start, $date_end, $type, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $special_code, $array_options, $fk_unit);
				break;
			
			case 'facture':
				$res = $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $type, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $special_code, $array_options, $situation_percent, $fk_unit);
				break;
		}
		
		if ($res <= 0) $object->db->rollback();
		else $object->db->commit();
		
		return $res;
	}
	
	public static function getAllTitleFromLine(&$origin_line, $reverse = false)
	{
		global $db;
		
		$TTitle = array();
		if ($origin_line->element == 'propaldet')
		{
			$object = new Propal($db);
			$object->fetch($origin_line->fk_propal);
		}
		else if ($origin_line->element == 'commandedet')
		{
			$object = new Commande($db);
			$object->fetch($origin_line->fk_commande);
		}
		else if ($origin_line->element == 'facturedet')
		{
			$object = new Facture($db);
			$object->fetch($origin_line->fk_facture);
		}
		else
		{
			return $TTitle;
		}
		
		// Récupération de la position de la ligne
		$i = 0;
		foreach ($object->lines as &$line)
		{
			if ($origin_line->id == $line->id) break;
			else $i++;
		}
		
		$i--; // Skip la ligne d'origine
		
		// Si elle n'est pas en 1ère position, alors on cherche des titres au dessus
		if ($i > 0)
		{
			$next_title_lvl_to_skip = 0;
			for ($y = $i; $y >= 0; $y--)
			{
				// Si je tombe sur un sous-total, je récupère son niveau pour savoir quel est le prochain niveau de titre que doit ignorer
				if (self::isSubtotal($object->lines[$y]))
				{
					$next_title_lvl_to_skip = self::getNiveau($object->lines[$y]);
				}
				elseif (self::isTitle($object->lines[$y]))
				{
					if ($object->lines[$y]->qty == $next_title_lvl_to_skip)
					{
						$next_title_lvl_to_skip = 0;
						continue;
					}
					else
					{
						$TTitle[$object->lines[$y]->id] = $object->lines[$y];
						
						if ($object->lines[$y]->qty == 1) break;
					}
				}
			}
		}
		
		if ($reverse) $TTitle = array_reverse($TTitle, true);
		
		return $TTitle;
	}
	
	public static function getNiveau(&$line)
	{
		if (self::isTitle($line)) return $line->qty;
		elseif (self::isSubtotal($line)) return 100 - $line->qty;
		else return 0;
	}
}
