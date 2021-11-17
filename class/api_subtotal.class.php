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
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
		'socid'
	);

	/**
	 * @var Propal $propal {@type Propal}
	 */
	public $propal;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->propal = new Propal($this->db);
	}


	/**
	 * Get Total for a subtotal line
	 *
	 *  Valid values for elementtype<br>
	 * 	elementtype : [Propale, Order, OrderSupplier, Invoice, InvoiceSupplier] <br>
	 *  <hr><br>
	 *  idline : any valid line owned by elementtype<br>
	 *<br>
	 *  Return float
	 *
	 * @param       string		$elementtype			Ref object Propale, Order, OrderSupplier, Invoice or InvoiceSupplier
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

		if ($conf->subtotal->enabled){

			dol_include_once('/custom/subtotal/class/subtotal.class.php');

			dol_include_once('/custom/subtotal/lib/subtotal.lib.php');
			$total = 0;


			switch ($elementtype){

				case "Propale" :
					$objDet = new PropaleLigne($db);
					$res = $objDet->fetch($idline);

					if ($res  >  0) {

						$obj = new Propal($db);
						$resProp = $obj->fetch($objDet->fk_propal);
						if ($resProp > 0 ) {

							// la ligne est elle une ligne de Total ?
							if (TSubtotal::isSubtotal($objDet)){
								// lib  return SUM for this Total
								return   getTotalLineFromObject($obj,$objDet);
							}else{
								throw new RestException(500, "line is not a Sum");
							}


						}else{
							throw new RestException(500, "propal  '$objDet->fk_propal' not exist");
						}

					}else{
						throw new RestException(500, "propal line  '$idline' not exist");
					}

					break;
				case "Order" :

					$objDet = new OrderLine($db);
					$res = $objDet->fetch($idline);

					if ($res  >  0) {

						$obj = new Commande($db);
						$resProp = $obj->fetch($objDet->fk_commande);
						if ($resProp > 0 ) {

							// la ligne est elle une ligne de Total ?
							if (TSubtotal::isSubtotal($objDet)){
								// lib  return SUM for this Total
								return   getTotalLineFromObject($obj,$objDet);
							}else{
								throw new RestException(500, "line is not a Sum");
							}
						}else{
							throw new RestException(500, "order  '$objDet->fk_commande' not exist");
						}
					}else{
						throw new RestException(500, "order line  '$idline' not exist");
					}
					break;
				case "OrderSupplier" :

					$objDet = new CommandeFournisseurLigne($db);
					$res = $objDet->fetch($idline);

					//**************** fetch function *****************************************************************************
					/**
					 * the fetch function does not return the field rang
					 * we have to do this until fixed in core
					 */
					$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet WHERE rowid = '.$idline;
					$resql = $db->query($sql);
					if ($resql) {
							$objp = $this->db->fetch_object($resql);
							$objDet->rang = $objp->rang;
					}
					$this->db->free($resql);
					//*********************************************************************************************

					if ($res  >  0) {

						$obj = new CommandeFournisseur($db);
						$resProp = $obj->fetch($objDet->fk_commande);
						if ($resProp > 0 ) {

							// la ligne est elle une ligne de Total ?
							if (TSubtotal::isSubtotal($objDet)){
								// lib  return SUM for this Total
								return   getTotalLineFromObject($obj,$objDet);
							}else{
								throw new RestException(500, "line is not a Sum");
							}


						}else{
							throw new RestException(500, "order supplier  '$objDet->fk_commande' not exist");
						}

					}else{
						throw new RestException(500, "order supplier  line  '$idline' not exist");
					}
					break;
				case "Invoice" :

					$objDet = new FactureLigne($db);
					$res = $objDet->fetch($idline);

					if ($res  >  0) {

						$obj = new Facture($db);
						$resProp = $obj->fetch($objDet->fk_facture);
						if ($resProp > 0 ) {

							// la ligne est elle une ligne de Total ?
							if (TSubtotal::isSubtotal($objDet)){
								// lib  return SUM for this Total
								return   getTotalLineFromObject($obj,$objDet);
							}else{
								throw new RestException(500, "line is not a Sum");
							}


						}else{
							throw new RestException(500, "facture  '$objDet->fk_facture' not exist");
						}

					}else{
						throw new RestException(500, "facture line  '$idline' not exist");
					}
					break;
				case "InvoiceSupplier" :
					$objDet = new SupplierInvoiceLine($db);
					$res = $objDet->fetch($idline);

					if ($res  >  0) {

						$obj = new FactureFournisseur($db);
						$resProp = $obj->fetch($objDet->fk_facture_fourn);
						if ($resProp > 0 ) {

							// la ligne est elle une ligne de Total ?
							if (TSubtotal::isSubtotal($objDet)){
								// lib  return SUM for this Total
								return   getTotalLineFromObject($obj,$objDet);
							}else{
								throw new RestException(500, "line is not a Sum");
							}


						}else{
							throw new RestException(500, "invoice supplier  '$objDet->fk_facture' not exist");
						}

					}else{
						throw new RestException(500, "invoice supplier line  '$idline' not exist");
					}
					break;
				default :
					throw new RestException(500, "elementType '$elementtype' not supported");
			}

		}else{
			throw new RestException(500, "Module subtotal not activated");
		}

	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   object  $object    Object to clean
	 * @return    array    Array of cleaned object properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->note);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->address);

		return $object;
	}
}
