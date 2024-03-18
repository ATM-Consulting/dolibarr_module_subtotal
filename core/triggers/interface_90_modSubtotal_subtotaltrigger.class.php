<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		core/triggers/interface_99_modMyodule_Mytrigger.class.php
 * 	\ingroup	titre
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class Interfacesubtotaltrigger extends DolibarrTriggers
{
    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are subtotal functions.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'subtotal@subtotal';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

	public function addToBegin(&$parent, &$object, $rang)
	{
		foreach ($parent->lines as &$line)
		{
			// Si (ma ligne courrante n'est pas celle que je viens d'ajouter) et que (le rang courrant est supérieure au rang du titre)
			if ($object->id != $line->id && $line->rang > $rang)
			{
				// Update du rang de toutes les lignes suivant mon titre
				$parent->updateRangOfLine($line->id, $line->rang+1);
			}
		}

		// Update du rang de la ligne fraichement ajouté pour la déplacer sous mon titre
		$parent->updateRangOfLine($object->id, $rang+1);
		$object->rang = $rang+1;
	}

	public function addToEnd(&$parent, &$object, $rang)
	{
		$title_level = -1;
		$subtotal_line_found = false;
		foreach ($parent->lines as $k => &$line)
		{
			if ($line->rang < $rang) continue;
			elseif ($line->rang == $rang) // Je suis sur la ligne de titre où je souhaite ajouter ma nouvelle ligne en fin de bloc
			{
				$title_level = $line->qty;
			}
			elseif (!$subtotal_line_found && $title_level > -1 && ($line->qty == 100 - $title_level)) // Le level de mon titre a été trouvé avant, donc maintenant je vais m'arrêter jusqu'à trouver un sous-total
			{
				$subtotal_line_found = true;
				$rang = $line->rang;
			}


			if ($subtotal_line_found)
			{
				$parent->updateRangOfLine($line->id, $line->rang+1);
			}
		}

		if ($subtotal_line_found)
		{
			$parent->updateRangOfLine($object->id, $rang);
			$object->rang = $rang;
		}
	}

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
		global $user;
       #COMPATIBILITÉ V16
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


        if ($action == 'LINEBILL_UPDATE'){
			$action = 'LINEBILL_MODIFY';
		}

		if ($action == 'LINEORDER_UPDATE'){
			$action == 'LINEORDER_MODIFY';
		}

		if ($action == 'LINEBILL_UPDATE'){
			$action = 'LINEBILL_MODIFY';
		}

		if ($action == 'LINEBILL_SUPPLIER_UPDATE'){
			$action = 'LINEBILL_SUPPLIER_MODIFY';
		}
		/* Refer to issue #379 */
		if($action == 'LINEBILL_INSERT'){
			static $TInvoices = array();
			if ($TInvoices[$object->fk_facture] === null) {
				$staticInvoice = new Facture($this->db);
				if ($staticInvoice->fetch($object->fk_facture) < 0){
					$object->error = $staticInvoice->error;
					$object->errors []= $staticInvoice->errors;
					return -1;
				}
				$isEligible = $staticInvoice->type == Facture::TYPE_DEPOSIT && GETPOST('typedeposit', 'aZ09') == "variablealllines";
				$TInvoices[$object->fk_facture] = $isEligible;
			}
			if ($TInvoices[$object->fk_facture]) {
				if (!empty($object->origin) && !empty($object->origin_id) && $object->special_code == TSubtotal::$module_number){
					$valuedeposit = price2num(str_replace('%', '', GETPOST('valuedeposit', 'alpha')), 'MU');
					$object->qty = 100 * $object->qty / $valuedeposit;
					if ($object->update('', 1) < 0){
						$object->error = $object->error;
						$object->errors []= $object->errors;
						return -1;
					}
				}
			}
		}
		// Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users
        dol_include_once('/subtotal/class/subtotal.class.php');
        $langs->load('subtotal@subtotal');

        // If we inserted an invoice line and it came from a shipment or a delivery, we have a problem, Houston.
        // The lines of those objects don't have a special_code, it is therefore not copied from them.
        // Nevertheless, they refer their origin order line => Get the order line, and if it belongs to our
        // module, update the invoice line accordingly
        if (
            $action === 'LINEBILL_INSERT'
            && isset($object->origin)
            && in_array($object->origin, array('shipping', 'delivery'))
            && ! empty($object->origin_id)
        ) {
            if ($object->element === 'delivery') {
                require_once DOL_DOCUMENT_ROOT . '/delivery/class/delivery.class.php';
                $originSendingLine = new DeliveryLine($this->db);
            } else {
                require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $originSendingLine = new ExpeditionLigne($this->db);
            }

            $originSendingLineFetchReturn = $originSendingLine->fetch($object->origin_id);

            if ($originSendingLineFetchReturn < 0) {
                $this->error = $originSendingLine->error;
                $this->errors = $originSendingLine->errors;
                return $originSendingLineFetchReturn;
            }

            require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
            $originOrderLine = new OrderLine($this->db);

            $originOrderLineFetchReturn = $originOrderLine->fetch($originSendingLine->fk_origin_line);

            if ($originOrderLineFetchReturn < 0) {
                $this->error = $originOrderLine->error;
                $this->errors = $originOrderLine->errors;
                return $originOrderLineFetchReturn;
            }

            if ($originOrderLine->special_code == TSubtotal::$module_number) {
                $object->special_code = TSubtotal::$module_number;

                $updateReturn = $object->update($user, 1); // No trigger to prevent loops

                if ($updateReturn < 0) {
                    $this->error = $object->error;
                    $this->errors = $object->errors;
                    return $updateReturn;
                }
            }
        }

        if (getDolGlobalString('SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE') && in_array($action, array('LINEPROPAL_INSERT', 'LINEORDER_INSERT', 'LINEBILL_INSERT')))
		{
			$rang = GETPOST('under_title', 'int'); // Rang du titre
			if ($rang > 0)
			{
				switch ($action) {
					case 'LINEPROPAL_INSERT':
						$parent = new Propal($this->db);
						$parent->fetch($object->fk_propal);
						break;
					case 'LINEORDER_INSERT':
						$parent = new Commande($this->db);
						$parent->fetch($object->fk_commande);
						break;
					case 'LINEBILL_INSERT':
						$parent = new Facture($this->db);
						$parent->fetch($object->fk_facture);
						break;
                    case 'LINEBILL_SUPPLIER_CREATE':
                        $parent = new FactureFournisseur($this->db);
                        $parent->fetch($object->fk_facture_fourn);
					default:
						$parent = $object;
						break;
				}

				if (getDolGlobalString('SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK')) $this->addToEnd($parent, $object, $rang);
				else $this->addToBegin($parent, $object, $rang);

			}

		}


        if ($action == 'LINEBILL_INSERT' || $action == 'LINEBILL_SUPPLIER_CREATE')
		{
		    $is_supplier = $action == 'LINEBILL_SUPPLIER_CREATE' ? true : false;
            /** @var bool $subtotal_skip Permet d'éviter de faire du traitement en double sur les titres est sous-totaux car ils ont automatiquement le bon rang, il ne faut donc pas faire un addline pour en suite update le rang ici */
		    global $subtotal_skip;

		    if ($subtotal_skip)
            {
                $subtotal_skip = false;
            }
		    else
            {
			    $subtotal_add_title_bloc_from_orderstoinvoice = (GETPOST('subtotal_add_title_bloc_from_orderstoinvoice', 'none') && GETPOST('createbills_onebythird', 'int'));
			    if (!empty($subtotal_add_title_bloc_from_orderstoinvoice))
			    {
				    global $subtotal_current_rang, $subtotal_bloc_previous_fk_commande, $subtotal_bloc_already_add_title, $subtotal_bloc_already_add_st;

                    if($object->origin == 'order_supplier') $current_fk_commande = $object->origin_id;
				    else $current_fk_commande = TSubtotal::getOrderIdFromLineId($this->db, $object->origin_id, $is_supplier);
				    $last_fk_commandedet = TSubtotal::getLastLineOrderId($this->db, $current_fk_commande, $is_supplier);

				    if (!$is_supplier){
				        $facture = new Facture($this->db);
				        $ret = $facture->fetch($object->fk_facture);
                    }
				    else
                    {
				        $facture = new FactureFournisseur($this->db);
				        $ret = $facture->fetch($object->fk_facture_fourn);
                    }
					$rang = 0;

				    if ($ret > 0 && !$subtotal_bloc_already_add_st)
				    {
					    $rang = !empty($subtotal_current_rang) ? $subtotal_current_rang : $object->rang;
					    // Si le fk_commande courrant est différent alors on change de commande => ajout d'un titre
					    if ($current_fk_commande != $subtotal_bloc_previous_fk_commande ) {
                            if (!$is_supplier) $commande = new Commande($this->db);
                            else $commande = new CommandeFournisseur($this->db);
                            $commande->fetch($current_fk_commande);

                            $label = getDolGlobalString('SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE');
                            if (empty($label)) {
                                $label = 'Commande [__REFORDER__]';
                                if (!$is_supplier) $label .= ' - Référence client : [__REFCUSTOMER__]';
                            }

                            $label = str_replace(array('__REFORDER__', '__REFCUSTOMER__'), array($commande->ref, $commande->ref_client), $label);

                            if(!empty($current_fk_commande)) {
                                $subtotal_skip = true;
                                TSubtotal::addTitle($facture, $label, 1, $rang);
                                $rang++;
                            }
                        }

                        $object->rang = $rang;
					    $facture->updateRangOfLine($object->id, $rang);
					    $rang++;

					    // Est-ce qu'il s'agit de la dernière ligne de la commande d'origine ? Si oui alors on ajout un sous-total
                        if ($last_fk_commandedet === (int) $object->origin_id && !empty($current_fk_commande))
					    {
                            $subtotal_skip = true;
                            $subtotal_bloc_already_add_st = 1;
							$rang+=2; // pour eviter un bug de décalage ou le sous total ce retrouve apres le nouveau titre : dug constaté en V16 ne doit pas avoir d'impact sur les anciennes versions
                            TSubtotal::addTotal($facture, $langs->trans('SubTotal'), 1, $rang);
                            $subtotal_bloc_already_add_st = 0;
                            $rang++;
					    }
				    }

				    $subtotal_bloc_previous_fk_commande = $current_fk_commande;
				    $subtotal_current_rang = $rang;
			    }
		    }

		}

		if ($action == 'LINEBILL_UPDATE' || 'LINEBILL_MODIFY')
		{
			if (GETPOST('all_progress', 'none') && TSubtotal::isModSubtotalLine($object))
			{
				$object->situation_percent = 0;
				$object->update($user, true); // notrigger pour éviter la boucle infinie
			}
		}

		if (getDolGlobalString('SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS') && in_array($action, array('LINEPROPAL_INSERT', 'LINEPROPAL_UPDATE','LINEPROPAL_MODIFY', 'LINEORDER_INSERT', 'LINEORDER_UPDATE','LINEORDER_MODIFY', 'LINEBILL_INSERT', 'LINEBILL_UPDATE','LINEBILL_MODIFY', 'LINEBILL_SUPPLIER_CREATE', 'LINEBILL_SUPPLIER_UPDATE','LINEBILL_SUPPLIER_MODIFY')))
		{
            if(! function_exists('_updateLineNC')) dol_include_once('/subtotal/lib/subtotal.lib.php');

			$doli_action = GETPOST('action', 'none');
			$set = GETPOST('set', 'none');
			if ( (in_array($doli_action, array('updateligne', 'updateline', 'addline', 'add', 'create', 'setstatut', 'save_nomenclature')) || $set == 'defaultTVA') && !TSubtotal::isTitle($object) && !TSubtotal::isSubtotal($object) && in_array($object->element, array('propaldet', 'commandedet', 'facturedet')))
			{
				 dol_syslog(
					"[SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS] Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". object=".$object->element." id=" . $object->id
				);

				$TTitle = TSubtotal::getAllTitleFromLine($object);
				foreach ($TTitle as &$line)
				{
					if (!empty($line->array_options['options_subtotal_nc']))
					{
						$object->total_ht = $object->total_tva = $object->total_ttc = $object->total_localtax1 = $object->total_localtax2 =
							$object->multicurrency_total_ht = $object->multicurrency_total_tva = $object->multicurrency_total_ttc = 0;

						if ($object->element == 'propal') $res = $object->update(1);
						else $res = $object->update($user, 1);

						if ($res > 0) setEventMessage($langs->trans('subtotal_update_nc_success'));
						break;
					}
				}

				// $object correspond à la ligne ajoutée
				if(empty($object->array_options)) $object->fetch_optionals();

				if(! empty($object->array_options['options_subtotal_nc'])) {
					$object->total_ht = $object->total_tva = $object->total_ttc = $object->total_localtax1 = $object->total_localtax2 =
							$object->multicurrency_total_ht = $object->multicurrency_total_tva = $object->multicurrency_total_ttc = 0;

					if ($object->element == 'propaldet') $res = $object->update(1);
					else $res = $object->update($user, 1);

					if ($res > 0) setEventMessage($langs->trans('subtotal_update_nc_success'));
				}

				// Correction d'un bug lors de la création d'une commande depuis une propale qui a, au moins, une ligne NC
				$parent_element = '';
				if($object->element == 'propaldet') $parent_element = 'propal';
				if($object->element == 'commandedet') $parent_element = 'commande';
				if($object->element == 'facturedet') $parent_element = 'facture';

				if(! empty($parent_element) && ! empty($object->array_options['options_subtotal_nc'])) {
					_updateLineNC($parent_element, $object->{'fk_'.$parent_element}, $object->id, $object->array_options['options_subtotal_nc'], 1);
				}
			}
		}
			// Les lignes libres (y compris les sous-totaux) créées à partir d'une facture modèle n'ont pas la TVA de la ligne du modèle mais la TVA par défaut
		if ($action == 'BILL_CREATE' && $object->fac_rec > 0) {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

			$object->fetch_lines(); // Lignes pas rajoutées à $object->lines par les appels à addline();

			foreach($object->lines as &$line) {
				if(TSubtotal::isSubtotal($line) && ! empty($line->tva_tx)) {
					$line->tva_tx = 0;
					$line->update();
				}
			}
		}

		// Gestion des titres et sous-totaux dans les expéditions
		// Il faut supprimer de l'expédition les titres et sous-totaux s'ils n'ont pas de lignes de produits / services entre eux
		if ($action == 'SHIPPING_CREATE') {
			$object->fetch_lines(); // Obligé de fetch les lines car au retour de la création, les lignes n'ont pas leur id...

			// on recupere la commande
			$object->fetchObjectLinked();

			foreach ($object->lines as &$line) {
				$orderline = new OrderLine($this->db);
				$orderline->fetch($line->origin_line_id);
				// si la conf pas d'affichage des titres  et consorts (sous total )
				//on supprime la ligne de sous total
				if (getDolGlobalString('NO_TITLE_SHOW_ON_EXPED_GENERATION')){
					// le special code n'est pas tranmit dans l'expedition
					// @todo voir plus tard pourquoi nous n'avons pas cette information dans la ligne d'expedition
					if (empty($line->special_code)){
						//  récuperation  de la facture generé par Trigger

						if (count($object->linkedObjectsIds['commande']) == 1) {
							$cmd = new Commande($this->db);
							$res = $cmd->fetch(array_pop($object->linkedObjectsIds['commande']));
							if ($res > 0  ){
								$resLines = $cmd->fetch_lines();
								if ($resLines > 0 ) {
									foreach ($cmd->lines as $cmdLine){
										if ($cmdLine->id == $line->origin_line_id){
											$line->special_code = $cmdLine->special_code;
											break;
										}
									}
								} else{
									//error
									setEventMessage($langs->trans("ErrorLoadingLinesFromLinkedOrder"),'errors');
								}
							} else{
								//error
								setEventMessage($langs->trans("ErrorLoadingLinkedOrder"),'errors');
							}
						}

					}

						if(TSubtotal::isModSubtotalLine($line)) {
							$resdelete = $line->delete($user);
							if ($resdelete < 0){
								setEventMessage($langs->trans('Error_subtotal_delete_line'),'errors');
							}
						}
				}

				if(TSubtotal::isModSubtotalLine($orderline)) { // Nous sommes sur une ligne titre, si la ligne précédente est un titre de même niveau, on supprime la ligne précédente
					$line->special_code = TSubtotal::$module_number;

				}
			}
			$TLinesToDelete = array();
			foreach ($object->lines as &$line) {
				if(TSubtotal::isTitle($line)) {
					$TLines = TSubtotal::getLinesFromTitleId($object, $line->id, true);
					$TBlocks = array();
					$isThereProduct = false;
					foreach($TLines as $lineInBlock) {
							if(TSubtotal::isModSubtotalLine($lineInBlock) ) $TBlocks[$lineInBlock->id] = $lineInBlock;
							else $isThereProduct = true;
					}
					if(!$isThereProduct) {
						$TLinesToDelete = array_merge($TLinesToDelete, $TBlocks);
					}
				}
			}
			if (!empty($TLinesToDelete)) {
				foreach ($TLinesToDelete as $lineToDelete) {
					$lineToDelete->delete($user);
				}
			}
		}

        if ($action == 'USER_LOGIN') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_UPDATE_SESSION') {
            // Warning: To increase performances, this action is triggered only if
            // constant MAIN_ACTIVATE_UPDATESESSIONTRIGGER is set to 1.
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_CREATE_FROM_CONTACT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_NEW_PASSWORD') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_ENABLEDISABLE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_LOGOUT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_SETINGROUP') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'USER_REMOVEFROMGROUP') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Groups
        elseif ($action == 'GROUP_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'GROUP_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'GROUP_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Companies
        elseif ($action == 'COMPANY_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'COMPANY_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'COMPANY_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Contacts
        elseif ($action == 'CONTACT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTACT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTACT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Products
        elseif ($action == 'PRODUCT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PRODUCT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PRODUCT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Customer orders
        elseif ($action == 'ORDER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEORDER_INSERT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEORDER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Supplier orders
        elseif ($action == 'ORDER_SUPPLIER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_SUPPLIER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_SUPPLIER_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SUPPLIER_ORDER_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Proposals
        elseif ((floatval(DOL_VERSION) <= 7.0 && in_array($action, array('PROPAL_CLONE', 'ORDER_CLONE', 'BILL_CLONE'))) ||
                (floatval(DOL_VERSION) >= 8.0 && ! empty($object->context) && in_array('createfromclone', $object->context) && in_array($action, array('PROPAL_CREATE', 'ORDER_CREATE', 'BILL_CREATE')))) {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

			$doli_action = GETPOST('action', 'none');

			if (getDolGlobalString('SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS') && in_array($doli_action, array('confirm_clone')))
			{
				dol_syslog(
					"[SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS] Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". object=".$object->element." id=" . $object->id
				);

				// En fonction de l'objet et de la version, les lignes conservent l'id de l'objet d'origine
				if (method_exists($object, 'fetch_lines')) $object->fetch_lines();
				else $object->fetch($object->id);

				foreach ($object->lines as &$line)
				{
					if (empty($line->array_options)) $line->fetch_optionals();

					if (!TSubtotal::isModSubtotalLine($line) && !empty($line->array_options['options_subtotal_nc']))
					{
						$line->total_ht = $line->total_tva = $line->total_ttc = $line->total_localtax1 = $line->total_localtax2 =
							$line->multicurrency_total_ht = $line->multicurrency_total_tva = $line->multicurrency_total_ttc = 0;

						if ($line->element == 'propaldet') $res = $line->update(1);
						else $res = $line->update($user, 1);

						if ($res > 0) setEventMessage($langs->trans('subtotal_update_nc_success'));
					}
				}

				if (!empty($line)) $object->update_price(1);
			}

        } elseif ($action == 'PROPAL_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_CLOSE_SIGNED') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_CLOSE_REFUSED') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEPROPAL_INSERT') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEPROPAL_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEPROPAL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Contracts
        elseif ($action == 'CONTRACT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_ACTIVATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_CANCEL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_CLOSE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CONTRACT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

		elseif ($action == 'BILL_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );


            global $conf;

            if (getDolGlobalString('INVOICE_USE_SITUATION') && $object->element == 'facture' && $object->type == Facture::TYPE_SITUATION)
            {
                $object->situation_final = 1;
                foreach($object->lines as $i => $line) {
                    if(!TSubtotal::isModSubtotalLine($line) && $line->situation_percent != 100){
                        $object->situation_final = 0;
                        break;
                    }
                }
                // ne pas utiliser $object->setFinal ne peut pas marcher
                $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture SET situation_final = ' . $object->situation_final . ' where rowid = ' . $object->id;
                $resql=$object->db->query($sql);
            }


        } elseif ($action == 'BILL_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_CANCEL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEBILL_INSERT') {
        	dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'LINEBILL_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Payments
        elseif ($action == 'PAYMENT_CUSTOMER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PAYMENT_SUPPLIER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PAYMENT_ADD_TO_BANK') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PAYMENT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Interventions
        elseif ($action == 'FICHEINTER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FICHEINTER_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FICHEINTER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FICHEINTER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Members
        elseif ($action == 'MEMBER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_SUBSCRIPTION') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_NEW_PASSWORD') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_RESILIATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'MEMBER_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Categories
        elseif ($action == 'CATEGORY_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CATEGORY_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'CATEGORY_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Projects
        elseif ($action == 'PROJECT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROJECT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROJECT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Project tasks
        elseif ($action == 'TASK_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Task time spent
        elseif ($action == 'TASK_TIMESPENT_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_TIMESPENT_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'TASK_TIMESPENT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        // Shipping
        elseif ($action == 'SHIPPING_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_VALIDATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_SENTBYMAIL') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'SHIPPING_BUILDDOC') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }elseif ($action == 'LINESHIPPING_INSERT') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		}

        // File
        elseif ($action == 'FILE_UPLOAD') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'FILE_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        return 0;
    }
}
