<?php

class TSubtotal extends TObjetStd
{
	static function addSubTotalLine(&$object, $label, $qty, $module_number = 104777) {
		if( (float)DOL_VERSION <= 3.4 ) {
			/**
			 * @var $object Facture
			 */
			if($object->element=='facture') $object->addline($object->id, $label, 0,$qty,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, $module_number);
			/**
			 * @var $object Propal
			 */
			else if($object->element=='propal') $object->addline($object->id,$label, 0,$qty,0,0,0,0,0,'HT',0,0,9,-1, $module_number);
			/**
			 * @var $object Commande
			 */
			else if($object->element=='commande') $object->addline($object->id,$label, 0,$qty,0,0,0,0,0,0,0,'HT',0,'','',9,-1, $module_number);
		}
		else {
			/**
			 * @var $object Facture
			 */
			if($object->element=='facture') $object->addline($label, 0,$qty,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, $module_number);
			/**
			 * @var $object Propal
			 */
			else if($object->element=='propal') $object->addline($label, 0,$qty,0,0,0,0,0,'HT',0,0,9,-1, $module_number);
			/**
			 * @var $object Commande
			 */
			else if($object->element=='commande') $object->addline($label, 0,$qty,0,0,0,0,0,0,0,'HT',0,'','',9,-1, $module_number);
		}
	}	
}
