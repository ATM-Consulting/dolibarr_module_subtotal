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
	
	public static function getTotalBlockFromTitle(&$object, &$line)
	{
		$TTot = array('total_options' => 0, 'total_ht' => 0, 'total_tva' => 0, 'total_ttc' => 0, 'TTotal_tva' => array(), 'multicurrency_total_ht' => 0, 'multicurrency_total_tva' => 0, 'multicurrency_total_ttc' => 0, 'TTotal_tva_multicurrency' => array());
		
		foreach ($object->lines as &$l)
		{
			if ($l->rang <= $line->rang) continue;
			elseif (self::isSubtotal($l) && self::getNiveau($l) == $line->qty) break;
			elseif (self::isModSubtotalLine($l)) continue;
			
			if ($l->qty == 0) $TTot['total_options'] += $l->subprice;
			else
			{
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
		
		return $TTot;
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
		return $line->special_code == self::$module_number && $line->product_type == 9 && $line->qty <= 9;
	}
	
	public static function isSubtotal(&$line)
	{
		return $line->special_code == self::$module_number && $line->product_type == 9 && $line->qty >= 90;
	}
	
	public static function isModSubtotalLine(&$line)
	{
		return self::isTitle($line) || self::isSubtotal($line);
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
						if (empty($object->lines[$y]->array_options)) $object->lines[$y]->fetch_optionals();
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
	
	/**
	 * Ajoute une page de récap à la génération du PDF
	 * Le tableau total en bas du document se base sur les totaux des titres niveau 1 pour le moment
	 */
	public static function addRecapPage(&$parameters, &$origin_pdf)
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
		
		$TLine = self::getAllTitleFromDocument($object, true);
		if (!empty($TLine))
		{
			$hidetop = 0;
				
			$iniY = $tab_top + 10;
			$curY = $tab_top + 10;
			$nexY = $tab_top + 10;
		
			$TTot = array('total_ht' => 0, 'total_ttc' => 0, 'TTotal_tva' => array());
			
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
				$label = !empty($line->label) ? $line->label : $line->desc;
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
		
		$pagecount = self::concat($outputlangs, array($origin_file, $file), $origin_file);
		
		if (empty($conf->global->SUBTOTAL_KEEP_RECAP_FILE)) unlink($file);
	}
	
	private static function printLevel($objmarge, $pdf, $line, $curY, $posx_designation)
	{
		$level = $line->qty; // TODO à améliorer
		
		$pdf->SetXY($objmarge->marge_gauche, $curY);
		$pdf->MultiCell($posx_designation-$objmarge->marge_gauche-0.8, 5, $level, 0, 'L', 0);
	}
	
	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showdetail    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
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
}
