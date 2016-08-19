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
}
