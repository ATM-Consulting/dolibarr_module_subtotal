<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2020   Thibault FOUCART   		<support@ptibogxiv.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';


/**
 * API class for subtotal
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Subtotal extends DolibarrApi
{


	const TYPE_PROPAL = 'propal';
	const TYPE_ORDER ='order';
	const TYPE_ORDER_SUPPLIER ='ordsup';
	const TYPE_INVOICE ='invoice';
	const TYPE_INVOICE_SUPPLIER ='invsup';

	const OBJ_PROPAL = 'Propal';
	const OBJ_ORDER ='Commande';
	const OBJ_ORDER_SUPPLIER ='CommandeFournisseur';
	const OBJ_INVOICE ='Facture';
	const OBJ_INVOICE_SUPPLIER ='FactureFournisseur';

	const OBJ_PROPAL_LINE = 'PropaleLigne';
	const OBJ_ORDER_LINE = 'OrderLine';
	const OBJ_ORDER_SUPPLIER_LINE = 'CommandeFournisseurLigne';
	const OBJ_INVOICE_LINE = 'FactureLigne';
	const OBJ_INVOICE_SUPPLIER_LINE = 'SupplierInvoiceLine';

	const FK_PROPAL ="fk_propal";
	const FK_ORDER ="fk_commande";
	const FK_INVOICE ="fk_facture";
	const FK_INVOICE_SUPPLIER ='fk_facture_fourn';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
	}


	/**
	 * Get Total for a subtotal line
	 *
	 *  Valid values for elementtype<br>
	 * 	elementtype : [propal, order, ordsup, invoice, invsup] <br>
	 *  propal : propale <br>
	 *  order  : order<br>
	 *  invsup : invoice supplier<br>
	 *  ordsup : order supplier <br>
	 *  invoice: invoice<br>
	 *  <hr><br>
	 *  idline : any valid line owned by elementtype<br>
	 *<br>
	 *  Return float
	 *
	 * @param       string		$elementtype			Ref object propal, order, ordsup, invoice, invsup
	 * @param       int         $id_line  				id line
	 * @return 	array|mixed     data without useless information
	 *
	 * @url GET    {elementtype}/{idline}
	 *
	 * @throws 	RestException
	 */
	public function getTotalLine($elementtype, $idline = 1)
	{
		global $db,$conf;

		if (isModEnabled('subtotal')){

			dol_include_once('/custom/subtotal/class/subtotal.class.php');
			dol_include_once('/custom/subtotal/lib/subtotal.lib.php');

			$total = 0;
			switch ($elementtype){

				case self::TYPE_PROPAL :
					return $this->_getTotal($db, $idline,self::OBJ_PROPAL_LINE,self::OBJ_PROPAL);

				case self::TYPE_ORDER :
					return $this->_getTotal($db, $idline,self::OBJ_ORDER_LINE,self::OBJ_ORDER);

				case self::TYPE_ORDER_SUPPLIER:
					return $this->_getTotal($db, $idline,self::OBJ_ORDER_SUPPLIER_LINE,self::OBJ_ORDER_SUPPLIER);

				case self::TYPE_INVOICE :
					return $this->_getTotal($db, $idline,self::OBJ_INVOICE_LINE, self::OBJ_INVOICE);

				case self::TYPE_INVOICE_SUPPLIER :
					return $this->_getTotal($db, $idline,self::OBJ_INVOICE_SUPPLIER_LINE,self::OBJ_INVOICE_SUPPLIER);

				default :
					throw new RestException(500, "elementType '$elementtype' not supported");
			}

		}else{
			throw new RestException(500, "Module subtotal not activated");
		}

	}

	/**
	 * @param DoliDB $db
	 * @param $idline
	 * @param $objectLine
	 * @param $objectMaster
	 * @return array|float|int
	 * @throws RestException
	 */
	protected function _getTotal(DoliDB $db, $idline,$objectLine,$objectMaster)
	{
		$objDet = new $objectLine($db);

		$res = $objDet->fetch($idline);

		if ($objectMaster == self::OBJ_ORDER_SUPPLIER) {

			//**************** fetch function *****************************************************************************
			/**
			 * the fetch function does not return the field rang
			 * we have to do this until fixed in core
			 */
			if (empty($objDet->rang)) {

				$sql = 'SELECT rang FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet WHERE rowid = ' . $idline;
				$resql = $db->query($sql);
				if ($resql) {
					$objp = $this->db->fetch_object($resql);
					$objDet->rang = $objp->rang;
				}
				$this->db->free($resql);
			}

			//*********************************************************************************************
		}

		if ($res > 0) {

			$obj = new $objectMaster($db);
			$resMaster = $obj->fetch($objDet->{$this->_getFkFieldName($objectLine)});
			if ($resMaster > 0) {

				// la ligne est elle une ligne de Total ?
				if (TSubtotal::isSubtotal($objDet)) {
					// lib  return SUM for this Total
					return getTotalLineFromObject($obj, $objDet);
				} else {
					throw new RestException(500, "line is not a Sum");
				}

			} else {
				throw new RestException(500, " '$objectMaster'  '$objDet->fk_propal' not exist");
			}

		} else {
			throw new RestException(500, " '$objectLine' line  '$idline' not exist");
		}
		return 0;
	}

	/**
	 * @param $objectLine
	 * @return string|void
	 */
	protected function _getFkFieldName($objectLine){

		switch ($objectLine){

			case self::OBJ_PROPAL_LINE  :
				return self::FK_PROPAL;

			case self::OBJ_ORDER_LINE  :
				return self::FK_ORDER;

			case self::OBJ_ORDER_SUPPLIER_LINE  :
				return self::FK_ORDER;

			case self::OBJ_INVOICE_LINE  :
				return self::FK_INVOICE;

			case self::OBJ_INVOICE_SUPPLIER_LINE  :
				return self::FK_INVOICE_SUPPLIER;
		}

	}
}
