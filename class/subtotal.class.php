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
	
	public function getLastLineOrderId(&$db, $fk_commande)
	{
		if (empty($fk_commande)) return false;
		
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commandedet WHERE fk_commande = '.$fk_commande.' ORDER BY rang DESC LIMIT 1';
		$resql = $db->query($sql);
		
		if ($resql && ($row = $db->fetch_object($resql))) return $row->rowid;
		else return false;
	}
	
	public function getParentTitleOfLine(&$object, $i)
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
	
}
