<?php


class TSubtotal {

	static $module_number = 104777;

    /**
     * Init subtotal qty list by level
     *
     * @param   CommonObject    $object     Object
     * @param   int             $level      [=0] Sub-total level
     */
    static function initSubtotalQtyForObject($object, $level = 0)
    {
        if (!isset($object->TSubtotalQty)) {
            $object->TSubtotalQty = array();
        }
        if (!isset($object->TSubtotalQty[$level])) {
            $object->TSubtotalQty[$level] = 0;
        }
    }

    /**
     * Set subtotal quantity in list by level
     *
     * @param   CommonObject    $object Object
     * @param   int             $level  Subtotal level
     * @param   int             $qty    [=0] Subtotal qty
     */
    static function setSubtotalQtyForObject($object, $level, $qty = 0)
    {
        self::initSubtotalQtyForObject($object, $level);
        $object->TSubtotalQty[$level] = $qty;
    }

    /**
     * Add subtotal quantity in list by level
     *
     * @param   CommonObject    $object Object
     * @param   int             $level  Subtotal level
     * @param   int             $qty    [=0] Subtotal qty
     */
    static function addSubtotalQtyForObject($object, $level, $qty = 0)
    {
        self::initSubtotalQtyForObject($object, $level);
        $object->TSubtotalQty[$level] += $qty;
    }

    /**
     * Determine to show subtotal line qty by default for this object
     *
     * @param   CommonObject    $object Object
     * @return  bool            False no show subtotal qty for this object else True
     */
    static function showQtyForObject($object)
    {
        global $conf;

        $show = false;
        if (!empty($conf->global->SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS) && in_array($object->element, explode(',', $conf->global->SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS))) {
            $show = true;
        }

        return $show;
    }

    /**
     * Determine to show subtotal line qty by default for this object line
     *
     * @param   Object  $line               Object line
     * @param   bool    $show_by_default    [=false] Not to show by default
     * @return  bool    False no show subtotal qty for this object line else True
     */
    static function showQtyForObjectLine($line, $show_by_default = false) {
        if ($show_by_default === false) {
            $line_show_qty = false;
            if (isset($line->array_options['options_subtotal_show_qty']) && $line->array_options['options_subtotal_show_qty'] > 0) {
                $line_show_qty = true;
            }
        } else {
            $line_show_qty = true;
            if (isset($line->array_options['options_subtotal_show_qty']) && $line->array_options['options_subtotal_show_qty'] < 0) {
                $line_show_qty = false;
            }
        }

        return $line_show_qty;
    }


	/**
	 * @param CommonObject $object
	 * @param string       $label
	 * @param int          $qty
	 * @param int          $rang
	 * @return int
	 */
	static function addSubTotalLine(&$object, $label, $qty, $rang=-1) {

		$res = 0;

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
			$desc = '';

			$TNotElements = array ('invoice_supplier', 'order_supplier');
			if ((float) DOL_VERSION < 6  || $qty==50 && !in_array($object->element, $TNotElements) ) {
				$desc = $label;
				$label = '';
			}

			if($object->element=='facture')
            {
                /** @var Facture $object */
                $res =  $object->addline($desc, 0,$qty,0,0,0,0,0,'','',0,0,'','HT',0,9,$rang, TSubtotal::$module_number, '', 0, 0, null, 0, $label);
            }
			elseif($object->element=='invoice_supplier') {
                /** @var FactureFournisseur $object */
			    $object->special_code = TSubtotal::$module_number;
                if( (float)DOL_VERSION < 6 ) $rang = $object->line_max() + 1;
			    $res = $object->addline($label,0,0,0,0,$qty,0,0,'','',0,0,'HT',9,$rang);
			}
			/**
			 * @var $object Propal
			 */
			else if($object->element=='propal') $res = $object->addline($desc, 0,$qty,0,0,0,0,0,'HT',0,0,9,$rang, TSubtotal::$module_number, 0, 0, 0, $label);
			/**
			 * @var $object Propal Fournisseur
			 */
			else if($object->element=='supplier_proposal') $res = $object->addline($desc, 0,$qty,0,0,0,0,0,'HT',0,0,9,$rang, TSubtotal::$module_number, 0, 0, 0, $label);

			/**
			 * @var $object Commande
			 */
			else if($object->element=='commande') $res =  $object->addline($desc, 0,$qty,0,0,0,0,0,0,0,'HT',0,'','',9,$rang, TSubtotal::$module_number, 0, null, 0, $label);
			/**
			 * @var $object Commande fournisseur
			 */
			else if($object->element=='order_supplier') {
			    $object->special_code = TSubtotal::$module_number;
			    $res = $object->addline($label, 0,$qty,0,0,0,0,0,'',0,'HT', 0, 9);
			}
			/**
			 * @var $object Facturerec
			 */
			else if($object->element=='facturerec') $res =  $object->addline($desc, 0,$qty, 0, 0, 0, 0, 0, 'HT', 0, '', 0, 9, $rang, TSubtotal::$module_number,$label);

		}

		self::generateDoc($object);

		return $res;
	}

	/**
	 * @param CommonObject $object
	 */
	public static function generateDoc(&$object)
	{
		global $conf,$langs,$db;

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
			$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
			$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

			// Define output language
			$outputlangs = $langs;
			$newlang = GETPOST('lang_id', 'alpha');
			if (! empty($conf->global->MAIN_MULTILANGS) && empty($newlang))
				$newlang = !empty($object->client) ? $object->client->default_lang : $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			$ret = $object->fetch($object->id); // Reload to get new records
			if ((float) DOL_VERSION <= 3.6)
			{
				if ($object->element == 'propal') propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				elseif ($object->element == 'commande') commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				elseif ($object->element == 'facture') facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
			else
			{
				if ($object->element!= 'facturerec') $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	}

	/**
	 * Permet de mettre à jour les rangs afin de décaler des lignes pour une insertion en milieu de document
	 *
	 * @param type $object
	 * @param type $rang_start
	 * @param type $move_to
	 */
	public static function updateRang(&$object, $rang_start, $move_to=1)
	{
		if (!class_exists('GenericObject')) require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

		$row=new GenericObject($object->db);
		$row->table_element_line = $object->table_element_line;
		$row->fk_element = $object->fk_element;
		$row->id = $object->id;

		foreach ($object->lines as &$line)
		{
			if ($line->rang < $rang_start) continue;

			$row->updateRangOfLine($line->id, $line->rang+$move_to);
		}
	}

	/**
	 * Méthode qui se charge de faire les ajouts de sous-totaux manquant afin de fermer les titres ouvert lors de l'ajout d'un nouveau titre
	 *
	 * @global Translate   $langs
	 * @param CommonObject $object
	 * @param int          $level_new_title
	 */
	public static function addSubtotalMissing(&$object, $level_new_title)
	{
		global $langs;
		$TTitle = self::getAllTitleWithoutTotalFromDocument($object);
		// Reverse - Pour partir de la fin et remonter dans les titres pour me permettre de m'arrêter quand je trouve un titre avec un niveau inférieur à celui qui a était ajouté
		$TTitle_reverse = array_reverse($TTitle);

		foreach ($TTitle_reverse as $k => $title_line)
		{
			$title_niveau = self::getNiveau($title_line);
			if ($title_niveau < $level_new_title) break;

			$rang_to_add = self::titleHasTotalLine($object, $title_line, true, true);

			if (is_numeric($rang_to_add))
			{
				if ($rang_to_add != -1) self::updateRang($object, $rang_to_add);

				self::addSubTotalLine($object, $langs->trans('SubTotal'), 100-$title_niveau, $rang_to_add);

				$object->lines[] = $object->line; // ajout de la ligne dans le tableau de ligne (Dolibarr ne le fait pas)
				if ($rang_to_add != -1)
				{
					if (method_exists($object, 'fetch_lines')) $object->fetch_lines();
					else $object->fetch($object->id);
				}
			}
		}
	}

	public static function addTitle(&$object, $label, $level, $rang=-1)
	/**
	 * @param CommonObject $object
	 * @param string       $label
	 * @param int          $level
	 * @param int          $rang
	 * @return int
	 */
	{
		return self::addSubTotalLine($object, $label, $level, $rang);
	}

	public static function addTotal(&$object, $label, $level, $rang=-1)
	/**
	 * @param CommonObject $object
	 * @param string       $label
	 * @param int          $level
	 * @param int          $rang
	 * @return int
	 */
	{
		return self::addSubTotalLine($object, $label, (100-$level), $rang);
	}

	/**
	 * Récupère la liste des lignes de titre qui n'ont pas de sous-total
	 *
	 * @param Propal|Commande|Facture				$object
	 * @param boolean								$get_block_total
	 *
	 * @return array
	 */
	public static function getAllTitleWithoutTotalFromDocument(&$object, $get_block_total=false)
	{
		$TTitle = self::getAllTitleFromDocument($object, $get_block_total);

		foreach ($TTitle as $k => $title_line)
		{
			if (self::titleHasTotalLine($object, $title_line)) unset($TTitle[$k]);
		}

		return $TTitle;
	}

	/**
	 * Est-ce que mon titre ($title_line) a un sous-total ?
	 *
	 * @param Propal|Commande|Facture				$object
	 * @param PropaleLigne|OrderLine|FactureLigne	$title_line
	 * @param boolean								$strict_mode			si true alors un titre doit avoir un sous-total de même niveau; si false un titre possède un sous-total à partir du moment où l'on trouve un titre de niveau égale ou inférieur
	 * @param boolean								$return_rang_on_false	si true alors renvoi le rang où devrait ce trouver le sous-total
	 * @return boolean
	 */
	public static function titleHasTotalLine(&$object, &$title_line, $strict_mode=false, $return_rang_on_false=false)
	{
		if (empty($object->lines) || !is_array($object->lines)) return false;

		$title_niveau = self::getNiveau($title_line);
		foreach ($object->lines as &$line)
		{
			if ($line->rang <= $title_line->rang) continue;
			if (self::isTitle($line) && self::getNiveau($line) <= $title_niveau) return false; // Oups on croise un titre d'un niveau inférieur ou égale (exemple : je croise un titre niveau 2 alors que je suis sur un titre de niveau 3) pas lieu de continuer car un nouveau bloc commence
			if (!self::isSubtotal($line)) continue;

			$subtotal_niveau = self::getNiveau($line);

			// Comparaison du niveau de la ligne de sous-total avec celui du titre
			if ($subtotal_niveau == $title_niveau) return true; // niveau égale => Ok mon titre a un sous-total
			elseif ($subtotal_niveau < $title_niveau) // niveau inférieur trouvé (exemple : sous-total de niveau 1 contre mon titre de niveau 3)
			{
				if ($strict_mode) return ($return_rang_on_false) ? $line->rang : false; // mode strict niveau pas égale donc faux
				else return true; // mode libre => OK je considère que mon titre à un sous-total
			}
		}

		// Sniff, j'ai parcouru toutes les lignes et pas de sous-total pour ce titre
		return ($return_rang_on_false) ? -1 : false;
	}

	/**
	 * @param CommonObject $object
	 * @param boolean      $get_block_total
	 * @return array
	 */
	public static function getAllTitleFromDocument(&$object, $get_block_total=false)
	{
		$TRes = array();
		if (!empty($object->lines))
		{
			foreach ($object->lines as $k => &$line)
			{
				if (self::isTitle($line))
				{
					if ($get_block_total)
					{
						$TTot = self::getTotalBlockFromTitle($object, $line);

						$line->total_pa_ht = $TTot['total_pa_ht'];
						$line->total_options = $TTot['total_options'];
						$line->total_ht = $TTot['total_ht'];
						$line->total_tva = $TTot['total_tva'];
						$line->total_ttc = $TTot['total_ttc'];
						$line->TTotal_tva = $TTot['TTotal_tva'];
						$line->multicurrency_total_ht = $TTot['multicurrency_total_ht'];
						$line->multicurrency_total_tva = $TTot['multicurrency_total_tva'];
						$line->multicurrency_total_ttc = $TTot['multicurrency_total_ttc'];
						$line->TTotal_tva_multicurrency = $TTot['TTotal_tva_multicurrency'];
					}

					$TRes[] = $line;
				}
			}
		}

		return $TRes;
	}

	/**
	 * @param CommonObject     $object
	 * @param CommonObjectLine $line
	 * @param boolean          $breakOnTitle
	 * @return array
	 */
	public static function getTotalBlockFromTitle(&$object, &$line, $breakOnTitle = false)
	{
		dol_include_once('/core/lib/price.lib.php');
		$TTot = array('total_pa_ht' => 0, 'total_options' => 0, 'total_ht' => 0, 'total_tva' => 0, 'total_ttc' => 0, 'TTotal_tva' => array(), 'multicurrency_total_ht' => 0, 'multicurrency_total_tva' => 0, 'multicurrency_total_ttc' => 0, 'TTotal_tva_multicurrency' => array());

		foreach ($object->lines as &$l)
		{
			if ($l->rang <= $line->rang) continue;
			elseif (self::isSubtotal($l) && self::getNiveau($l) <= self::getNiveau($line)) break;
			elseif ($breakOnTitle && self::isTitle($l) && self::getNiveau($l) <= self::getNiveau($line)) break;

			if (!empty($l->array_options['options_subtotal_nc']))
			{
				$tabprice = calcul_price_total($l->qty, $l->subprice, $l->remise_percent, $l->tva_tx, $l->localtax1_tx, $l->localtax2_tx, 0, 'HT', $l->info_bits, $l->product_type);
				$TTot['total_options'] += $tabprice[0]; // total ht
			}
			else
			{
				// Fix DA020000 : exlure les sous-totaux du calcul (calcul pété)
				// sinon ça compte les ligne de produit puis les sous-totaux qui leurs correspondent...
				if (! self::isSubtotal($l))
				{
					$TTot['total_pa_ht'] += $l->pa_ht * $l->qty;
					$TTot['total_subprice'] += $l->subprice * $l->qty;
					$TTot['total_unit_subprice'] += $l->subprice; // Somme des prix unitaires non remisés
					$TTot['total_ht'] += $l->total_ht;
					$TTot['total_tva'] += $l->total_tva;
					$TTot['total_ttc'] += $l->total_ttc;
					$TTot['TTotal_tva'][$l->tva_tx] += $l->total_tva;
					$TTot['multicurrency_total_ht'] += $l->multicurrency_total_ht;
					$TTot['multicurrency_total_tva'] += $l->multicurrency_total_tva;
					$TTot['multicurrency_total_ttc'] += $l->multicurrency_total_ttc;
					$TTot['TTotal_tva_multicurrency'][$l->tva_tx] += $l->multicurrency_total_tva;
				}

			}
		}

		return $TTot;
	}

	/**
	 * @param DoliDB  $db
	 * @param int     $fk_commandedet
	 * @param boolean $supplier
	 * @return int|false
	 */
	public static function getOrderIdFromLineId(&$db, $fk_commandedet, $supplier = false)
	{
		if (empty($fk_commandedet)) return false;

		$table = 'commandedet';
		if ($supplier) $table = 'commande_fournisseurdet';

		$sql = 'SELECT fk_commande FROM '.MAIN_DB_PREFIX.$table.' WHERE rowid = '.$fk_commandedet;
		$resql = $db->query($sql);

		if ($resql && ($row = $db->fetch_object($resql))) return $row->fk_commande;
		else return false;
	}

	/**
	 * @param DoliDB  $db
	 * @param int     $fk_commande
	 * @param boolean $supplier
	 * @return false|int
	 */
	public static function getLastLineOrderId(&$db, $fk_commande, $supplier = false)
	{
		if (empty($fk_commande)) return false;

        $table = 'commandedet';
        if ($supplier) $table = 'commande_fournisseurdet';

		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$table.' WHERE fk_commande = '.$fk_commande.' ORDER BY rang DESC LIMIT 1';
		$resql = $db->query($sql);

		if ($resql && ($row = $db->fetch_object($resql))) return (int) $row->rowid;
		else return false;
	}

	/**
	 * @param FactureLigne|PropaleLigne|OrderLine $object
	 * @param int $rang  rank of the line in the object; The first line has rank = 1, not 0.
	 * @param int $lvl
	 * @return bool|FactureLigne|PropaleLigne|OrderLine
	 */
	public static function getParentTitleOfLine(&$object, $rang, $lvl = 0)
	{
		if ($rang <= 0) return false;

		$skip_title = 0;
		$TLineReverse = array_reverse($object->lines);

		foreach($TLineReverse as $line)
		{
			if ($line->rang >= $rang || ($lvl > 0 && self::getNiveau($line) > $lvl)) continue; // Tout ce qui ce trouve en dessous j'ignore, nous voulons uniquement ce qui ce trouve au dessus

            if (self::isTitle($line))
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
			elseif (self::isSubtotal($line))
			{
				// Il s'agit d'un sous-total, ça veut dire que le prochain titre théoriquement doit être ignorer (je travail avec un incrément au cas ou je croise plusieurs sous-totaux)
				$skip_title++;
			}
		}

		return false;
	}

	/**
	 * @param CommonObjectLine $line
	 * @param int              $level
	 * @return bool
	 */
	public static function isTitle(&$line, $level=-1)
	{
		$res = $line->special_code == self::$module_number && $line->product_type == 9 && $line->qty <= 9;
		if($res && $level > -1) {
			return $line->qty == $level;
		} else return $res;

	}

	/**
	 * @param CommonObjectLine $line
	 * @param int              $level
	 * @return bool
	 */
	public static function isSubtotal(&$line, $level=-1)
	{
	    $res = $line->special_code == self::$module_number && $line->product_type == 9 && $line->qty >= 90;
	    if($res && $level > -1) {
	        return self::getNiveau($line) == $level;
	    } else return $res;
	}

	/**
	 * @param CommonObjectLine $line
	 * @return bool
	 */
	public static function isFreeText(&$line)
	{
		return $line->special_code == self::$module_number && $line->product_type == 9 && $line->qty == 50;
	}

	/**
	 * @param CommonObjectLine $line
	 * @return bool
	 */
	public static function isModSubtotalLine(&$line)
	{
		return self::isTitle($line) || self::isSubtotal($line) || self::isFreeText($line);
	}

	/**
	 * @param CommonObjectLine $line
	 * @param int $readonly
	 * @return string|void
	 */
	public static function getFreeTextHtml(&$line, $readonly=0)
	{
		global $conf;

		// Copie du fichier "objectline_edit.tpl.php"
		// editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows=ROWS_2;
		if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
		$enable=(isset($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
		$toolbarname='dolibarr_details';
		if (! empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) $toolbarname='dolibarr_notes';
		$text = !empty($line->description)?$line->description:$line->label;
		$doleditor=new DolEditor('line-description',$text,'',164,$toolbarname,'',false,true,$enable,$nbrows,'98%', $readonly);
		return $doleditor->Create(1);
	}

	/**
	 * @param CommonObject $object
	 * @param int          $lineid
	 * @param bool         $withBlockLine
	 * @return int
	 */
	public static function duplicateLines(&$object, $lineid, $withBlockLine=false)
	{
		global $db,$user,$conf;

		$createRight = $user->rights->{$object->element}->creer;
		if($object->element == 'facturerec' )
		{
		    $object->statut = 0; // hack for facture rec
		    $createRight = $user->rights->facture->creer;
		}
		elseif($object->element == 'order_supplier' )
		{
		    $createRight = $user->rights->fournisseur->commande->creer;
		}
		elseif($object->element == 'invoice_supplier' )
		{
		    $createRight = $user->rights->fournisseur->facture->creer;
		}

		if ($object->statut == 0  && $createRight && (!empty($conf->global->SUBTOTAL_ALLOW_DUPLICATE_BLOCK) || !empty($conf->global->SUBTOTAL_ALLOW_DUPLICATE_LINE)))
		{
			dol_include_once('/subtotal/lib/subtotal.lib.php');

            if(!empty($object->lines)) {
                foreach($object->lines as $line) {
                    if($line->id == $lineid) $duplicateLine = $line;
                }
            }
            if(!empty($duplicateLine) && !self::isModSubtotalLine($duplicateLine)) $TLine = array($duplicateLine);
            else $TLine = self::getLinesFromTitleId($object, $lineid, $withBlockLine);

			if (!empty($TLine))
			{
				$object->db->begin();
				$res = 1;
                $object->context['subtotalDuplicateLines'] = true;

				$TLineAdded = array();
				foreach ($TLine as $line)
				{
					// TODO refactore avec un doAddLine sur le même schéma que le doUpdateLine
					switch ($object->element) {
						case 'propal':
							//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $fk_product=0, $remise_percent=0.0, $price_base_type='HT', $pu_ttc=0.0, $info_bits=0, $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=0, $pa_ht=0, $label='',$date_start='', $date_end='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0)
							$res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, 'HT', 0, $line->info_bits, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $object->element, $line->id);
							break;

						case 'supplier_proposal':
						    $res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, 'HT', 0, $line->info_bits, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $object->element, $line->id);
						    break;

						case 'commande':
							//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0, $date_start='', $date_end='', $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0)
							$res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->date_start, $line->date_end, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->array_options, $line->fk_unit, $object->element, $line->id);
							break;

						case 'order_supplier':
						    $object->line = $line;
						    $object->line->origin = $object->element;
						    $object->line->origin_id = $line->id;
						    $object->line->fk_commande = $object->id;
						    $object->line->rang = $object->line_max() +1;
						    $res = $object->line->insert(1);
							break;

						case 'facture':
							//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id='', $fk_unit = null
							$res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->date_start, $line->date_end, 0, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->product_type, -1, $line->special_code, $object->element, $line->id, $line->fk_parent_line, $line->fk_fournprice, $line->pa_ht, $line->label, $line->array_options, $line->situation_percent, $line->fk_prev_id, $line->fk_unit);
							break;
						/*	Totally useless on invoice supplier
						case 'invoice_supplier':
						    //var_dump($line); exit;
						    $rang = $object->line_max() +1;
						    $object->special_code = $line->special_code;
						    if (TSubtotal::isModSubtotalLine($line)) {
						        $object->line = $line;
						        $object->line->desc = $line->description;
						        $object->line->description = $line->description;
						        $object->line->fk_facture_fourn = $object->id;
						        $object->line->rang = $rang;
						        //var_dump($object->line); exit;
    						    $res = $object->line->insert(1);
						        break;
						        //var_dump($line->desc, $line->label, $line->description); exit;
						    }
						    $res = $object->addline($line->desc, $line->subprice, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->qty, $line->fk_product, $line->remise_percent, $line->date_start, $line->date_end, 0, $line->info_bits, 'HT', $line->product_type, $rang);
						    break;
							*/
						case 'facturerec':
							//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id='', $fk_unit = null
							$res = $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->date_start, $line->date_end, 0, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->product_type, -1, $line->special_code, $line->origin, $line->origin_id, $line->fk_parent_line, $line->fk_fournprice, $line->pa_ht, $line->label, $line->array_options, $line->situation_percent, $line->fk_prev_id, $line->fk_unit);
							break;
					}

					$TLineAdded[] = $object->line;
					// Error from addline
					if ($res <= 0) break;
				}

				if ($res > 0)
				{
					$object->db->commit();
					foreach ($TLineAdded as &$line)
					{
					    // ça peut paraitre non optimisé de déclancher la fonction sur toutes les lignes mais ceci est nécessaire pour réappliquer l'état exact de chaque ligne
                        //En gros ça met à jour le sous total
					   if(!empty($line->array_options['options_subtotal_nc'])) _updateLineNC($object->element, $object->id, $line->id, $line->array_options['options_subtotal_nc']);
					}
					return count($TLineAdded);
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

	/**
	 * @param CommonObject $object
	 * @param string       $key_trad
	 * @param int          $level
	 * @param string       $under_title
	 * @param bool         $withBlockLine
	 * @param bool         $key_is_id
	 * @return array
	 */
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
				elseif ($add_line && TSubtotal::isModSubtotalLine($line) && TSubtotal::getNiveau($line) == $level) // Si on tombe sur un sous-total, il faut que ce soit un du même niveau que le titre
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

	/**
	 * @param CommonObject $object
	 * @param int          $lineid
	 * @param bool         $withBlockLine
	 * @return array
	 */
	public static function getLinesFromTitleId(&$object, $lineid, $withBlockLine=false)
	{
		return self::getLinesFromTitle($object, $lineid, '', '', $withBlockLine, true);
	}

	/**
	 * Wrapper around $object->updateline() to ensure it is called with the right parameters depending on the object's
	 * type.
	 *
	 * @param CommonObject $object
	 * @param int $rowid
	 * @param string $desc
	 * @param double $pu
	 * @param double $qty
	 * @param double $remise_percent
	 * @param $date_start
	 * @param $date_end
	 * @param double $txtva
	 * @param $type
	 * @param int $txlocaltax1
	 * @param int $txlocaltax2
	 * @param string $price_base_type
	 * @param int $info_bits
	 * @param int $fk_parent_line
	 * @param int $skip_update_total
	 * @param null $fk_fournprice
	 * @param int $pa_ht
	 * @param string $label
	 * @param int $special_code
	 * @param int $array_options
	 * @param int $situation_percent
	 * @param null $fk_unit
	 * @param int $notrigger
	 * @return int
	 */
	public static function doUpdateLine(&$object, $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $type, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_options=0, $situation_percent=0, $fk_unit = null, $notrigger = 0)
	{
		$res = 0;
		$object->db->begin();

		switch ($object->element)
		{
		    case 'propal':
		        $res = $object->updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $desc, $price_base_type, $info_bits, $special_code, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $type, $date_start, $date_end, $array_options, $fk_unit, 0, $notrigger);
		        break;

		    case 'supplier_proposal':
		        $res = $object->updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $desc, $price_base_type, $info_bits, $special_code, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $type, $array_options,'', $fk_unit);
		        break;

			case 'commande':
				$res = $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $date_start, $date_end, $type, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $special_code, $array_options, $fk_unit, 0, $notrigger);
				break;

			case 'order_supplier':
			    $object->special_code = SELF::$module_number;
			    if (empty($desc)) $desc = $label;
			    $res = $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $type, 0, $date_start, $date_end, $array_options, $fk_unit);
			    break;

			case 'facture':
				$res = $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $type, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $special_code, $array_options, $situation_percent, $fk_unit, 0, $notrigger);
				break;

			case 'invoice_supplier':
			    $object->special_code = SELF::$module_number;
			    if (empty($desc)) $desc = $label;
			    $res = $object->updateline($rowid, $desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, 0, $price_base_type, $info_bits, $type, $remise_percent, 0, $date_start, $date_end, $array_options, $fk_unit);
			    break;

			case 'facturerec':
				// Add extrafields and get rang
				$factureRecLine = new FactureLigneRec($object->db);
				$factureRecLine->fetch($rowid);
				$factureRecLine->array_options = $array_options;
				$factureRecLine->insertExtraFields();
				$rang=$factureRecLine->rang;

				$fk_product=0; $fk_remise_except=''; $pu_ttc=0;
				$res = $object->updateline($rowid, $desc, $pu, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $price_base_type, $info_bits, $fk_remise_except, $pu_ttc, $type, $rang, $special_code, $label, $fk_unit);
				break;
		}

		if ($res <= 0) $object->db->rollback();
		else $object->db->commit();

		return $res;
	}

	/**
	 * @param CommonObjectLine $origin_line
	 * @param bool             $reverse
	 * @return array
	 */
	public static function getAllTitleFromLine(&$origin_line, $reverse = false)
	{
		global $db, $object;

		$TTitle = array();
		if(! empty($object->id) && in_array($object->element, array('propal', 'commande', 'facture'))) {}
		else {
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
		if ($i >= 0)
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
						if (empty($object->lines[$y]->array_options) && !empty($object->lines[$y]->id)) $object->lines[$y]->fetch_optionals();
						$TTitle[$object->lines[$y]->id] = $object->lines[$y];

						if ($object->lines[$y]->qty == 1) break;
					}
				}
			}
		}

		if ($reverse) $TTitle = array_reverse($TTitle, true);

		return $TTitle;
	}

	/**
	 * @param CommonObjectLine $line
	 * @return int
	 */
	public static function getNiveau(&$line)
	{
		if (self::isTitle($line)) return $line->qty;
		elseif (self::isSubtotal($line)) return 100 - $line->qty;
		else return 0;
	}

	/**
	 * Ajoute une page de récap à la génération du PDF
	 * Le tableau total en bas du document se base sur les totaux des titres niveau 1 pour le moment
	 *
	 * @param array $parameters assoc array; keys: 'object' (CommonObject), 'file' (string), 'outputlangs' (Translate)
	 * @param null  $origin_pdf unused [lines that used it are commented out]
	 */
	public static function addRecapPage(&$parameters, &$origin_pdf, $fromInfraS = 0)	// InfraS change
	{
		global $user,$conf,$langs;

		$origin_file = $parameters['file'];
		$outputlangs = $parameters['outputlangs'];
		$object = $parameters['object'];

		$outputlangs->load('subtotal@subtotal');

		$objmarge = new stdClass();
		$objmarge->page_hauteur = 297;
		$objmarge->page_largeur = 210;
		$objmarge->marge_gauche = 10;
		$objmarge->marge_haute = 10;
		$objmarge->marge_droite = 10;

		$objectref = dol_sanitizeFileName($object->ref);
		if ($object->element == 'propal') $dir = $conf->propal->dir_output . '/' . $objectref;
		elseif ($object->element == 'commande') $dir = $conf->commande->dir_output . '/' . $objectref;
		elseif ($object->element == 'facture') $dir = $conf->facture->dir_output . '/' . $objectref;
		elseif ($object->element == 'facturerec') return; // no PDF for facturerec
		else
		{
			setEventMessage($langs->trans('warning_subtotal_recap_object_element_unknown', $object->element), 'warnings');
			return -1;
		}
		$file = $dir . '/' . $objectref . '_recap.pdf';

//		$pdf=pdf_getInstance($origin_pdf->format);
		$pdf=pdf_getInstance(array(210, 297)); // Format A4 Portrait
		$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
		$pdf->SetAutoPageBreak(1,0);

		if (class_exists('TCPDF'))
		{
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));
		// Set path to the background PDF File
		if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
		{
			$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
			$tplidx = $pdf->importPage(1);
		}

		$pdf->Open();
		$pagenb=0;
		$pdf->SetDrawColor(128,128,128);

		$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
		$pdf->SetSubject($outputlangs->transnoentities("subtotalRecap"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("subtotalRecap")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
		if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

		$pdf->SetMargins($objmarge->marge_gauche, $objmarge->marge_haute, $objmarge->marge_droite);   // Left, Top, Right

		$pagenb=0;
		$pdf->SetDrawColor(128,128,128);


		// New page
		$pdf->AddPage();
		if (! empty($tplidx)) $pdf->useTemplate($tplidx);
		$pagenb++;


		self::pagehead($objmarge, $pdf, $object, 1, $outputlangs);
		$pdf->SetFont('','', $default_font_size - 1);
		$pdf->MultiCell(0, 3, '');		// Set interline to 3
		$pdf->SetTextColor(0,0,0);

		$heightforinfotot = 25;	// Height reserved to output the info and total part
		$heightforfooter = $objmarge->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)

		$posx_designation = 25;
		$posx_options = 150;
		$posx_montant = 170;

		$tab_top = 72;
		$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?72:20); // TODO à vérifier

		$TTot = array('total_ht' => 0, 'total_ttc' => 0, 'TTotal_tva' => array());

		$TLine = self::getAllTitleFromDocument($object, true);
		if (!empty($TLine))
		{
			$hidetop = 0;

			$iniY = $tab_top + 10;
			$curY = $tab_top + 10;
			$nexY = $tab_top + 10;

			$nblignes = count($TLine);
			foreach($TLine as $i => &$line)
			{
				$curY = $nexY;

				if (self::getNiveau($line) == 1)
				{
					$pdf->SetFont('','B', $default_font_size - 1);   // Into loop to work with multipage
					$curY+=2;

					$TTot['total_ht'] += $line->total_ht;
					$TTot['total_tva'] += $line->total_tva;
					$TTot['total_ttc'] += $line->total_ttc;
					$TTot['multicurrency_total_ht'] += $line->multicurrency_total_ht;
					$TTot['multicurrency_total_tva'] += $line->multicurrency_total_tva;
					$TTot['multicurrency_total_ttc'] += $line->multicurrency_total_ttc;

					foreach ($line->TTotal_tva as $tx => $amount)
					{
						$TTot['TTotal_tva'][$tx] += $amount;
					}

					foreach ($line->TTotal_tva_multicurrency as $tx => $amount)
					{
						$TTot['TTotal_tva_multicurrency'][$tx] += $amount;
					}
				}
				else $pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage

				$pdf->SetTextColor(0,0,0);

				$pdf->setTopMargin($tab_top_newpage + 10);
				$pdf->setPageOrientation('', 1, $heightforfooter+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
				$pageposbefore=$pdf->getPage();

				$showpricebeforepagebreak=1;

				$decalage = (self::getNiveau($line) - 1) * 2;

				// Print: Designation
				$label = $line->label;
				if( (float)DOL_VERSION < 6 ) {
					$label = !empty($line->label) ? $line->label : $line->desc;
				}


				$pdf->startTransaction();
				$pdf->writeHTMLCell($posx_options-$posx_designation-$decalage, 3, $posx_designation+$decalage, $curY, $outputlangs->convToOutputCharset($label), 0, 1, false, true, 'J',true);
				$pageposafter=$pdf->getPage();
				if ($pageposafter > $pageposbefore)	// There is a pagebreak
				{
					$pdf->rollbackTransaction(true);
					$pageposafter=$pageposbefore;
					//print $pageposafter.'-'.$pageposbefore;exit;
					$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
					$pdf->writeHTMLCell($posx_options-$posx_designation-$decalage, 3, $posx_designation+$decalage, $curY, $outputlangs->convToOutputCharset($label), 0, 1, false, true, 'J',true);

					$pageposafter=$pdf->getPage();
					$posyafter=$pdf->GetY();
					//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
					if ($posyafter > ($objmarge->page_hauteur - ($heightforfooter+$heightforinfotot)))	// There is no space left for total+free text
					{
						if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
						{
							$pdf->AddPage('','',true);
							if (! empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) self::pagehead($objmarge, $pdf, $object, 0, $outputlangs);
							$pdf->setPage($pageposafter+1);
						}
					}
					else
					{
						// We found a page break
						$showpricebeforepagebreak=0;
					}
				}
				else	// No pagebreak
				{
					$pdf->commitTransaction();
				}
				$posYAfterDescription=$pdf->GetY();

				$nexY = $pdf->GetY();
				$pageposafter=$pdf->getPage();

				$pdf->setPage($pageposbefore);
				$pdf->setTopMargin($objmarge->marge_haute);
				$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

				// We suppose that a too long description or photo were moved completely on next page
				if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
					$pdf->setPage($pageposafter); $curY = $tab_top_newpage + 10;
				}

				self::printLevel($objmarge, $pdf, $line, $curY, $posx_designation);

				// Print: Options
				if (!empty($line->total_options))
				{
					$pdf->SetXY($posx_options, $curY);
					$pdf->MultiCell($posx_montant-$posx_options-0.8, 3, price($line->total_options, 0, $outputlangs), 0, 'R', 0);
				}

				// Print: Montant
				$pdf->SetXY($posx_montant, $curY);
				$pdf->MultiCell($objmarge->page_largeur-$objmarge->marge_droite-$posx_montant-0.8, 3, price($line->total_ht, 0, $outputlangs), 0, 'R', 0);

				$nexY+=2;    // Passe espace entre les lignes

				// Detect if some page were added automatically and output _tableau for past pages
				while ($pagenb < $pageposafter)
				{
					$pdf->setPage($pagenb);
					if ($pagenb == 1)
					{
						self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top, $objmarge->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
					}
					else
					{
						self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top_newpage, $objmarge->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code);
					}

					$pagenb++;
					$pdf->setPage($pagenb);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
					if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) self::pagehead($objmarge, $pdf, $object, 0, $outputlangs);
				}
			}
		}

		// Show square
		if ($pagenb == 1)
		{
			self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top, $objmarge->page_hauteur - $tab_top - $heightforinfotot - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
			$bottomlasttab=$objmarge->page_hauteur - $heightforinfotot - $heightforfooter + 1;
		}
		else
		{
			self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top_newpage, $objmarge->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfooter, 0, $outputlangs, $hidetop, 0, $object->multicurrency_code);
			$bottomlasttab=$objmarge->page_hauteur - $heightforinfotot - $heightforfooter + 1;
		}

		// Affiche zone totaux
		$posy=self::tableau_tot($objmarge, $pdf, $object, $bottomlasttab, $outputlangs, $TTot);

		$pdf->Close();
		$pdf->Output($file,'F');

		if (empty($fromInfraS))		$pagecount = self::concat($outputlangs, array($origin_file, $file), $origin_file);	// InfraS change
		if (!empty($fromInfraS))	return $file;	// InfraS add
		if (empty($conf->global->SUBTOTAL_KEEP_RECAP_FILE)) unlink($file);
	}

	/**
	 * @param stdClass         $objmarge Fields: 'marge_gauche', …
	 * @param TCPDF            $pdf
	 * @param CommonObjectLine $line
	 * @param int              $curY
	 * @param int              $posx_designation
	 */
	private static function printLevel($objmarge, $pdf, $line, $curY, $posx_designation)
	{
		$level = $line->qty; // TODO à améliorer

		$pdf->SetXY($objmarge->marge_gauche, $curY);
		$pdf->MultiCell($posx_designation-$objmarge->marge_gauche-0.8, 5, $level, 0, 'L', 0);
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF     $pdf          Object PDF
	 *  @param  Object    $object       Object to show
	 *  @param  int       $showdetail   0=no, 1=yes
	 *  @param  Translate $outputlangs  Object lang for output
	 *  @return	void
	 */
	private static function pagehead(&$objmarge, &$pdf, &$object, $showdetail, $outputlangs)
	{
		global $conf,$mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$objmarge->page_hauteur);

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$posy=$objmarge->marge_haute;
		$posx=$objmarge->page_largeur-$objmarge->marge_droite-100;

		$pdf->SetXY($objmarge->marge_gauche,$posy);

		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo)
		{
			if (is_readable($logo))
			{
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $objmarge->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}

			$posy+=35;
		}
		else
		{
			$text=$mysoc->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');

			$posy+=15;
		}


		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','B', $default_font_size + 2);
		$pdf->SetXY($objmarge->marge_gauche,$posy);

		$key = 'subtotalPropalTitle';
		if ($object->element == 'commande') $key = 'subtotalCommandeTitle';
		elseif ($object->element == 'facture') $key = 'subtotalInvoiceTitle';
		elseif ($object->element == 'facturerec') $key = 'subtotalInvoiceTitle';

		$pdf->MultiCell(150, 4, $outputlangs->transnoentities($key, $object->ref, $object->thirdparty->name), '', 'L');

		$pdf->SetFont('','', $default_font_size);
		$pdf->SetXY($objmarge->page_largeur-$objmarge->marge_droite-40,$posy);
		$pdf->MultiCell(40, 4, dol_print_date($object->date, 'daytext'), '', 'R');

		$posy += 8;

		$pdf->SetFont('','B', $default_font_size + 2);
		$pdf->SetXY($objmarge->marge_gauche,$posy);
		$pdf->MultiCell(70, 4, $outputlangs->transnoentities('subtotalRecapLot'), '', 'L');

	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	private static function tableau(&$objmarge, &$pdf, $posx_designation, $posx_options, $posx_montant, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0, $currency='')
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',$default_font_size);

		if (empty($hidetop))
		{
			$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($objmarge->page_largeur - $objmarge->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4.5);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			if (! empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($objmarge->marge_gauche, $tab_top, $objmarge->page_largeur-$objmarge->marge_droite-$objmarge->marge_gauche, 8, 'F', null, explode(',',$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));


			$pdf->line($objmarge->marge_gauche, $tab_top, $objmarge->page_largeur-$objmarge->marge_droite, $tab_top);	// line prend une position y en 2eme param et 4eme param

			$pdf->SetXY($posx_designation, $tab_top+2);
			$pdf->MultiCell($posx_options - $posx_designation,2, $outputlangs->transnoentities("Designation"),'','L');
			$pdf->SetXY($posx_options, $tab_top+2);
			$pdf->MultiCell($posx_montant - $posx_options,2, $outputlangs->transnoentities("Options"),'','R');
			$pdf->SetXY($posx_montant, $tab_top+2);
			$pdf->MultiCell($objmarge->page_largeur - $objmarge->marge_droite - $posx_montant,2, $outputlangs->transnoentities("Amount"),'','R');

			$pdf->line($objmarge->marge_gauche, $tab_top+8, $objmarge->page_largeur-$objmarge->marge_droite, $tab_top+8);	// line prend une position y en 2eme param et 4eme param
		}
		else
		{
			$pdf->line($objmarge->marge_gauche, $tab_top-2, $objmarge->page_largeur-$objmarge->marge_droite, $tab_top-2);	// line prend une position y en 2eme param et 4eme param
		}

	}

	/**
	 * @param stdClass     $objmarge
	 * @param TCPDF        $pdf
	 * @param CommonObject $object
	 * @param int          $posy
	 * @param Translate    $outputlangs
	 * @param array        $TTot
	 * @return float|int
	 */
	private static function tableau_tot(&$objmarge, &$pdf, $object, $posy, $outputlangs, $TTot)
	{
		global $conf;

		$pdf->line($objmarge->marge_gauche, $posy, $objmarge->page_largeur-$objmarge->marge_droite, $posy);	// line prend une position y en 2eme param et 4eme param

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy+2;
		$tab2_hl = 4;
		$pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		$col1x = 120; $col2x = 170;
		if ($objmarge->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = ($objmarge->page_largeur - $objmarge->marge_droite - $col2x);

		$useborder=0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		// $total_ht = ($conf->multicurrency->enabled && $object->mylticurrency_tx != 1) ? $TTot['multicurrency_total_ht'] : $TTot['total_ht'];
		$total_ht = $TTot['total_ht'];
		$pdf->SetXY($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht, 0, $outputlangs), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);

		$atleastoneratenotnull=0;
		foreach($TTot['TTotal_tva'] as $tvakey => $tvaval)
		{
			if ($tvakey != 0)    // On affiche pas taux 0
			{
				$atleastoneratenotnull++;

				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

				$tvacompl='';
				if (preg_match('/\*/',$tvakey))
				{
					$tvakey=str_replace('*','',$tvakey);
					$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
				}
				$totalvat =$outputlangs->transnoentities("TotalVAT").' ';
				$totalvat.=vatrate($tvakey,1).$tvacompl;
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
			}
		}

		// Total TTC
		$index++;
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFillColor(224,224,224);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

		// $total_ttc = ($conf->multicurrency->enabled && $object->multiccurency_tx != 1) ? $TTot['multicurrency_total_ttc'] : $TTot['total_ttc'];
		$total_ttc = $TTot['total_ttc'];
		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs), $useborder, 'R', 1);

		$pdf->SetTextColor(0,0,0);

		$index++;
		return ($tab2_top + ($tab2_hl * $index));

	}

	/**
	 * Rect pdf
	 *
	 * @param	PDF		$pdf			Object PDF
	 * @param	float	$x				Abscissa of first point
	 * @param	float	$y		        Ordinate of first point
	 * @param	float	$l				??
	 * @param	float	$h				??
	 * @param	int		$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 * @param	int		$hidebottom		Hide bottom
	 * @return	void
	 */
	private static function printRect($pdf, $x, $y, $l, $h, $hidetop=0, $hidebottom=0)
	{
	    if (empty($hidetop) || $hidetop==-1) $pdf->line($x, $y, $x+$l, $y);
	    $pdf->line($x+$l, $y, $x+$l, $y+$h);
	    if (empty($hidebottom)) $pdf->line($x+$l, $y+$h, $x, $y+$h);
	    $pdf->line($x, $y+$h, $x, $y);
	}

	/**
	 * @param Translate $outputlangs
	 * @param array     $files
	 * @param string    $fileoutput
	 * @return int
	 */
	public static function concat(&$outputlangs, $files, $fileoutput='')
	{
		global $conf;

		if (empty($fileoutput)) $fileoutput = $file[0];

		$pdf=pdf_getInstance();
        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);


		foreach($files as $file)
		{
			$pagecount = $pdf->setSourceFile($file);
			for ($i = 1; $i <= $pagecount; $i++)
			{
				$tplidx = $pdf->ImportPage($i);
				$s = $pdf->getTemplatesize($tplidx);
				$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplidx);
			}
		}

		$pdf->Output($fileoutput,'F');
		if (! empty($conf->global->MAIN_UMASK)) @chmod($file, octdec($conf->global->MAIN_UMASK));

		return $pagecount;
	}

	/**
	 * Méthode pour savoir si une ligne fait partie d'un bloc Compris/Non Compris
	 *
	 * @param PropaleLigne|OrderLine|FactureLigne	$line
	 * @return	true or false
	 */
	public static function hasNcTitle(&$line)
	{
		if(isset($line->has_nc_title)) return $line->has_nc_title;

		$TTitle = self::getAllTitleFromLine($line);
		foreach ($TTitle as &$line_title)
		{
			if (!empty($line_title->array_options['options_subtotal_nc']))
			{
				$line->has_nc_title = true;
				return true;
			}
		}

		$line->has_nc_title = false;
		return false;
	}

	/**
	 * Méthode pour récupérer le titre de la ligne
	 *
	 * @param PropaleLigne|OrderLine|FactureLigne	$line
	 * @return	string
	 */
	public static function getTitleLabel($line)
	{
		$title = $line->label;
		if (empty($title)) $title = !empty($line->description) ? $line->description : $line->desc;
		return $title;
	}
}
