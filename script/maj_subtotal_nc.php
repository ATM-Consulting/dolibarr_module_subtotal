<?php
require '../config.php';

dol_include_once('/subtotal/lib/subtotal.lib.php');
dol_include_once('/subtotal/class/subtotal.class.php');
dol_include_once('/comm/propal/class/propal.class.php');
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/compta/facture/class/facture.class.php');
dol_include_once('/comm/propal/class/propal.class.php');

$limit = GETPOST('limit', 'int');

$sql = 'SELECT rowid';
$sql .= ' FROM '.MAIN_DB_PREFIX.'propal';
$sql .= ' WHERE total_ht + tva != total';
if(! empty($limit)) $sql .= ' LIMIT '.$limit;

$resql = $db->query($sql);
if($resql) {
	$db->begin();
	while($obj = $db->fetch_object($resql)) {
		$propal = new Propal($db);
		var_dump($obj->rowid);
		$propal->fetch($obj->rowid);

		foreach($propal->lines as &$l) {
			if(empty($l->array_options)) $l->fetch_optionals();
			if(! empty($l->array_options['options_subtotal_nc']) && ! TSubtotal::isModSubtotalLine($l)) {
				_updateLineNC($propal->element, $propal->id, $l->id, $l->array_options['options_subtotal_nc']);
			}
		}
	}
	$db->commit();
}