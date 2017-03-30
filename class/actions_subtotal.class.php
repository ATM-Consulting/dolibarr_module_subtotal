<?php
class ActionsSubtotal
{
	
	function __construct($db)
	{
		global $langs;
		
		$this->db = $db;
		$langs->load('subtotal@subtotal');
	}
	
	/** Overloading the doActions function : replacing the parent's function with the one below
	 * @param      $parameters  array           meta datas of the hook (context, etc...)
	 * @param      $object      CommonObject    the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param      $action      string          current action (if set). Generally create or edit or null
	 * @param      $hookmanager HookManager     current hook manager
	 * @return     void
	 */
    
    var $module_number = 104777;
    
    function formObjectOptions($parameters, &$object, &$action, $hookmanager) 
    {
      	global $langs,$db,$user, $conf;
		
		$langs->load('subtotal@subtotal');
		
		$contexts = explode(':',$parameters['context']);
		
		if(in_array('ordercard',$contexts) || in_array('propalcard',$contexts) || in_array('invoicecard',$contexts)) {
        		
        	if ($object->statut == 0  && $user->rights->{$object->element}->creer) {
			
			
				if($object->element=='facture')$idvar = 'facid';
				else $idvar='id';
				
				
				if($action=='add_title_line' || $action=='add_total_line' || $action=='add_subtitle_line' || $action=='add_subtotal_line') {
					
					$level = GETPOST('level', 'int'); //New avec SUBTOTAL_USE_NEW_FORMAT
					
					if($action=='add_title_line') {
						$title = GETPOST('title');
						if(empty($title)) $title = $langs->trans('title');
						$qty = $level<1 ? 1 : $level ;
					}
					else if($action=='add_subtitle_line') {
						$title = GETPOST('title');
						if(empty($title)) $title = $langs->trans('subtitle');
						$qty = 2;
					}
					else if($action=='add_subtotal_line') {
						$title = $langs->trans('SubSubTotal');
						$qty = 98;
					}
					else {
						$title = $langs->trans('SubTotal');
						$qty = $level ? 100-$level : 99;
					}
					dol_include_once('/subtotal/class/subtotal.class.php');
	    			TSubtotal::addSubTotalLine($object, $title, $qty);
				}
				else if($action==='ask_deleteallline') {
						$form=new Form($db);
						
						$lineid = GETPOST('lineid','integer');
						$TIdForGroup = $this->getArrayOfLineForAGroup($object, $lineid);
					
						$nbLines = count($TIdForGroup);
					
						$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('deleteWithAllLines'), $langs->trans('ConfirmDeleteAllThisLines',$nbLines), 'confirm_delete_all_lines','',0,1);
						print $formconfirm;
				}

				if (!empty($conf->global->SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE))
				{
					$this->showSelectTitleToAdd($object);
				}

				
				if($action!='editline') {
					
					// New format is for 3.8
					if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT)) 
					{
						$this->printNewFormat($object, $conf, $langs, $idvar);
					}
					else 
					{
						$this->printOldFormat($object, $conf, $langs, $idvar);
					}
				}
			}
		}
		elseif ((!empty($parameters['currentcontext']) && $parameters['currentcontext'] == 'orderstoinvoice') || in_array('orderstoinvoice',$contexts))
		{
			?>
			<script type="text/javascript">
				$(function() {
					var tr = $("<tr><td><?php echo $langs->trans('subtotal_add_title_bloc_from_orderstoinvoice'); ?></td><td><input type='checkbox' value='1' name='subtotal_add_title_bloc_from_orderstoinvoice' checked='checked' /></td></tr>")
					$("textarea[name=note]").closest('tr').after(tr);
				});
			</script>
			<?php
			
		}

		return 0;
	}
     
	function printNewFormat(&$object, &$conf, &$langs, $idvar)
	{
		if (empty($conf->global->SUBTOTAL_ALLOW_ADD_BLOCK)) return false;
		
		?>
		 	<script type="text/javascript">
				$(document).ready(function() {
					$('div.fiche div.tabsAction').append('<br />');
					
					var label = "<label for='subtotal_line_level'><?php echo $langs->trans('SubtotalLabel'); ?></label>";
					var select = "<select name='subtotal_line_level'>";
					for (var i=1;i<10;i++)
					{
						select += "<option value="+i+"><?php echo $langs->trans('Level'); ?> "+i+"</option>";
					}
					select += "</select>";
					
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction">'+label+select+'</div>');
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_title_line" rel="add_title_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddTitle' )?></a></div>');
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_total_line" rel="add_total_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddSubTotal')?></a></div>');
					
					function promptSubTotal(titleDialog, label, url_to, url_ajax) {
					     $( "#dialog-prompt-subtotal" ).remove();
					     $('body').append('<div id="dialog-prompt-subtotal"><input id="sub-total-title" size=30 value="'+label+'" /></div>');
					    
					     $( "#dialog-prompt-subtotal" ).dialog({
	                        resizable: false,
	                        height:140,
	                        modal: true,
	                        title: titleDialog,
	                        buttons: {
	                            "Ok": function() {
	                                $.get(url_ajax+'&title='+encodeURIComponent( $(this).find('#sub-total-title').val() ), function() {
	                                    document.location.href=url_to;
	                                })
	
                                    $( this ).dialog( "close" );
	                            },
	                            "<?php echo $langs->trans('Cancel') ?>": function() {
	                                $( this ).dialog( "close" );
	                            }
	                        }
	                     });
					}
					
					$('a[rel=add_title_line]').click(function() 
					{
						promptSubTotal("<?php echo $langs->trans('YourTitleLabel') ?>"
						     , "<?php echo $langs->trans('title'); ?>"
						     , '?<?php echo $idvar ?>=<?php echo $object->id; ?>'
						     , '?<?php echo $idvar ?>=<?php echo $object->id; ?>&action=add_title_line&level='+$('select[name=subtotal_line_level]').val()
						     
						);
					});
					
					$('a[rel=add_total_line]').click(function() {
						$.get('?<?php echo $idvar ?>=<?php echo $object->id ?>&action=add_total_line&level='+$('select[name=subtotal_line_level]').val(), function() {
							document.location.href='?<?php echo $idvar ?>=<?php echo $object->id; ?>';
						});
						
					});
				});
		 	</script>
		 <?php
	}
	 
	function printOldFormat(&$object, &$conf, &$langs, $idvar)
	{
		if (empty($conf->global->SUBTOTAL_ALLOW_ADD_BLOCK)) return false;
		
		?>
			<script type="text/javascript">
				$(document).ready(function() {
					
					<?php
						if($conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL==1) {
							?>$('div.fiche div.tabsAction').append('<br /><br />');<?php
						}
					?>
					
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_title_line" rel="add_title_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddTitle' )?></a></div>');
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_total_line" rel="add_total_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddSubTotal')?></a></div>');
					
					<?php
						if($conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL==1) {
						?>
							$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_subtitle_line" rel="add_subtitle_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddSubTitle' )?></a></div>');
							$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_subtotal_line" rel="add_subtotal_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddSubSubTotal')?></a></div>');
	
						<?php								
						}
					?>
					
					function promptSubTotal(titleDialog, label, url_to, url_ajax) {
					    
					     $( "#dialog-prompt-subtotal" ).remove();
					     $('body').append('<div id="dialog-prompt-subtotal"><input id="sub-total-title" size=30 value="'+label+'" /></div>');
					    
					     $( "#dialog-prompt-subtotal" ).dialog({
	                        resizable: false,
	                        height:140,
	                        modal: true,
	                        title: titleDialog,
	                        buttons: {
	                            "Ok": function() {
	                                
	                                $.get(url_ajax+'&title='+encodeURIComponent( $(this).find('#sub-total-title').val() ), function() {
	                                    document.location.href=url_to;
	                                })
	
	                                    $( this ).dialog( "close" );
	                                
	                            },
	                            "<?php echo $langs->trans('Cancel') ?>": function() {
	                                $( this ).dialog( "close" );
	                            }
	                        }
	                     });
	                     
					}
					
					$('a[rel=add_title_line]').click(function() {
						
						
						promptSubTotal("<?php echo $langs->trans('YourTitleLabel') ?>"
						     , "<?php echo $langs->trans('title') ?>"
						     , '?<?php echo $idvar ?>=<?php echo $object->id ?>'
						     , '?<?php echo $idvar ?>=<?php echo $object->id ?>&action=add_title_line'
						);
						
						
					});
					$('a[rel=add_subtitle_line]').click(function() {
					    
					    promptSubTotal(
					        "<?php echo $langs->trans('YourTitleLabel') ?>"
					        , "<?php echo $langs->trans('title') ?>"
					        , '?<?php echo $idvar ?>=<?php echo $object->id ?>'
	                        , '?<?php echo $idvar ?>=<?php echo $object->id ?>&action=add_subtitle_line'
					    );
					    
					});
					
					$('a[rel=add_total_line]').click(function() {
						
						$.get('?<?php echo $idvar ?>=<?php echo $object->id ?>&action=add_total_line', function() {
							document.location.href='?<?php echo $idvar ?>=<?php echo $object->id ?>';
						});
						
					});
					
					$('a[rel=add_subtotal_line]').click(function() {
						
						$.get('?<?php echo $idvar ?>=<?php echo $object->id ?>&action=add_subtotal_line', function() {
							document.location.href='?<?php echo $idvar ?>=<?php echo $object->id ?>';
						});
						
					});
					
					
				});
				
			</script>
		<?php
	}
	 
	 
	function showSelectTitleToAdd(&$object)
	{
		global $langs;
		
		dol_include_once('/subtotal/class/subtotal.class.php');
		dol_include_once('/subtotal/lib/subtotal.lib.php');
		$TTitle = TSubtotal::getAllTitleFromDocument($object);
		
		?>
		<script type="text/javascript">
			$(function() {
				var add_button = $("#addline");
				
				if (add_button.length > 0)
				{
					add_button.closest('tr').prev('tr.liste_titre').children('td:last').addClass('center').text("<?php echo $langs->trans('subtotal_title_to_add_under_title'); ?>");
					var select_title = $(<?php echo json_encode(getHtmlSelectTitle($object)); ?>);
					
					add_button.before(select_title);
				}
			});
		</script>
		<?php
	}
	
	
	function formBuilddocOptions($parameters) {
	/* Réponse besoin client */		
			
		global $conf, $langs, $bc;
			
		$action = GETPOST('action');	
		$TContext = explode(':',$parameters['context']);
		if (
				in_array('invoicecard',$TContext)
				|| in_array('propalcard',$TContext)
				|| in_array('ordercard',$TContext)
			)
	        {	
				$hideInnerLines	= isset( $_SESSION['subtotal_hideInnerLines_'.$parameters['modulepart']] ) ?  $_SESSION['subtotal_hideInnerLines_'.$parameters['modulepart']] : 0;
				$hidedetails	= isset( $_SESSION['subtotal_hidedetails_'.$parameters['modulepart']] ) ?  $_SESSION['subtotal_hidedetails_'.$parameters['modulepart']] : 0;	
					
				$var=false;
		     	$out.= '<tr '.$bc[$var].'>
		     			<td colspan="4" align="right">
		     				<label for="hideInnerLines">'.$langs->trans('HideInnerLines').'</label>
		     				<input type="checkbox" onclick="if($(this).is(\':checked\')) { $(\'#hidedetails\').prop(\'checked\', \'checked\')  }" id="hideInnerLines" name="hideInnerLines" value="1" '.(( $hideInnerLines ) ? 'checked="checked"' : '' ).' />
		     			</td>
		     			</tr>';
				
				$var=!$var;
				$out.= '<tr '.$bc[$var].'>
		     			<td colspan="4" align="right">
		     				<label for="hidedetails">'.$langs->trans('SubTotalhidedetails').'</label>
		     				<input type="checkbox" id="hidedetails" name="hidedetails" value="1" '.(( $hidedetails ) ? 'checked="checked"' : '' ).' />
		     			</td>
		     			</tr>';
				
				 
				if ( 
					(in_array('propalcard',$TContext) && !empty($conf->global->SUBTOTAL_PROPAL_ADD_RECAP))
					|| (in_array('ordercard',$TContext) && !empty($conf->global->SUBTOTAL_COMMANDE_ADD_RECAP))
					|| (in_array('invoicecard',$TContext) && !empty($conf->global->SUBTOTAL_INVOICE_ADD_RECAP))
				)
				{
					$var=!$var;
					$out.= '
						<tr '.$bc[$var].'>
							<td colspan="4" align="right">
								<label for="subtotal_add_recap">'.$langs->trans('subtotal_add_recap').'</label>
								<input type="checkbox" id="subtotal_add_recap" name="subtotal_add_recap" value="1" '.( GETPOST('subtotal_add_recap') ? 'checked="checked"' : '' ).' />
							</td>
						</tr>';
				}
				
				
				$this->resprints = $out;	
			}
			
		
        return 1;
	} 
	 
    function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
		
    	if (in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	
        }
		
        return 0;
    }
	
	function ODTSubstitutionLine(&$parameters, &$object, $action, $hookmanager) {
		global $conf;
		
		if($action === 'builddoc') {
			
			$line = &$parameters['line'];
			$object = &$parameters['object'];
			$substitutionarray = &$parameters['substitutionarray'];
			
			if($line->product_type == 9 && $line->special_code == $this->module_number) {
				$substitutionarray['line_modsubtotal'] = 1;	
				
				$substitutionarray['line_price_ht']
					 = $substitutionarray['line_price_vat'] 
					 = $substitutionarray['line_price_ttc']
					 = $substitutionarray['line_vatrate']
					 = $substitutionarray['line_qty']
					 = $substitutionarray['line_up'] 
					 = '';
				
				if($line->qty>90) {
					$substitutionarray['line_modsubtotal_total'] = true;
					
					list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, $conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL, 1);
					
					$substitutionarray['line_price_ht'] = $total;
					$substitutionarray['line_price_vat'] = $total_tva;
					$substitutionarray['line_price_ttc'] = $total_ttc;
				} else {
					$substitutionarray['line_modsubtotal_title'] = true;
				}
				
				
			}	
			else{
				$substitutionarray['line_not_modsubtotal'] = true;
				$substitutionarray['line_modsubtotal'] = 0;
			}
			
		}
		
	}
	
	function createFrom($parameters, &$object, $action, $hookmanager) {
	
		if (
				in_array('invoicecard',explode(':',$parameters['context']))
				|| in_array('propalcard',explode(':',$parameters['context']))
				|| in_array('ordercard',explode(':',$parameters['context']))
		) {
			
			global $db;
			
			$objFrom = $parameters['objFrom'];
			
			foreach($objFrom->lines as $k=> &$lineOld) {
				
					if($lineOld->product_type == 9 && $lineOld->info_bits > 0 ) {
							
							$line = & $object->lines[$k];
				
							$idLine = (int) ($line->id ? $line->id : $line->rowid); 
				
							$db->query("UPDATE ".MAIN_DB_PREFIX.$line->table_element."
							SET info_bits=".(int)$lineOld->info_bits."
							WHERE rowid = ".$idLine."
							");
						
					}
				
				
			}
			
			
		}
		
	}
	
	function doActions($parameters, &$object, $action, $hookmanager)
	{
		global $conf,$langs;
		
		dol_include_once('/subtotal/class/subtotal.class.php');
		dol_include_once('/subtotal/lib/subtotal.lib.php');
		
		if($object->element=='facture') $idvar = 'facid';
		else $idvar = 'id';
			
		if ($action == 'updateligne' || $action == 'updateline')
		{
			$found = false;
			$lineid = GETPOST('lineid', 'int');
			foreach ($object->lines as &$line)
			{
				if ($line->id == $lineid && (TSubtotal::isTitle($line) || TSubtotal::isSubtotal($line)) )
				{
					$found = true;
					_updateSubtotalLine($object, $line);
					_updateSubtotalBloc($object, $line);
				}
			}
			
			if ($found)
			{
				header('Location: '.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id);
				exit; // Surtout ne pas laisser Dolibarr faire du traitement sur le updateligne sinon ça plante les données de la ligne
			}
		}
		else if($action === 'builddoc') {
			
			if (
				in_array('invoicecard',explode(':',$parameters['context']))
				|| in_array('propalcard',explode(':',$parameters['context']))
				|| in_array('ordercard',explode(':',$parameters['context']))
			)
	        {								
				if(in_array('invoicecard',explode(':',$parameters['context']))) {
					$sessname = 'subtotal_hideInnerLines_facture';	
					$sessname2 = 'subtotal_hidedetails_facture';
				}
				elseif(in_array('propalcard',explode(':',$parameters['context']))) {
					$sessname = 'subtotal_hideInnerLines_propal';
					$sessname2 = 'subtotal_hidedetails_propal';	
				}
				elseif(in_array('ordercard',explode(':',$parameters['context']))) {
					$sessname = 'subtotal_hideInnerLines_commande';
					$sessname2 = 'subtotal_hidedetails_commande';	
				}
				else {
					$sessname = 'subtotal_hideInnerLines_unknown';
					$sessname2 = 'subtotal_hidedetails_unknown';
				}
								
				$hideInnerLines = (int)GETPOST('hideInnerLines');
				$_SESSION[$sessname] = $hideInnerLines;		
				
				$hidedetails= (int)GETPOST('hidedetails');	
				$_SESSION[$sessname2] = $hidedetails;
				
	           	foreach($object->lines as &$line) {
					if ($line->product_type == 9 && $line->special_code == $this->module_number) {
					    
                        if($line->qty>=90) {
                            $line->modsubtotal_total = 1;
                        }
                        else{
                            $line->modsubtotal_title = 1;
                        }
                        
						$line->total_ht = $this->getTotalLineFromObject($object, $line, $conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL);
					}
	        	}
	        }
			
		}
		else if($action === 'confirm_delete_all_lines' && GETPOST('confirm')=='yes') {
			
			$Tab = $this->getArrayOfLineForAGroup($object, GETPOST('lineid'));
			
			foreach($Tab as $idLine) {
				/**
				 * @var $object Facture
				 */
				if($object->element=='facture') $object->deleteline($idLine);
				/**
				 * @var $object Propal
				 */
				else if($object->element=='propal') $object->deleteline($idLine);
				/**
				 * @var $object Commande
				 */
				else if($object->element=='commande') $object->deleteline($idLine);
			}
			
			header('location:?id='.$object->id);
			exit;
			
		}
		else if ($action == 'duplicate')
		{
			$lineid = GETPOST('lineid', 'int');
			$nbDuplicate = TSubtotal::duplicateLines($object, $lineid, true);
			
			if ($nbDuplicate > 0) setEventMessage($langs->trans('subtotal_duplicate_success', $nbDuplicate));
			elseif ($nbDuplicate == 0) setEventMessage($langs->trans('subtotal_duplicate_lineid_not_found'), 'warnings');
			else setEventMessage($langs->trans('subtotal_duplicate_error'), 'errors');
			
			header('Location: ?id='.$object->id);
			exit;
		}
		
		return 0;
	}
	
	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		return 0;
	}

	function getArrayOfLineForAGroup(&$object, $lineid) {
		$rang = $line->rang;
		$qty_line = $line->qty;
		
		$total = 0;
		
		$found = false;

		$Tab= array();
		
		foreach($object->lines as $l) {
		
			if($l->rowid == $lineid) {
				$found = true;
				$qty_line = $l->qty;
			}
			
			if($found) {
				
				$Tab[] = $l->rowid;
				
				if($l->special_code==$this->module_number && (($l->qty==99 && $qty_line==1) || ($l->qty==98 && $qty_line==2))   ) {
					break; // end of story
				}
			}
			
			
		}
		
		
		return $Tab;
		
	}

	/**
	 *  TODO le calcul est faux dans certains cas,  exemple :
	 *	T1
	 *		|_ l1 => 50 €
	 *		|_ l2 => 40 €
	 *		|_ T2
	 *			|_l3 => 100 €
	 *		|_ ST2
	 *		|_ l4 => 23 €
	 *	|_ ST1
	 * 
	 * On obtiens ST2 = 100 ET ST1 = 123 €
	 * Alors qu'on devrais avoir ST2 = 100 ET ST1 = 213 €
	 */
	function getTotalLineFromObject(&$object, &$line, $use_level=false, $return_all=0) {
		
		$rang = $line->rang;
		$qty_line = $line->qty;
		
		$total = 0;
		$total_tva = 0;
		$total_ttc = 0;
		$TTotal_tva = array();
		
		foreach($object->lines as $l) {
			//print $l->rang.'>='.$rang.' '.$total.'<br/>';
			if($l->rang>=$rang) {
				//echo 'return!<br>';
				if (!$return_all) return $total;
				else return array($total, $total_tva, $total_ttc, $TTotal_tva);
			}
			else if(TSubtotal::isTitle($l)) 
		  	{
				$total = 0;
				$total_tva = 0;
				$total_ttc = 0;
				$TTotal_tva = array();
			}
			elseif(!TSubtotal::isTitle($l) && !TSubtotal::isSubtotal($l)) {
				$total += $l->total_ht;
				$total_tva += $l->total_tva;
				$TTotal_tva[$l->tva_tx] += $l->total_tva;
				$total_ttc += $l->total_ttc;
			}
			
		}
		if (!$return_all) return $total;
		else return array($total, $total_tva, $total_ttc, $TTotal_tva);
	}

	/**
	 * @param $pdf          TCPDF               PDF object
	 * @param $object       CommonObject        dolibarr object
	 * @param $line         CommonObjectLine    dolibarr object line
	 * @param $label        string
	 * @param $description  string
	 * @param $posx         float               horizontal position
	 * @param $posy         float               vertical position
	 * @param $w            float               width
	 * @param $h            float               height
	 */
	function pdf_add_total(&$pdf,&$object, &$line, $label, $description,$posx, $posy, $w, $h) {
		global $conf,$subtotal_last_title_posy;
		
		$hideInnerLines = (int)GETPOST('hideInnerLines');
		if (!empty($conf->global->SUBTOTAL_ONE_LINE_IF_HIDE_INNERLINES) && $hideInnerLines && !empty($subtotal_last_title_posy))
		{
			$posy = $subtotal_last_title_posy;
			$subtotal_last_title_posy = null;
		}
		
		$hidePriceOnSubtotalLines = (int) GETPOST('hide_price_on_subtotal_lines');
		
		$set_pagebreak_margin = false;
		if(method_exists('Closure','bind')) {
			$pageBreakOriginalValue = $pdf->AcceptPageBreak();
			$sweetsThief = function ($pdf) {
		    		return $pdf->bMargin ;
			};
			$sweetsThief = Closure::bind($sweetsThief, null, $pdf);
	
			$bMargin  = $sweetsThief($pdf);
	
			$pdf->SetAutoPageBreak( false );

			$set_pagebreak_margin = true;			
		}
		
			
		if($line->qty==99)
			$pdf->SetFillColor(220,220,220);
		elseif ($line->qty==98)
			$pdf->SetFillColor(230,230,230);
		else
			$pdf->SetFillColor(240,240,240);
		
		$style = 'B';
		if (!empty($conf->global->SUBTOTAL_SUBTOTAL_STYLE)) $style = $conf->global->SUBTOTAL_SUBTOTAL_STYLE;
		
		$pdf->SetFont('', $style, 9);
		
		$pdf->writeHTMLCell($w, $h, $posx, $posy, $label, 0, 1, false, true, 'R',true);
//		var_dump($bMargin);
		$pageAfter = $pdf->getPage();
		
		//Print background
		$cell_height = $pdf->getStringHeight($w, $label);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(200-$posx, $cell_height, '', 0, '', 1);
		
		if (!$hidePriceOnSubtotalLines) {
			$total_to_print = price($line->total);
			
			if (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS))
			{
				$TTitle = TSubtotal::getAllTitleFromLine($line);
				foreach ($TTitle as &$line_title)
				{
					if (!empty($line_title->array_options['options_subtotal_nc']))
					{
						$total_to_print = ''; // TODO Gestion "Compris/Non compris", voir si on affiche une annotation du genre "NC"
						break;
					}
				}
			}
			else if($total_to_print) {
				list($total, $total_tva, $total_ttc) = $this->getTotalLineFromObject($object, $line, $conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL, 1);

				$total_to_print = price($total);
				$line->total_ht = $total;
				$line->total = $total;
				$line->total_tva = $total_tva;
				$line->total_ttc = $total_ttc;
			}
			
			$pdf->SetXY($pdf->postotalht, $posy);
			if($set_pagebreak_margin) $pdf->SetAutoPageBreak( $pageBreakOriginalValue , $bMargin);
			$pdf->MultiCell($pdf->page_largeur-$pdf->marge_droite-$pdf->postotalht, 3, $total_to_print, 0, 'R', 0);
		}
		else{
			if($set_pagebreak_margin) $pdf->SetAutoPageBreak( $pageBreakOriginalValue , $bMargin);
		}
		
		$posy = $posy + $cell_height;
		$pdf->SetXY($posx, $posy); 
			
		
	}

	/**
	 * @param $pdf          TCPDF               PDF object
	 * @param $object       CommonObject        dolibarr object
	 * @param $line         CommonObjectLine    dolibarr object line
	 * @param $label        string
	 * @param $description  string
	 * @param $posx         float               horizontal position
	 * @param $posy         float               vertical position
	 * @param $w            float               width
	 * @param $h            float               height
	 */
	function pdf_add_title(&$pdf,&$object, &$line, $label, $description,$posx, $posy, $w, $h) {
		
		global $db,$conf,$subtotal_last_title_posy;
		
		$subtotal_last_title_posy = $posy;
		$pdf->SetXY ($posx, $posy);
		
		$hideInnerLines = (int)GETPOST('hideInnerLines');
		
		
 
		$style = ($line->qty==1) ? 'BU' : 'BUI';
		if (!empty($conf->global->SUBTOTAL_TITLE_STYLE)) $style = $conf->global->SUBTOTAL_TITLE_STYLE;
		
		if($hideInnerLines) {
			if($line->qty==1)$pdf->SetFont('', $style, 9);
			else 
			{
				if (!empty($conf->global->SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES)) $style = $conf->global->SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES;
				$pdf->SetFont('', $style, 9);
			}
		}
		else {

			if($line->qty==1)$pdf->SetFont('', $style, 9); //TODO if super utile
			else $pdf->SetFont('', $style, 9);
			
		}
		
		$pdf->MultiCell($w, $h, $label, 0, 'L');
		
		if($description && !$hidedesc) {
			$posy = $pdf->GetY();
			
			$pdf->SetFont('', '', 8);
			
			$pdf->writeHTMLCell($w, $h, $posx, $posy, $description, 0, 1, false, true, 'J',true);

		}
		
	}

	function pdf_writelinedesc_ref($parameters=array(), &$object, &$action='') {
	// ultimate PDF hook O_o
		
		return $this->pdf_writelinedesc($parameters,$object,$action);
		
	}

	function isModSubtotalLine(&$parameters, &$object) {
		
		if(is_array($parameters)) {
			$i = & $parameters['i'];	
		}
		else {
			$i = (int)$parameters;
		}
		
		
		if($object->lines[$i]->special_code == $this->module_number && $object->lines[$i]->product_type == 9) {
			return true;
		}
		
		return false;
		
	}

	function pdf_getlineqty($parameters=array(), &$object, &$action='') {
		global $conf;
		
		if($this->isModSubtotalLine($parameters,$object) ){
			
			$this->resprints = ' ';
			
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
			
		}
		elseif (!empty($conf->global->SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY))
		{
			$hideInnerLines = (int)GETPOST('hideInnerLines');
			$hidedetails = (int)GETPOST('hidedetails');
			if (empty($hideInnerLines) && !empty($hidedetails))
			{
				$this->resprints = $object->lines[$parameters['i']]->qty;
			}
		}
		
		return 0;
	}
	
	function pdf_getlinetotalexcltax($parameters=array(), &$object, &$action='') {
		global $conf;
			
		if($this->isModSubtotalLine($parameters,$object) ){
			
			$this->resprints = ' ';
			
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
			
		}
		elseif (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS))
		{
			if(is_array($parameters)) $i = & $parameters['i'];
			else $i = (int)$parameters;
			
			$TTitle = TSubtotal::getAllTitleFromLine($object->lines[$i]);
			foreach ($TTitle as &$line_title)
			{
				if (!empty($line_title->array_options['options_subtotal_nc']))
				{
					$this->resprints = ' ';
					return 1;
				}
			}
		}
			
		
		return 0;
	}
	
	function pdf_getlinetotalwithtax($parameters=array(), &$object, &$action='') {
		if($this->isModSubtotalLine($parameters,$object) ){
			
			$this->resprints = ' ';
		
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		
		return 0;
	}
	
	function pdf_getlineunit($parameters=array(), &$object, &$action='') {
		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';
		
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		
		return 0;
	}
	
	function pdf_getlineupexcltax($parameters=array(), &$object, &$action='') {
		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';
		
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		
		return 0;
	}
	
	function pdf_getlineupwithtax($parameters=array(), &$object, &$action='') {
		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		
		return 0;
	}
	
	function pdf_getlinevatrate($parameters=array(), &$object, &$action='') {
		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';
			
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		
		return 0;
	}
		
	function pdf_getlineprogress($parameters=array(), &$object, &$action) {
		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';
			if((float)DOL_VERSION<=3.4) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		
		return 0;
	}
	
	function add_numerotation(&$object) {
		global $conf;
		
		if(!empty($conf->global->SUBTOTAL_USE_NUMEROTATION)) {
		
			$TLevelTitre = array();
			$prevlevel = 0;
		
			foreach($object->lines as $k=>&$line) 
			{
				if ($line->id > 0 && $this->isModSubtotalLine($k, $object) && $line->qty <= 10)
				{
					$TLineTitle[] = &$line;
				}
			}
			
			if (!empty($TLineTitle)) $TTitleNumeroted = $this->formatNumerotation($TLineTitle);
		}
		
	}

	// TODO ne gère pas encore la numération des lignes "Totaux"
	private function formatNumerotation(&$TLineTitle, $line_reference='', $level=1, $prefix_num=0)
	{
		$TTitle = array();
		
		$i=1;
		$j=0;
		foreach ($TLineTitle as $k => &$line)
		{
			if (!empty($line_reference) && $line->rang <= $line_reference->rang) continue;
			if (!empty($line_reference) && $line->qty <= $line_reference->qty) break;
			
			if ($line->qty == $level)
			{
				$TTitle[$j]['numerotation'] = ($prefix_num == 0) ? $i : $prefix_num.'.'.$i;
				//var_dump('Prefix == '.$prefix_num.' // '.$line->desc.' ==> numerotation == '.$TTitle[$j]['numerotation'].'   ###    '.$line->qty .'=='. $level);
				if (empty($line->label))
				{
					$line->label = !empty($line->desc) ? $line->desc : $line->description;
					$line->desc = $line->description = '';
				}
				
				$line->label = $TTitle[$j]['numerotation'].' '.$line->label;
				$TTitle[$j]['line'] = &$line;
				
				$deep_level = $line->qty;
				do {
					$deep_level++;
					$TTitle[$j]['children'] = $this->formatNumerotation($TLineTitle, $line, $deep_level, $TTitle[$j]['numerotation']);
				} while (empty($TTitle[$j]['children']) && $deep_level <= 10); // Exemple si un bloc Titre lvl 1 contient pas de sous lvl 2 mais directement un sous lvl 5
				// Rappel on peux avoir jusqu'a 10 niveau de titre
				
				$i++;
				$j++;
			}
		}

		return $TTitle;
	}
	
	function setDocTVA(&$pdf, &$object) {
		
		$hidedetails = (int)GETPOST('hidedetails');
		
		if(empty($hidedetails)) return false;
		
		// TODO can't add VAT to document without lines... :-/
		
		return true;
	}
	
	function beforePDFCreation($parameters=array(), &$object, &$action)
	{
		/**
		 * @var $pdf    TCPDF
		 */
		global $pdf,$conf;

		dol_include_once('/subtotal/class/subtotal.class.php');

		foreach($parameters as $key=>$value) {
			${$key} = $value;
		}
		
		$this->setDocTVA($pdf, $object);
		
		$this->add_numerotation($object);	
		
		
		$hideInnerLines = (int)GETPOST('hideInnerLines');
		$hidedetails = (int)GETPOST('hidedetails');
		
		if ($hideInnerLines) { // si c une ligne de titre
	    	$fk_parent_line=0;
			$TLines =array();
		
			$original_count=count($object->lines);
		
			foreach($object->lines as $k=>&$line) 
			{
				if($line->product_type==9 && $line->rowid>0) 
				{
					$fk_parent_line = $line->rowid;
					
					if($line->qty>90 && $line->total==0) 
					{
						/*$total = $this->getTotalLineFromObject($object, $line, $conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL);
						
						$line->total_ht = $total;
						$line->total = $total;
						*/
						list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, $conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL, 1);
						
						$line->TTotal_tva = $TTotal_tva;
						$line->total_ht = $total;
						$line->total_tva = $total_tva;
						$line->total = $line->total_ht;
					} 
						
				} 
			
				if ($hideInnerLines)
				{
					if($line->product_type==9 && $line->rowid>0) 
					{
						$TLines[] = $line; //Cas où je doit cacher les produits et afficher uniquement les sous-totaux avec les titres
					}
					elseif (!TSubtotal::getParentTitleOfLine($object, $k)) {
						$TLines[] = $line;
					}
				}
				elseif ($hidedetails)
				{
					$TLines[] = $line; //Cas où je cache uniquement les prix des produits	
				}
				
				if ($line->product_type != 9) { // jusqu'au prochain titre ou total
					//$line->fk_parent_line = $fk_parent_line;
					
				}
			
				/*if($hideTotal) {
					$line->total = 0;
					$line->subprice= 0;
				}*/
			}
			
			$object->lines = $TLines;
			//var_dump($original_count,$i,count($object->lines));
			if($i>count($object->lines)) {
				$this->resprints = '';
				return 0;
			}
	    }
		
		return 0;
	}

	function pdf_writelinedesc($parameters=array(), &$object, &$action)
	{
		/**
		 * @var $pdf    TCPDF
		 */
		global $pdf,$conf;

		foreach($parameters as $key=>$value) {
			${$key} = $value;
		}
		
		$hideInnerLines = (int)GETPOST('hideInnerLines');
		$hidedetails = (int)GETPOST('hidedetails');
		
		if($this->isModSubtotalLine($parameters,$object) ){			
			
				$line = &$object->lines[$i];
				
				if($line->info_bits>0) { // PAGE BREAK
					$pdf->addPage();
					$posy = $pdf->GetY();
				}
				
				if($line->label=='') {
					$label = $outputlangs->convToOutputCharset($line->desc);
					$description='';
				}
				else {
					$label = $outputlangs->convToOutputCharset($line->label);
					$description=$outputlangs->convToOutputCharset(dol_htmlentitiesbr($line->desc));
				}
				
				if($line->qty>90) {
					
					if ($conf->global->SUBTOTAL_USE_NEW_FORMAT)	$label .= ' '.$this->getTitle($object, $line);
					
					$pageBefore = $pdf->getPage();
					$this->pdf_add_total($pdf,$object, $line, $label, $description,$posx, $posy, $w, $h);
					$pageAfter = $pdf->getPage();	

					if($pageAfter>$pageBefore) {
						//print "ST $pageAfter>$pageBefore<br>";
						$pdf->rollbackTransaction(true);	
						$pdf->addPage('','', true);
						$posy = $pdf->GetY();
						$this->pdf_add_total($pdf,$object, $line, $label, $description,$posx, $posy, $w, $h);
						$posy = $pdf->GetY();
						//print 'add ST'.$pdf->getPage().'<br />';
					}
				
					$posy = $pdf->GetY();
					
				}	
				else{
					$pageBefore = $pdf->getPage();

					$this->pdf_add_title($pdf,$object, $line, $label, $description,$posx, $posy, $w, $h); 
					$pageAfter = $pdf->getPage();	

					
					/*if($pageAfter>$pageBefore) {
						print "T $pageAfter>$pageBefore<br>";
						$pdf->rollbackTransaction(true);
						$pdf->addPage('','', true);
						print 'add T'.$pdf->getPage().' '.$line->rowid.' '.$pdf->GetY().' '.$posy.'<br />';
						
						$posy = $pdf->GetY();
						$this->pdf_add_title($pdf,$object, $line, $label, $description,$posx, $posy, $w, $h);
						$posy = $pdf->GetY();
					}
				*/
					$posy = $pdf->GetY();
				}
//	if($line->rowid==47) exit;
			
			return 1;
		}
		elseif (empty($object->lines[$parameters['i']]))
		{
			$this->resprints = -1;
		}

		/* TODO je desactive parce que je comprends pas PH Style, mais à test
		else {
			
			if($hideInnerLines) {
				$pdf->rollbackTransaction(true);
			}
			else {
				$labelproductservice=pdf_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline);
				$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1);
			}
			
		}*/


		
	}

	/**
	 * Permet de récupérer le titre lié au sous-total
	 * 
	 * @return string
	 */
	function getTitle(&$object, &$currentLine)
	{
		$res = '';
		
		foreach ($object->lines as $line)
		{
			if ($line->id == $currentLine->id) break;
			
			$qty_search = 100 - $currentLine->qty;
			
			if ($line->product_type == 9 && $line->special_code == $this->module_number && $line->qty == $qty_search) 
			{
				$res = ($line->label) ? $line->label : (($line->description) ? $line->description : $line->desc);
			}
		}
		
		return $res;
	}
	
	/**
	 * @param $parameters   array
	 * @param $object       CommonObject
	 * @param $action       string
	 * @param $hookmanager  HookManager
	 * @return int
	 */
	function printObjectLine ($parameters, &$object, &$action, $hookmanager){
		
		global $conf,$langs,$user,$db;
		
		$num = &$parameters['num'];
		$line = &$parameters['line'];
		$i = &$parameters['i'];

		$contexts = explode(':',$parameters['context']);

		if($line->special_code!=$this->module_number || $line->product_type!=9) {
			null;
		}	
		else if (in_array('invoicecard',$contexts) || in_array('propalcard',$contexts) || in_array('ordercard',$contexts)) 
        {
			if($object->element=='facture')$idvar = 'facid';
			else $idvar='id';
			
			if((float)DOL_VERSION <= 3.4)
			{
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('#tablelines tr[rel=subtotal]').mouseleave(function() {

							id_line =$(this).attr('id');

							$(this).find('td[rel=subtotal_total]').each(function() {
								$.get(document.location.href, function(data) {
									var total = $(data).find('#tablelines tr#'+id_line+' td[rel=subtotal_total]').html();

									$('#tablelines tr#'+id_line+' td[rel=subtotal_total]').html(total);

								});
							});
						});
					});

				</script>
				<?php
			}
			
			if(empty($line->description)) $line->description = $line->desc;
			
			$colspan = 5;
			if(!empty($conf->multicurrency->enabled)) $colspan+=2;
			if($object->element == 'commande' && $object->statut < 3 && !empty($conf->shippableorder->enabled)) $colspan++;
			if(!empty($conf->margin->enabled)) $colspan++;
			if(!empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
			if(!empty($conf->global->DISPLAY_MARK_RATES)) $colspan++;
			if($object->element == 'facture' && !empty($conf->global->INVOICE_USE_SITUATION) && $object->type == Facture::TYPE_SITUATION) $colspan++;
			if(!empty($conf->global->PRODUCT_USE_UNITS)) $colspan++;
					
			/* Titre */
			//var_dump($line);
			?>
			<tr class="drag drop" rel="subtotal" id="row-<?php echo $line->id ?>" style="<?php
					if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT))
					{
						if($line->qty==99) print 'background-color:#adadcf';
						else if($line->qty==98) print 'background-color:#ddddff;';
						else if($line->qty<=97 && $line->qty>=91) print 'background-color:#eeeeff;';
						else if($line->qty==1) print 'background-color:#adadcf;';
						else if($line->qty==2) print 'background-color:#ddddff;';
						else print 'background-color:#eeeeff;';

						//A compléter si on veux plus de nuances de couleurs avec les niveau 4,5,6,7,8 et 9
					}
					else 
					{
						if($line->qty==99) print 'background-color:#ddffdd';
						else if($line->qty==98) print 'background-color:#ddddff;';
						else if($line->qty==2) print 'background-color:#eeeeff; ';
						else print 'background-color:#eeffee;' ;	
					}

			?>;">
			
			<td colspan="<?php echo $colspan; ?>" style="font-weight:bold;  <?php echo ($line->qty>90)?'text-align:right':' font-style: italic;' ?> "><?php
					if($action=='editline' && GETPOST('lineid') == $line->id && (TSubtotal::isTitle($line) || TSubtotal::isSubtotal($line)) ) {

						echo '<div id="line_'.$line->id.'"></div>'; // Imitation Dolibarr
						echo '<input type="hidden" value="'.$line->id.'" name="lineid">';
						echo '<input id="product_type" type="hidden" value="'.$line->product_type.'" name="type">';
						echo '<input id="product_id" type="hidden" value="'.$line->fk_product.'" name="type">';
						echo '<input id="special_code" type="hidden" value="'.$line->special_code.'" name="type">';

						if (TSubtotal::isTitle($line))
						{
							if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT))
							{
								$qty_displayed = $line->qty;
								print img_picto('', 'subsubtotal@subtotal').'<span style="font-size:9px;margin-left:-3px;color:#0075DE;">'.$qty_displayed.'</span>&nbsp;&nbsp;';
							}
							else
							{
								if($line->qty<=1) print img_picto('', 'subtotal@subtotal');
								else if($line->qty==2) print img_picto('', 'subsubtotal@subtotal').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							}
						}
						else
						{
							$qty_displayed = 100 - $line->qty;
							print img_picto('', 'subsubtotal2@subtotal').'<span style="font-size:9px;margin-left:-1px;color:#0075DE;">'.$qty_displayed.'</span>&nbsp;&nbsp;';
						}
						

						if($line->label=='') {
							$line->label = $line->description.' '.$this->getTitle($object, $line);
							$line->description='';
						}

						echo '<input type="text" name="line-title" id-line="'.$line->id.'" value="'.$line->label.'" size="80"/>&nbsp;';
						
						if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT) && TSubtotal::isTitle($line))
						{
							$select = '<select name="subtotal_level">';
							for ($j=1; $j<10; $j++)
							{
								$select .= '<option '.($qty_displayed == $j ? 'selected="selected"' : '').' value="'.$j.'">'.$langs->trans('Level').' '.$j.'</option>';
							}
							$select .= '</select>&nbsp;';

							echo $select;
						}
						

						echo '<div class="subtotal_underline" style="margin-left:24px;">';
							echo '<label for="subtotal-pagebreak">'.$langs->trans('AddBreakPageBefore').'</label> <input style="vertical-align:sub;"  type="checkbox" name="line-pagebreak" id="subtotal-pagebreak" value="8" '.(($line->info_bits > 0) ? 'checked="checked"' : '') .' />&nbsp;&nbsp;';

							if (TSubtotal::isTitle($line))
							{
								$form = new Form($db);
								echo '<label for="subtotal_tva_tx">'.$form->textwithpicto($langs->trans('subtotal_apply_default_tva'), $langs->trans('subtotal_apply_default_tva_help')).'</label>';
								echo '<select id="subtotal_tva_tx" name="subtotal_tva_tx" class="flat"><option selected="selected" value="">-</option>';
								echo str_replace('selected', '', $form->load_tva('subtotal_tva_tx', '', $parameters['seller'], $parameters['buyer'], 0, 0, '', true));
								echo '</select>&nbsp;&nbsp;';
								
								if (!empty($conf->global->INVOICE_USE_SITUATION) && $object->element == 'facture' && $object->type == Facture::TYPE_SITUATION)
								{
									echo '<label for="subtotal_progress">'.$langs->trans('subtotal_apply_progress').'</label> <input id="subtotal_progress" name="subtotal_progress" value="" size="1" />%';
								}
							}
						echo '</div>';

						if($line->qty<10) {
							// WYSIWYG editor
							require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
							$nbrows = ROWS_2;
							$cked_enabled = (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
							if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
								$nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
							}
							$toolbarname = 'dolibarr_details';
							if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
								$toolbarname = 'dolibarr_notes';
							}
							$doleditor = new DolEditor('line-description', $line->description, '', 100, $toolbarname, '',
								false, true, $cked_enabled, $nbrows, '98%');
							$doleditor->Create();
						}

					}
					else {

						 if ($conf->global->SUBTOTAL_USE_NEW_FORMAT)
						 {
							if(TSubtotal::isTitle($line) || TSubtotal::isSubtotal($line)) 
							{
								echo str_repeat('&nbsp;&nbsp;&nbsp;', $line->qty-1);
								
								if (TSubtotal::isTitle($line)) print img_picto('', 'subtotal@subtotal').'<span style="font-size:9px;margin-left:-3px;">'.$line->qty.'</span>&nbsp;&nbsp;';
								else print img_picto('', 'subtotal2@subtotal').'<span style="font-size:9px;margin-left:-1px;">'.(100-$line->qty).'</span>&nbsp;&nbsp;';
							}
						 }
						 else 
						 {
							if($line->qty<=1) print img_picto('', 'subtotal@subtotal');
							else if($line->qty==2) print img_picto('', 'subsubtotal@subtotal').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'; 
						 }

						 if (empty($line->label)) {
							if ($line->qty >= 91 && $line->qty <= 99 && $conf->global->SUBTOTAL_USE_NEW_FORMAT) print  $line->description.' '.$this->getTitle($object, $line);
							else print  $line->description;
						 } 
						 else {

							if (! empty($conf->global->PRODUIT_DESC_IN_FORM) && !empty($line->description)) {
								print $line->label.'<br><span style="font-weight:normal;">'.dol_htmlentitiesbr($line->description).'</span>';
							}
							else{
								print '<span class="classfortooltip" title="'.$line->description.'">'.$line->label.'</span>';    
							}

						 } 
						if($line->qty>90) print ' : ';
						if($line->info_bits > 0) echo img_picto($langs->trans('Pagebreak'), 'pagebreak@subtotal');

						 


					}
			?></td>
					 
			<?php
				if($line->qty>90) {
					/* Total */
					$total_line = $this->getTotalLineFromObject($object, $line, $conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL);
					echo '<td class="nowrap" align="right" style="font-weight:bold;" rel="subtotal_total">'.price($total_line).'</td>';
				} else {
					echo '<td>&nbsp;</td>';
				}	
			?>
					
			<td align="center" class="nowrap">
				<?php
					if($action=='editline' && GETPOST('lineid') == $line->id && (TSubtotal::isTitle($line) || TSubtotal::isSubtotal($line)) ) {
						?>
						<input id="savelinebutton" class="button" type="submit" name="save" value="<?php echo $langs->trans('Save') ?>" />
						<br />
						<input class="button" type="button" name="cancelEditlinetitle" value="<?php echo $langs->trans('Cancel') ?>" />
						<script type="text/javascript">
							$(document).ready(function() {
								$('input[name=cancelEditlinetitle]').click(function () {
									document.location.href="<?php echo '?'.$idvar.'='.$object->id ?>";
								});
							});

						</script>
						<?php
					}
					else{
						if ($object->statut == 0  && $user->rights->{$object->element}->creer && !empty($conf->global->SUBTOTAL_ALLOW_DUPLICATE_BLOCK))
						{
							if(TSubtotal::isTitle($line) && ($object->situation_counter == 1 || !$object->situation_cycle_ref) ) echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=duplicate&lineid='.$line->id.'">'. img_picto($langs->trans('Duplicate'), 'duplicate@subtotal').'</a>';
						}

						if ($object->statut == 0  && $user->rights->{$object->element}->creer && !empty($conf->global->SUBTOTAL_ALLOW_EDIT_BLOCK)) 
						{
							echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=editline&lineid='.$line->id.'">'.img_edit().'</a>';
						}								
					}
				?>
			</td>

			<td align="center" nowrap="nowrap">	
				<?php

					if ($action != 'editline') {
						if ($object->statut == 0  && $user->rights->{$object->element}->creer && !empty($conf->global->SUBTOTAL_ALLOW_REMOVE_BLOCK))
						{

							if ($object->situation_counter == 1 || !$object->situation_cycle_ref)
							{
								echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=ask_deleteline&lineid='.$line->id.'">'.img_delete().'</a>';
							}

							if(TSubtotal::isTitle($line) && ($object->situation_counter == 1 || !$object->situation_cycle_ref) )
							{
								$img_delete = ((float) DOL_VERSION >= 3.8) ? img_picto($langs->trans('deleteWithAllLines'), 'delete_all.3.8@subtotal') : img_picto($langs->trans('deleteWithAllLines'), 'delete_all@subtotal');
								echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=ask_deleteallline&lineid='.$line->id.'">'.$img_delete.'</a>';
							}
						}
					}
				?>
			</td>
			
			<?php 
			if ($object->statut == 0  && $user->rights->{$object->element}->creer && !empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && TSubtotal::isTitle($line) && $action != 'editline')
			{
				echo '<td class="subtotal_nc">';
				echo '<input id="subtotal_nc-'.$line->id.'" class="subtotal_nc_chkbx" data-lineid="'.$line->id.'" type="checkbox" name="subtotal_nc" value="1" '.(!empty($line->array_options['options_subtotal_nc']) ? 'checked="checked"' : '').' />';
				echo '</td>';
			}
			
			if ($num > 1 && empty($conf->browser->phone)) { ?>
			<td align="center" class="tdlineupdown">
			</td>
			<?php } else { ?>
			<td align="center"<?php echo ((empty($conf->browser->phone) && ($object->statut == 0  && $user->rights->{$object->element}->creer))?' class="tdlineupdown"':''); ?>></td>
			<?php } ?>

			</tr>
			<?php
			
			return 1;	
			
		}
		
		return 0;

	}

	
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $conf,$langs;
		
		if ($object->statut == 0 && !empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && $action != 'editline')
		{
			$form = new Form($db);
			?>
			<script type="text/javascript">
				$(function() {
					$("#tablelines tbody > tr").each(function(i, item) {
						if ($(item).children('.subtotal_nc').length == 0)
						{
							var id = $(item).attr('id');
							
							if ((typeof id != 'undefined' && id.indexOf('row-') >= 0) || $(item).hasClass('liste_titre'))
							{
								$(item).children('td:last-child').before('<td class="subtotal_nc"></td>');
							}
							else 
							{
								$(item).append('<td class="subtotal_nc"></td>');
							}
						}
					});
					
					$('#tablelines tbody tr.liste_titre:first .subtotal_nc').html(<?php echo json_encode($form->textwithtooltip($langs->trans('subtotal_nc_title'), $langs->trans('subtotal_nc_title_help'))); ?>);
					
					function callAjaxUpdateLineNC(set, lineid, subtotal_nc)
					{
						$.ajax({
							url: '<?php echo dol_buildpath('/subtotal/script/interface.php', 1); ?>'
							,type: 'POST'
							,data: {
								json:1
								,set: set
								,element: '<?php echo $object->element; ?>'
								,elementid: <?php echo (int) $object->id; ?>
								,lineid: lineid
								,subtotal_nc: subtotal_nc
							}
						}).done(function(response) {
							window.location.href = window.location.pathname + '?id=<?php echo $object->id; ?>&page_y=' + window.pageYOffset;
						});
					}
					
					$(".subtotal_nc_chkbx").change(function(event) {
						var lineid = $(this).data('lineid');
						var subtotal_nc = 0 | $(this).is(':checked'); // Renvoi 0 ou 1 
						
						callAjaxUpdateLineNC('updateLineNC', lineid, subtotal_nc);
					});
					
					$(document).ajaxSuccess(function(event, xhr, options) {
						if (xhr.status == 200 && xhr.statusText == 'OK' && typeof options.url != 'undefined' && options.url == '/core/ajax/row.php')
						{
							var roworder = GetURLParameter('roworder', options.data);
							if (roworder.length > 0)
							{
								var lineid = GetURLParameter('element_id', options.data);
								if (lineid > 0)
								{
									callAjaxUpdateLineNC('updateLine', lineid);
								}
							}
						}
						
					});
				});
				
				// source : http://www.jquerybyexample.net/2012/06/get-url-parameters-using-jquery.html
				function GetURLParameter(sParam, sPageURL)
				{
					if (!sPageURL) {
						sPageURL = window.location.search.substring(1);
					}
					
					var sURLVariables = sPageURL.split('&');
					for (var i = 0; i < sURLVariables.length; i++)
					{
						var sParameterName = sURLVariables[i].split('=');
						if (sParameterName[0] == sParam)
						{
							return sParameterName[1];
						}
					}
					
					return '';
				}

			</script>
			<?php
		}
	}
	
	function afterPDFCreation($parameters, &$pdf, &$action, $hookmanager)
	{
		global $conf;
		
		$object = $parameters['object'];
		
		if ((!empty($conf->global->SUBTOTAL_PROPAL_ADD_RECAP) && $object->element == 'propal') || (!empty($conf->global->SUBTOTAL_COMMANDE_ADD_RECAP) && $object->element == 'commande') || (!empty($conf->global->SUBTOTAL_INVOICE_ADD_RECAP) && $object->element == 'facture'))
		{
			if (GETPOST('subtotal_add_recap')) TSubtotal::addRecapPage($parameters, $pdf);
		}
	}
	
}
