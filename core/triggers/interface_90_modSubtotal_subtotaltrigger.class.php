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
class Interfacesubtotaltrigger
{

    private $db;

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
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'titre@titre';
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
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users
        dol_include_once('/subtotal/class/subtotal.class.php');
        $langs->load('subtotal@subtotal');
        
        if (!empty($conf->global->SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE) && in_array($action, array('LINEPROPAL_INSERT', 'LINEORDER_INSERT', 'LINEBILL_INSERT')))
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
					default:
						$parent = $object;
						break;
				}
				
				if (!empty($conf->global->SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK)) $this->addToEnd($parent, $object, $rang);
				else $this->addToBegin($parent, $object, $rang);
				
			}
			
		}
        
		
        if ($action == 'LINEBILL_INSERT' && $object->special_code != TSubtotal::$module_number)
		{
			$subtotal_add_title_bloc_from_orderstoinvoice = GETPOST('subtotal_add_title_bloc_from_orderstoinvoice');
			if (!empty($subtotal_add_title_bloc_from_orderstoinvoice))
			{
				global $subtotal_current_rang, $subtotal_bloc_previous_fk_commande, $subtotal_bloc_already_add_title;
				
				$current_fk_commande = TSubtotal::getOrderIdFromLineId($this->db, $object->origin_id);
				$last_fk_commandedet = TSubtotal::getLastLineOrderId($this->db, $current_fk_commande);
				
				$facture = new Facture($this->db);
				if ($facture->fetch($object->fk_facture) > 0)
				{
					$rang = !empty($subtotal_current_rang) ? $subtotal_current_rang : $object->rang;
					// Si le fk_commande courrant est différent alors on change de commande => ajout d'un titre
					if ($current_fk_commande != $subtotal_bloc_previous_fk_commande) 
					{
						$commande = new Commande($this->db);
						$commande->fetch($current_fk_commande);
						
						$label = $conf->global->SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE;
						if (empty($label)) $label = 'Commande [__REFORDER__] - Référence client : [__REFCUSTOMER__]';
						$label = str_replace(array('__REFORDER__', '__REFCUSTOMER__'), array($commande->ref, $commande->ref_client), $label);
						
						TSubtotal::addTitle($facture, $label, 1, $rang);
						$rang++;
					}
					
					$object->rang = $rang;
					$facture->updateRangOfLine($object->id, $rang);
					$rang++;
						
					// Est-ce qu'il s'agit de la dernière ligne de la commande d'origine ? Si oui alors on ajout un sous-total
					if ($last_fk_commandedet == $object->origin_id) 
					{
						TSubtotal::addTotal($facture, $langs->trans('SubTotal'), 1, $rang);
						$rang++;
					}
				}
				
				$subtotal_bloc_previous_fk_commande = $current_fk_commande;
				$subtotal_current_rang = $rang;
			}
		}
		
		
		if (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && in_array($action, array('LINEPROPAL_INSERT', 'LINEPROPAL_UPDATE', 'LINEORDER_INSERT', 'LINEORDER_UPDATE', 'LINEBILL_INSERT', 'LINEBILL_UPDATE')))
		{
			$doli_action = GETPOST('action');
			$set = GETPOST('set');
			if ( (in_array($doli_action, array('updateligne', 'updateline', 'addline', 'add')) || $set == 'defaultTVA') && !TSubtotal::isTitle($object) && !TSubtotal::isSubtotal($object) && in_array($object->element, array('propaldet', 'commandedet', 'facturedet')))
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
        elseif ($action == 'ORDER_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_CLONE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'ORDER_VALIDATE') {
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
        elseif ($action == 'PROPAL_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PROPAL_CLONE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
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

        // Bills
        elseif ($action == 'BILL_CREATE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_CLONE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'BILL_MODIFY') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
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