<?php
class ActionsSubtotal
{

	function __construct($db)
	{
		global $langs;

		$this->db = $db;
		$langs->load('subtotal@subtotal');

		$this->allow_move_block_lines = true;
	}


	function createDictionaryFieldlist($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		if ($parameters['tabname'] == MAIN_DB_PREFIX.'c_subtotal_free_text')
		{
			// Merci Dolibarr de remplacer les textarea par un input text
			if ((float) DOL_VERSION >= 6.0)
			{
				$value = '';
				$sql = 'SELECT content FROM '.MAIN_DB_PREFIX.'c_subtotal_free_text WHERE rowid = '.GETPOST('rowid', 'int');
				$resql = $this->db->query($sql);
				if ($resql && ($obj = $this->db->fetch_object($resql))) $value = $obj->content;
			}

			?>
			<script type="text/javascript">
				$(function() {

					<?php if ((float) DOL_VERSION >= 6.0) { ?>
							if ($('input[name=content]').length > 0)
							{
								$('input[name=content]').each(function(i,item) {
									var value = '';
									// Le dernier item correspond à l'édition
									if (i == $('input[name=content]').length) value = <?php echo json_encode($value); ?>;
									$(item).replaceWith($('<textarea name="content">'+value+'</textarea>'));
								});

								<?php if (!empty($conf->fckeditor->enabled) && !empty($conf->global->FCKEDITOR_ENABLE_DETAILS)) { ?>
								$('textarea[name=content]').each(function(i, item) {
									CKEDITOR.replace(item, {
										toolbar: 'dolibarr_notes'
										,customConfig : ckeditorConfig
									});
								});
								<?php } ?>
							}
					<?php } else { ?>
						// <= 5.0
						// Le CKEditor est forcé sur la page dictionnaire, pas possible de mettre une valeur custom
						// petit js qui supprimer le wysiwyg et affiche le textarea car avant la version 6.0 le wysiwyg sur une page de dictionnaire est inexploitable
						<?php if (!empty($conf->fckeditor->enabled)) { ?>
							CKEDITOR.on('instanceReady', function(ev) {
								var editor = ev.editor;

								if (editor.name == 'content') // Mon champ en bdd s'appel "content", pas le choix si je veux avoir un textarea sur une page de dictionnaire
								{
									editor.element.show();
									editor.destroy();
								}
							});
						<?php } ?>
					<?php } ?>
				});
			</script>
			<?php
		}
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

		if(in_array('ordercard',$contexts) || in_array('ordersuppliercard',$contexts) || in_array('propalcard',$contexts) || in_array('supplier_proposalcard',$contexts) || in_array('invoicecard',$contexts) || in_array('invoicesuppliercard',$contexts) || in_array('invoicereccard',$contexts)) {

			$createRight = $user->rights->{$object->element}->creer;
			if($object->element == 'facturerec' )
			{
				$object->statut = 0; // hack for facture rec
				$createRight = $user->rights->facture->creer;
			} elseif($object->element == 'order_supplier' )
			{
			    $createRight = $user->rights->fournisseur->commande->creer;
			} elseif($object->element == 'invoice_supplier' )
			{
			    $createRight = $user->rights->fournisseur->facture->creer;
			}

			if ($object->statut == 0  && $createRight) {


				if($object->element=='facture')$idvar = 'facid';
				else $idvar='id';

				if(in_array($action, array('add_title_line', 'add_total_line', 'add_subtitle_line', 'add_subtotal_line', 'add_free_text')) )
				{
					$level = GETPOST('level', 'int'); //New avec SUBTOTAL_USE_LEVEL

					if($action=='add_title_line') {
						$title = GETPOST('title', 'alpha');
						if(empty($title)) $title = $langs->trans('title');
						$qty = $level<1 ? 1 : $level ;
					}
					else if($action=='add_free_text') {
						$title = GETPOST('title', 'alphahtml');

						if (empty($title)) {
							$free_text = GETPOST('free_text', 'int');
							if (!empty($free_text)) {
								$TFreeText = getTFreeText();
								if (!empty($TFreeText[$free_text])) {
									$title = $TFreeText[$free_text]->content;
								}
							}
						}
						if(empty($title)) $title = $langs->trans('subtotalAddLineDescription');
						$qty = 50;
					}
					else if($action=='add_subtitle_line') {
						$title = GETPOST('title', 'alpha');
						if(empty($title)) $title = $langs->trans('subtitle');
						$qty = 2;
					}
					else if($action=='add_subtotal_line') {
						$title = $langs->trans('SubSubTotal');
						$qty = 98;
					}
					else {
						$title = GETPOST('title', 'alpha') ? GETPOST('title', 'alpha') : $langs->trans('SubTotal');
						$qty = $level ? 100-$level : 99;
					}
					dol_include_once('/subtotal/class/subtotal.class.php');



	    			TSubtotal::addSubTotalLine($object, $title, $qty);
				}
				else if($action==='ask_deleteallline') {
						$form=new Form($db);

						$lineid = GETPOST('lineid','int');
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
					$this->printNewFormat($object, $conf, $langs, $idvar);
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
		if (!empty($object->situation_cycle_ref) && $object->situation_counter > 1) return false; // Si facture de situation
		?>
		 	<script type="text/javascript">
				$(document).ready(function() {
					$('div.fiche div.tabsAction').append('<br />');

					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_title_line" rel="add_title_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddTitle' )?></a></div>');
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_total_line" rel="add_total_line" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddSubTotal')?></a></div>');
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_free_text" rel="add_free_text" href="javascript:;" class="butAction"><?php echo  $langs->trans('AddFreeText')?></a></div>');


					function updateAllMessageForms(){
				         for (instance in CKEDITOR.instances) {
				             CKEDITOR.instances[instance].updateElement();
				         }
				    }

					function promptSubTotal(action, titleDialog, label, url_to, url_ajax, params, use_textarea, show_free_text, show_under_title) {
					     $( "#dialog-prompt-subtotal" ).remove();

						 var dialog_html = '<div id="dialog-prompt-subtotal" '+(action == 'addSubtotal' ? 'class="center"' : '')+' >';

						 if (typeof show_under_title != 'undefined' && show_under_title)
						 {
							 var selectUnderTitle = <?php echo json_encode(getHtmlSelectTitle($object, true)); ?>;
							 dialog_html += selectUnderTitle + '<br /><br />';
						 }

						if (action == 'addTitle' || action == 'addFreeTxt')
						{
							if (typeof show_free_text != 'undefined' && show_free_text)
							{
							   var selectFreeText = <?php echo json_encode(getHtmlSelectFreeText()); ?>;
							   dialog_html += selectFreeText + ' <?php echo $langs->transnoentities('subtotalFreeTextOrDesc'); ?><br />';
							}

							if (typeof use_textarea != 'undefined' && use_textarea) dialog_html += '<textarea id="sub-total-title" rows="<?php echo ROWS_8; ?>" cols="80" placeholder="'+label+'"></textarea>';
							else dialog_html += '<input id="sub-total-title" size="30" value="" placeholder="'+label+'" />';
						}

						if (action == 'addTitle' || action == 'addSubtotal')
						{
							if (action == 'addSubtotal') dialog_html += '<input id="sub-total-title" size="30" value="" placeholder="'+label+'" />';

							dialog_html += "&nbsp;<select name='subtotal_line_level'>";
							for (var i=1;i<3;i++)
							{
								dialog_html += "<option value="+i+"><?php echo $langs->trans('Level'); ?> "+i+"</option>";
							}
							dialog_html += "</select>";
						}

						 dialog_html += '</div>';

						$('body').append(dialog_html);

						<?php
						$editorTool = empty($conf->global->FCKEDITOR_EDITORNAME)?'ckeditor':$conf->global->FCKEDITOR_EDITORNAME;
						$editorConf = empty($conf->global->FCKEDITOR_ENABLE_DETAILS)?false:$conf->global->FCKEDITOR_ENABLE_DETAILS;
						if($editorConf && in_array($editorTool,array('textarea','ckeditor'))){
						?>
						if (action == 'addTitle' || action == 'addFreeTxt')
						{
							if (typeof use_textarea != 'undefined' && use_textarea && typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" )
							{
								 CKEDITOR.replace( 'sub-total-title', {toolbar: 'dolibarr_details', toolbarStartupExpanded: false} );
							}
						}
						<?php } ?>

					     $( "#dialog-prompt-subtotal" ).dialog({
	                        resizable: false,
							height: 'auto',
							width: 'auto',
	                        modal: true,
	                        title: titleDialog,
	                        buttons: {
	                            "Ok": function() {
	                            	if (typeof use_textarea != 'undefined' && use_textarea && typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" ){ updateAllMessageForms(); }
									params.title = params.title = (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && "sub-total-title" in CKEDITOR.instances ? CKEDITOR.instances["sub-total-title"].getData() : $(this).find('#sub-total-title').val());
									params.under_title = $(this).find('select[name=under_title]').val();
									params.free_text = $(this).find('select[name=free_text]').val();
									params.level = $(this).find('select[name=subtotal_line_level]').val();

									$.ajax({
										url: url_ajax
										,type: 'POST'
										,data: params
									}).done(function() {
										document.location.href=url_to;
									});

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
						promptSubTotal('addTitle'
							 , "<?php echo $langs->trans('YourTitleLabel') ?>"
							 , "<?php echo $langs->trans('title'); ?>"
							 , '?<?php echo $idvar ?>=<?php echo $object->id; ?>'
							 , '<?php echo $_SERVER['PHP_SELF']; ?>'
							 , {<?php echo $idvar; ?>: <?php echo (int) $object->id; ?>, action:'add_title_line'}
						);
					});

					$('a[rel=add_total_line]').click(function()
					{
						promptSubTotal('addSubtotal'
							, '<?php echo $langs->trans('YourSubtotalLabel') ?>'
							, '<?php echo $langs->trans('subtotal'); ?>'
							, '?<?php echo $idvar ?>=<?php echo $object->id; ?>'
							, '<?php echo $_SERVER['PHP_SELF']; ?>'
							, {<?php echo $idvar; ?>: <?php echo (int) $object->id; ?>, action:'add_total_line'}
							/*,false,false, <?php echo !empty($conf->global->SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE) ? 'true' : 'false'; ?>*/
						);
					});

					$('a[rel=add_free_text]').click(function()
					{
						promptSubTotal('addFreeTxt'
							, "<?php echo $langs->transnoentitiesnoconv('YourTextLabel') ?>"
							, "<?php echo $langs->trans('subtotalAddLineDescription'); ?>"
							, '?<?php echo $idvar ?>=<?php echo $object->id; ?>'
							, '<?php echo $_SERVER['PHP_SELF']; ?>'
							, {<?php echo $idvar; ?>: <?php echo (int) $object->id; ?>, action:'add_free_text'}
							, true
							, true
							, <?php echo !empty($conf->global->SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE) ? 'true' : 'false'; ?>
						);
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

					list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, '', 1);

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
		        || in_array('invoicesuppliercard',explode(':',$parameters['context']))
				|| in_array('propalcard',explode(':',$parameters['context']))
		        || in_array('supplier_proposalcard',explode(':',$parameters['context']))
				|| in_array('ordercard',explode(':',$parameters['context']))
		        || in_array('ordersuppliercard',explode(':',$parameters['context']))
				|| in_array('invoicereccard',explode(':',$parameters['context']))
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
		global $db, $conf, $langs,$user;

		dol_include_once('/subtotal/class/subtotal.class.php');
		dol_include_once('/subtotal/lib/subtotal.lib.php');
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

		$showBlockExtrafields = GETPOST('showBlockExtrafields', 'none');

		if($object->element=='facture') $idvar = 'facid';
		else $idvar = 'id';

		if ($action == 'updateligne' || $action == 'updateline')
		{
			$found = false;
			$lineid = GETPOST('lineid', 'int');
			foreach ($object->lines as &$line)
			{

				if ($line->id == $lineid && TSubtotal::isModSubtotalLine($line))
				{
					$found = true;
					if(TSubtotal::isTitle($line) && !empty($showBlockExtrafields)) {
						$extrafieldsline = new ExtraFields($db);
						$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
						$extrafieldsline->setOptionalsFromPost($extralabelsline, $line);
					}
					_updateSubtotalLine($object, $line);
					_updateSubtotalBloc($object, $line);

					TSubtotal::generateDoc($object);
					break;
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
			    || in_array('ordersuppliercard',explode(':',$parameters['context']))
			    || in_array('invoicesuppliercard',explode(':',$parameters['context']))
			    || in_array('supplier_proposalcard',explode(':',$parameters['context']))
			)
	        {
				if(in_array('invoicecard',explode(':',$parameters['context']))) {
					$sessname = 'subtotal_hideInnerLines_facture';
					$sessname2 = 'subtotal_hidedetails_facture';
					$sessname3 = 'subtotal_hideprices_facture';
				}
				elseif(in_array('invoicesuppliercard',explode(':',$parameters['context']))) {
				    $sessname = 'subtotal_hideInnerLines_facture_fournisseur';
				    $sessname2 = 'subtotal_hidedetails_facture_fournisseur';
				    $sessname3 = 'subtotal_hideprices_facture_fournisseur';
				}
				elseif(in_array('propalcard',explode(':',$parameters['context']))) {
					$sessname = 'subtotal_hideInnerLines_propal';
					$sessname2 = 'subtotal_hidedetails_propal';
					$sessname3 = 'subtotal_hideprices_propal';
				}
				elseif(in_array('supplier_proposalcard',explode(':',$parameters['context']))) {
				    $sessname = 'subtotal_hideInnerLines_supplier_proposal';
				    $sessname2 = 'subtotal_hidedetails_supplier_proposal';
				    $sessname3 = 'subtotal_hideprices_supplier_proposal';
				}
				elseif(in_array('ordercard',explode(':',$parameters['context']))) {
					$sessname = 'subtotal_hideInnerLines_commande';
					$sessname2 = 'subtotal_hidedetails_commande';
					$sessname3 = 'subtotal_hideprices_commande';
				}
				elseif(in_array('ordersuppliercard',explode(':',$parameters['context']))) {
				    $sessname = 'subtotal_hideInnerLines_commande_fournisseur';
				    $sessname2 = 'subtotal_hidedetails_commande_fournisseur';
				    $sessname3 = 'subtotal_hideprices_commande_fournisseur';
				}
				else {
					$sessname = 'subtotal_hideInnerLines_unknown';
					$sessname2 = 'subtotal_hidedetails_unknown';
					$sessname3 = 'subtotal_hideprices_unknown';
				}

				global $hideprices;

				$hideInnerLines = GETPOST('hideInnerLines', 'int');
				$_SESSION[$sessname] = $hideInnerLines;

				$hidedetails= GETPOST('hidedetails', 'int');
				$_SESSION[$sessname2] = $hidedetails;

				$hideprices= GETPOST('hideprices', 'int');
				$_SESSION[$sessname3] = $hideprices;

				foreach($object->lines as &$line) {
					if ($line->product_type == 9 && $line->special_code == $this->module_number) {

                        if($line->qty>=90) {
                            $line->modsubtotal_total = 1;
                        }
                        else{
                            $line->modsubtotal_title = 1;
                        }

						$line->total_ht = $this->getTotalLineFromObject($object, $line, '');
					}
	        	}
	        }

		}
		else if($action === 'confirm_delete_all_lines' && GETPOST('confirm', 'alpha')=='yes') {

			$Tab = $this->getArrayOfLineForAGroup($object, GETPOST('lineid', 'int'));

			foreach($Tab as $idLine) {
				/**
				 * @var $object Facture
				 */
				if($object->element=='facture') $object->deleteline($idLine);
				/**
				 * @var $object Facture fournisseur
				 */
				else if($object->element=='invoice_supplier')
				{
				    $object->deleteline($idLine);
				}
				/**
				 * @var $object Propal
				 */
				else if($object->element=='propal') $object->deleteline($idLine);
				/**
				 * @var $object Propal Fournisseur
				 */
				else if($object->element=='supplier_proposal') $object->deleteline($idLine);
				/**
				 * @var $object Commande
				 */
				else if($object->element=='commande')
				{
					if ((float) DOL_VERSION >= 5.0) $object->deleteline($user, $idLine);
					else $object->deleteline($idLine);
				}
				/**
				 * @var $object Commande fournisseur
				 */
				else if($object->element=='order_supplier')
				{
				    $object->deleteline($idLine);
				}
				/**
				 * @var $object Facturerec
				 */
				else if($object->element=='facturerec') $object->deleteline($idLine);
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

		$qty_line = 0;

		$found = false;

		$Tab= array();

		foreach($object->lines as $l) {

		    $lid = (!empty($l->rowid) ? $l->rowid : $l->id);
			if($lid == $lineid) {

				$found = true;
				$qty_line = $l->qty;
			}

			if($found) {

			    $Tab[] = (!empty($l->rowid) ? $l->rowid : $l->id);

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
	 *
	 * @param	$use_level		isn't used anymore
	 */
	function getTotalLineFromObject(&$object, &$line, $use_level=false, $return_all=0) {

		$rang = $line->rang;
		$qty_line = $line->qty;

		$total = 0;
		$total_tva = 0;
		$total_ttc = 0;
		$TTotal_tva = array();

		dol_include_once('/subtotal/class/subtotal.class.php');
		foreach($object->lines as $l) {
			//print $l->rang.'>='.$rang.' '.$total.'<br/>';
			if($l->rang>=$rang) {
				//echo 'return!<br>';
				if (!$return_all) return $total;
				else return array($total, $total_tva, $total_ttc, $TTotal_tva);
			}
			else if(TSubtotal::isTitle($l, 100 - $qty_line))
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

	/*
	 * Get the sum of situation invoice for last column
	 */
	function getTotalToPrintSituation(&$object, &$line) {

		$rang = $line->rang;
		$total = 0;
		foreach($object->lines as $l) {
			if($l->rang>=$rang) {
				return price($total);
			}
                        if (TSubtotal::isSubtotal($l)){
                            $total = 0;
                        } else  if ($l->situation_percent > 0 ){


		 	$prev_progress = $l->get_prev_progress($object->id);
		 	$progress = ($l->situation_percent - $prev_progress) /100;
                        $total += ($l->total_ht/($l->situation_percent/100)) * $progress;

                    }
                }

		return price($total);
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

		$hideInnerLines = GETPOST('hideInnerLines', 'int');


		$hidePriceOnSubtotalLines = GETPOST('hide_price_on_subtotal_lines', 'int');

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
		$pdf->MultiCell($pdf->page_largeur - $pdf->marge_droite, $cell_height, '', 0, '', 1);

		if (!$hidePriceOnSubtotalLines) {
			$total_to_print = price($line->total);


			if($total_to_print !== '') {

				if (GETPOST('hideInnerLines', 'int'))
				{
					// Dans le cas des lignes cachés, le calcul est déjà fait dans la méthode beforePDFCreation et les lignes de sous-totaux sont déjà renseignés
//					$line->TTotal_tva
//					$line->total_ht
//					$line->total_tva
//					$line->total
//					$line->total_ttc
				}
				else
				{
					list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, '', 1);
                                        if(get_class($object) == 'Facture' && $object->type==Facture::TYPE_SITUATION){//Facture de situation
                                                $total_to_print = $this->getTotalToPrintSituation($object, $line);
                                        } else {
                                            	$total_to_print = price($total);
                                        }

					$line->total_ht = $total;
					$line->total = $total;
					$line->total_tva = $total_tva;
					$line->total_ttc = $total_ttc;
				}
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

		$hideInnerLines = GETPOST('hideInnerLines', 'int');



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

		if ($label === strip_tags($label) && $label === dol_html_entity_decode($label, ENT_QUOTES)) $pdf->MultiCell($w, $h, $label, 0, 'L'); // Pas de HTML dans la chaine
		else $pdf->writeHTMLCell($w, $h, $posx, $posy, $label, 0, 1, false, true, 'J',true); // et maintenant avec du HTML

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
		global $conf,$hideprices;

		if($this->isModSubtotalLine($parameters,$object) ){

			$this->resprints = ' ';

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}

		}
		elseif(!empty($hideprices)) {
			$this->resprints = $object->lines[$parameters['i']]->qty;
			return 1;
		}

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;

		if (empty($object->lines[$i])) return 0; // hideInnerLines => override $object->lines et Dolibarr ne nous permet pas de mettre à jour la variable qui conditionne la boucle sur les lignes (PR faite pour 6.0)

		if(empty($object->lines[$i]->array_options)) $object->lines[$i]->fetch_optionals();

		return 0;
	}

	function pdf_getlinetotalexcltax($parameters=array(), &$object, &$action='') {
	    global $conf, $hideprices;

		if($this->isModSubtotalLine($parameters,$object) ){

			$this->resprints = ' ';

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}

		}


		if (!empty($hideprices))
		{
		    if (!empty($hideprices))
		    {

		        if(is_array($parameters)) $i = & $parameters['i'];
		        else $i = (int)$parameters;

		        // Check if a title exist for this line && if the title have subtotal
		        $lineTitle = TSubtotal::getParentTitleOfLine($object, $i);
		        if(TSubtotal::getParentTitleOfLine($object, $i) && TSubtotal::titleHasTotalLine($object, $lineTitle, true))
		        {
		            $this->resprints = ' ';
		            return 1;
		        }
		    }
		    elseif (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
		    {
		        $this->resprints = ' ';
		        return 1;
		    }
		}

		return 0;
	}

	function pdf_getlinetotalwithtax($parameters=array(), &$object, &$action='') {
		global $conf;

		if($this->isModSubtotalLine($parameters,$object) ){

			$this->resprints = ' ';

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;


		return 0;
	}

	function pdf_getlineunit($parameters=array(), &$object, &$action='') {
		global $conf;

		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;


		return 0;
	}

	function pdf_getlineupexcltax($parameters=array(), &$object, &$action='') {
		global $conf,$hideprices;

		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;


		if (!empty($hideprices) )
		{
			$this->resprints = ' ';
			return 1;
		}


		return 0;
	}

	function pdf_getlineupwithtax($parameters=array(), &$object, &$action='') {
		global $conf,$hideprices;

		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';
			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;


		if (!empty($hideprices))
		{
			$this->resprints = ' ';
			return 1;
		}


		return 0;
	}

	function pdf_getlinevatrate($parameters=array(), &$object, &$action='') {
		global $conf;

		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;

		if (empty($object->lines[$i])) return 0; // hideInnerLines => override $object->lines et Dolibarr ne nous permet pas de mettre à jour la variable qui conditionne la boucle sur les lignes (PR faite pour 6.0)

		$object->lines[$i]->fetch_optionals();


		return 0;
	}

	function pdf_getlineprogress($parameters=array(), &$object, &$action) {
		global $conf;

		if($this->isModSubtotalLine($parameters,$object) ){
			$this->resprints = ' ';
			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;



		return 0;
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
				if (empty($line->label) && (float)DOL_VERSION < 6)
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

		$hidedetails = GETPOST('hidedetails', 'int');

		if(empty($hidedetails)) return false;

		// TODO can't add VAT to document without lines... :-/

		return true;
	}

	function beforePDFCreation($parameters=array(), &$object, &$action)
	{
		/**
		 * @var $pdf    TCPDF
		 */
		global $pdf,$conf, $langs;

		// var_dump($object->lines);
		dol_include_once('/subtotal/class/subtotal.class.php');

		foreach($parameters as $key=>$value) {
			${$key} = $value;
		}

		$this->setDocTVA($pdf, $object);



		$hideInnerLines = GETPOST('hideInnerLines', 'int');
		$hidedetails = GETPOST('hidedetails', 'int');

		if ($hideInnerLines) { // si c une ligne de titre
	    	$fk_parent_line=0;
			$TLines =array();

			$original_count=count($object->lines);
		    $TTvas = array(); // tableau de tva

			foreach($object->lines as $k=>&$line)
			{

				if($line->product_type==9 && $line->rowid>0)
				{
					$fk_parent_line = $line->rowid;

					// Fix tk7201 - si on cache le détail, la TVA est renseigné au niveau du sous-total, l'erreur c'est s'il y a plusieurs sous-totaux pour les même lignes, ça va faire la somme
					if(TSubtotal::isSubtotal($line))
					{
						/*$total = $this->getTotalLineFromObject($object, $line, '');

						$line->total_ht = $total;
						$line->total = $total;
						*/
						list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, '', 1);

						if (TSubtotal::getNiveau($line) == 1) $line->TTotal_tva = $TTotal_tva;
						$line->total_ht = $total;
						$line->total_tva = $total_tva;
						$line->total = $line->total_ht;
						$line->total_ttc = $total_ttc;
					}

				}

				if ($hideInnerLines)
				{
			        if($line->product_type==9 && $line->rowid>0)
			        {
			            // ajoute la ligne de sous-total
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

			// cas incongru où il y aurait des produits en dessous du dernier sous-total
			$nbtva = count($TTvas);


			global $nblignes;
			$nblignes=count($TLines);

			$object->lines = $TLines;

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

		$hideInnerLines = GETPOST('hideInnerLines', 'int');
		$hidedetails = GETPOST('hidedetails', 'int');

		if($this->isModSubtotalLine($parameters,$object) ){

				global $hideprices;

				if(!empty($hideprices)) {
					foreach($object->lines as &$line) {
						if($line->fk_product_type!=9) $line->fk_parent_line = -1;
					}
				}

				$line = &$object->lines[$i];

				$margin = $pdf->getMargins();
				if(!empty($margin) && $line->info_bits>0) {
					$pdf->addPage();
					$posy = $margin['top'];
				}

				$label = $line->label;
				$description= !empty($line->desc) ? $outputlangs->convToOutputCharset($line->desc) : $outputlangs->convToOutputCharset($line->description);

				if(empty($label)) {
					$label = $description;
					$description='';
				}


				if($line->qty>90) {

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
					return 1;

				}
				else if ($line->qty < 10) {
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
					return 1;
				} elseif(!empty($margin)) {

					$labelproductservice = pdf_getlinedesc($object, $i, $outputlangs, $parameters['hideref'], $parameters['hidedesc'], $parameters['issupplierline']);

					$labelproductservice = preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $labelproductservice, -1, $nbrep);

					$pdf->writeHTMLCell($parameters['w'], $parameters['h'], $parameters['posx'], $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'J', true);

					return 1;
				}
//	if($line->rowid==47) exit;

			return 0;
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

		global $conf,$langs,$user,$db,$bc;

		$num = &$parameters['num'];
		$line = &$parameters['line'];
		$i = &$parameters['i'];

		$var = &$parameters['var'];

		$contexts = explode(':',$parameters['context']);

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

		if($line->special_code!=$this->module_number || $line->product_type!=9) {
			null;
		}
		else if (in_array('invoicecard',$contexts) || in_array('invoicesuppliercard',$contexts) || in_array('propalcard',$contexts) || in_array('supplier_proposalcard',$contexts) || in_array('ordercard',$contexts) || in_array('ordersuppliercard',$contexts) || in_array('invoicereccard',$contexts))
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
			if($object->element == 'facturerec' ) $colspan = 3;
			if($object->element == 'order_supplier') $colspan = 3;
			if($object->element == 'invoice_supplier') $colspan = 4;
			if($object->element == 'supplier_proposal') $colspan = 4;
			if(!empty($conf->multicurrency->enabled)) $colspan+=2;
			if($object->element == 'commande' && $object->statut < 3 && !empty($conf->shippableorder->enabled)) $colspan++;
			if(!empty($conf->margin->enabled)) $colspan++;
			if(!empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
			if(!empty($conf->global->DISPLAY_MARK_RATES)) $colspan++;
			if($object->element == 'facture' && !empty($conf->global->INVOICE_USE_SITUATION) && $object->type == Facture::TYPE_SITUATION) $colspan++;
			if(!empty($conf->global->PRODUCT_USE_UNITS)) $colspan++;

			/* Titre */
			//var_dump($line);

			// HTML 5 data for js
            $data = $this->_getHtmlData($parameters, $object, $action, $hookmanager);


			?>
			<tr <?php echo $bc[$var]; $var=!$var; echo $data; ?> rel="subtotal" id="row-<?php echo $line->id ?>" style="<?php

					if($line->qty==99) print 'background:#ddffdd';
					else if($line->qty==98) print 'background:#ddddff;';
					else if($line->qty==2) print 'background:#eeeeff; ';
					else if($line->qty==50) print '';
					else print 'background:#eeffee;' ;


			?>;">

				<td colspan="<?php echo $colspan; ?>" style="<?php TSubtotal::isFreeText($line) ? '' : 'font-weight:bold;'; ?>  <?php echo ($line->qty>90)?'text-align:right':'' ?> "><?php
					if($action=='editline' && GETPOST('lineid', 'int') == $line->id && TSubtotal::isModSubtotalLine($line) ) {

						$params=array('line'=>$line);
						$reshook=$hookmanager->executeHooks('formEditProductOptions',$params,$object,$action);

						echo '<div id="line_'.$line->id.'"></div>'; // Imitation Dolibarr
						echo '<input type="hidden" value="'.$line->id.'" name="lineid">';
						echo '<input id="product_type" type="hidden" value="'.$line->product_type.'" name="type">';
						echo '<input id="product_id" type="hidden" value="'.$line->fk_product.'" name="type">';
						echo '<input id="special_code" type="hidden" value="'.$line->special_code.'" name="type">';

						$isFreeText=false;
						if (TSubtotal::isTitle($line))
						{
							$qty_displayed = $line->qty;
							print img_picto('', 'subsubtotal@subtotal').'<span style="font-size:9px;margin-left:-3px;color:#0075DE;">'.$qty_displayed.'</span>&nbsp;&nbsp;';

						}
						else if (TSubtotal::isSubtotal($line))
						{
							$qty_displayed = 100 - $line->qty;
							print img_picto('', 'subsubtotal2@subtotal').'<span style="font-size:9px;margin-left:-1px;color:#0075DE;">'.$qty_displayed.'</span>&nbsp;&nbsp;';
						}
						else
						{
							$isFreeText = true;
						}

						if ($object->element == 'order_supplier' || $object->element == 'invoice_supplier') {
						    $line->label = !empty($line->description) ? $line->description : $line->desc;
						    $line->description = '';
						}
						$newlabel = $line->label;
						if($line->label=='' && !$isFreeText) {
							if(TSubtotal::isSubtotal($line)) {
								$newlabel = $line->description.' '.$this->getTitle($object, $line);
								$line->description='';
							} elseif( (float)DOL_VERSION < 6 ) {
								$newlabel= $line->description;
								$line->description='';
							}
						}

						$readonlyForSituation = '';
						if (!empty($object->situation_cycle_ref) && $object->situation_counter > 1) $readonlyForSituation = 'readonly';

						if (!$isFreeText) echo '<input type="text" name="line-title" id-line="'.$line->id.'" value="'.$newlabel.'" size="80" '.$readonlyForSituation.'/>&nbsp;';

						if (!empty($conf->global->SUBTOTAL_USE_LEVEL) && (TSubtotal::isTitle($line) || TSubtotal::isSubtotal($line)) )
						{
						    $select = '<select name="subtotal_level">';
						    for ($j=1; $j<3; $j++)
						    {
						        if (!empty($readonlyForSituation)) {
						            if ($qty_displayed == $j) $select .= '<option selected="selected" value="'.$j.'">'.$langs->trans('Level').' '.$j.'</option>';
						        } else $select .= '<option '.($qty_displayed == $j ? 'selected="selected"' : '').' value="'.$j.'">'.$langs->trans('Level').' '.$j.'</option>';
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
								if (empty($readonlyForSituation)) echo str_replace('selected', '', $form->load_tva('subtotal_tva_tx', '', $parameters['seller'], $parameters['buyer'], 0, 0, '', true));
								echo '</select>&nbsp;&nbsp;';

								if (!empty($conf->global->INVOICE_USE_SITUATION) && $object->element == 'facture' && $object->type == Facture::TYPE_SITUATION)
								{
									echo '<label for="subtotal_progress">'.$langs->trans('subtotal_apply_progress').'</label> <input id="subtotal_progress" name="subtotal_progress" value="" size="1" />%';
								}
							}
							else if ($isFreeText) echo TSubtotal::getFreeTextHtml($line, (bool) $readonlyForSituation);
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
								false, true, $cked_enabled, $nbrows, '98%', (bool) $readonlyForSituation);
							$doleditor->Create();
						}

					}
					else {


					    if ($conf->global->SUBTOTAL_USE_LEVEL)
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



						 // Get display styles and apply them
						 $titleStyleItalic = strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'I') === false ? '' : ' font-style: italic;';
						 $titleStyleBold =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'B') === false ? '' : ' font-weight:bold;';
						 $titleStyleUnderline =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'U') === false ? '' : ' text-decoration: underline;';

						 if (empty($line->label)) {
						     print  $line->description;
						 }
						 else {

							if (! empty($conf->global->PRODUIT_DESC_IN_FORM) && !empty($line->description)) {
								print '<span class="subtotal_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" >'.$line->label.'</span><br><div class="subtotal_desc">'.dol_htmlentitiesbr($line->description).'</div>';
							}
							else{
								print '<span class="subtotal_label classfortooltip '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
							}

						 }
						if($line->qty>90) print ' : ';
						if($line->info_bits > 0) echo img_picto($langs->trans('Pagebreak'), 'pagebreak@subtotal');




					}
			?></td>

			<?php
				if($line->qty>90) {
					/* Total */
					$total_line = $this->getTotalLineFromObject($object, $line, '');
					echo '<td class="nowrap liencolht" align="right" style="font-weight:bold;" rel="subtotal_total">'.price($total_line).'</td>';
				} else {
					echo '<td class="liencolht movetitleblock" >&nbsp;</td>';
				}
			?>

			<td align="center" class="nowrap linecoledit">
				<?php
				if ($action != 'selectlines') {

					if($action=='editline' && GETPOST('lineid', 'int') == $line->id && TSubtotal::isModSubtotalLine($line) ) {
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
						if ($object->statut == 0  && $createRight && !empty($conf->global->SUBTOTAL_ALLOW_DUPLICATE_BLOCK) && $object->element !== 'invoice_supplier')
						{
							if(TSubtotal::isTitle($line) && ($object->situation_counter == 1 || !$object->situation_cycle_ref) ) echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=duplicate&lineid='.$line->id.'">'. img_picto($langs->trans('Duplicate'), 'duplicate@subtotal').'</a>';
						}

						if ($object->statut == 0  && $createRight && !empty($conf->global->SUBTOTAL_ALLOW_EDIT_BLOCK))
						{
							echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=editline&lineid='.$line->id.'">'.img_edit().'</a>';
						}
					}

				}

				?>
			</td>

			<td align="center" nowrap="nowrap" class="linecoldelete">
				<?php

				if ($action != 'editline' && $action != 'selectlines') {
						if ($object->statut == 0  && $createRight && !empty($conf->global->SUBTOTAL_ALLOW_REMOVE_BLOCK))
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


			if ($num > 1 && empty($conf->browser->phone)) { ?>
			<td align="center" class="tdlineupdown">
			</td>
			<?php } else { ?>
			<td align="center"<?php echo ((empty($conf->browser->phone) && ($object->statut == 0  && $createRight ))?' class="tdlineupdown"':''); ?>></td>
			<?php } ?>

			<?php  if($action == 'selectlines'){ // dolibarr 8 ?>
			<td class="linecolcheck" align="center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php echo $i+1; ?>]" value="<?php echo $line->id; ?>" ></td>
			<?php } ?>

			</tr>
			<?php

			return 1;

		}

		return 0;

	}


	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $conf,$langs;

		$this->_ajax_block_order_js($object);
	}

	function afterPDFCreation($parameters, &$pdf, &$action, $hookmanager)
	{
		global $conf;

		$object = $parameters['object'];

	}

	// HTML 5 data for js
	private function _getHtmlData($parameters, &$object, &$action, $hookmanager)
	{
		dol_include_once('/subtotal/class/subtotal.class.php');

	    $line = &$parameters['line'];

	    $ThtmlData['data-id']           = $line->id;
	    $ThtmlData['data-product_type'] = $line->product_type;
	    $ThtmlData['data-qty']          = 0; //$line->qty;
	    $ThtmlData['data-level']        = TSubtotal::getNiveau($line);

	    if(TSubtotal::isTitle($line)){
	        $ThtmlData['data-issubtotal'] = 'title';
	    }elseif(TSubtotal::isSubtotal($line)){
	        $ThtmlData['data-issubtotal'] = 'subtotal';
	    }
	    else{
	        $ThtmlData['data-issubtotal'] = 'freetext';
	    }


	    // Change or add data  from hooks
	    $parameters = array_replace($parameters , array(  'ThtmlData' => $ThtmlData )  );

	    // hook
	    $reshook = $hookmanager->executeHooks('subtotalLineHtmlData',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
	    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	    if ($reshook>0)
	    {
	        $ThtmlData = $hookmanager->resArray;
	    }

	    return $this->implodeHtmlData($ThtmlData);

	}


	function implodeHtmlData($ThtmlData = array())
	{
	    $data = '';
	    foreach($ThtmlData as $k => $h )
	    {
	        if(is_array($h))
	        {
	            $h = json_encode($h);
	        }

	        $data .= $k . '="'.dol_htmlentities($h, ENT_QUOTES).'" ';
	    }

	    return $data;
	}

	function _ajax_block_order_js($object)
	{
	    global $conf,$tagidfortablednd,$filepath,$langs;

	    /*
	     * this part of js is base on dolibarr htdocs/core/tpl/ajaxrow.tpl.php
	     * for compatibility reasons we don't use tableDnD but jquery sortable
	     */

	    $id=$object->id;
	    $nboflines=(isset($object->lines)?count($object->lines):0);
	    $forcereloadpage=empty($conf->global->MAIN_FORCE_RELOAD_PAGE)?0:1;

	    $id=$object->id;
	    $fk_element=$object->fk_element;
	    $table_element_line=$object->table_element_line;
	    $nboflines=(isset($object->lines)?count($object->lines):(empty($nboflines)?0:$nboflines));
	    $tagidfortablednd=(empty($tagidfortablednd)?'tablelines':$tagidfortablednd);
	    $filepath=(empty($filepath)?'':$filepath);


	    if (GETPOST('action','aZ09') != 'editline' && $nboflines > 1)
	    {

	        ?>


			<script type="text/javascript">
			$(document).ready(function(){

				// target some elements
				var titleRow = $('tr[data-issubtotal="title"]');
				var lastTitleCol = titleRow.find('td:last-child');
				var moveBlockCol= titleRow.find('td.liencolht');


				moveBlockCol.disableSelection(); // prevent selection
<?php if ($object->statut == 0) { ?>
				// apply some graphical stuff
				moveBlockCol.css("background-image",'url(<?php echo dol_buildpath('subtotal/img/grip_all.png',2);  ?>)');
				moveBlockCol.css("background-repeat","no-repeat");
				moveBlockCol.css("background-position","center center");
				moveBlockCol.css("cursor","move");
				titleRow.attr('title', '<?php echo html_entity_decode($langs->trans('MoveTitleBlock')); ?>');


 				$( "#<?php echo $tagidfortablednd; ?>" ).sortable({
			    	  cursor: "move",
			    	  handle: ".movetitleblock",
			    	  items: 'tr:not(.nodrag,.nodrop,.noblockdrop)',
			    	  delay: 150, //Needed to prevent accidental drag when trying to select
			    	  opacity: 0.8,
			    	  axis: "y", // limit y axis
			    	  placeholder: "ui-state-highlight",
			    	  start: function( event, ui ) {
			    	      //console.log('X:' + e.screenX, 'Y:' + e.screenY);
			    		  //console.log(ui.item);
			    		  var colCount = ui.item.children().length;
   						  ui.placeholder.html('<td colspan="'+colCount+'">&nbsp;</td>');

			    		  var TcurrentChilds = getSubtotalTitleChilds(ui.item);
			    		  ui.item.data('childrens',TcurrentChilds); // store data

			    		  for (var key in TcurrentChilds) {
			    			  $('#'+ TcurrentChilds[key]).addClass('noblockdrop');//'#row-'+
			    			  $('#'+ TcurrentChilds[key]).fadeOut();//'#row-'+
			    		  }

			    		  $(this).sortable("refresh");	// "refresh" of source sortable is required to make "disable" work!

			    	    },
				    	stop: function (event, ui) {
							// call we element is droped
				    	  	$('.noblockdrop').removeClass('noblockdrop');

				    	  	var TcurrentChilds = ui.item.data('childrens'); // reload child list from data and not attr to prevent load error

							for (var i =TcurrentChilds.length ; i >= 0; i--) {
				    			  $('#'+ TcurrentChilds[i]).insertAfter(ui.item); //'#row-'+
				    			  $('#'+ TcurrentChilds[i]).fadeIn(); //'#row-'+
							}
							console.log('onstop');
							console.log(cleanSerialize($(this).sortable('serialize')));

							$.ajax({
			    	            data: {
									objet_id: <?php print $object->id; ?>,
							    	roworder: cleanSerialize($(this).sortable('serialize')),
									table_element_line: "<?php echo $table_element_line; ?>",
									fk_element: "<?php echo $fk_element; ?>",
									element_id: "<?php echo $id; ?>",
									filepath: "<?php echo urlencode($filepath); ?>"
								},
			    	            type: 'POST',
			    	            url: '<?php echo DOL_URL_ROOT; ?>/core/ajax/row.php',
			    	            success: function(data) {
			    	                console.log(data);
			    	            },
			    	        });

			    	  },
			    	  update: function (event, ui) {

			    	        // POST to server using $.post or $.ajax
				    	  	$('.noblockdrop').removeClass('noblockdrop');
							//console.log('onupdate');
			    	        //console.log(cleanSerialize($(this).sortable('serialize')));
			    	    }
			    });
 				<?php } ?>

				function getSubtotalTitleChilds(item)
				{
		    		var TcurrentChilds = []; // = JSON.parse(item.attr('data-childrens'));
		    		var level = item.data('level');

		    		var indexOfFirstSubtotal = -1;
		    		var indexOfFirstTitle = -1;

		    		item.nextAll('[id^="row-"]').each(function(index){

						var dataLevel = $(this).attr('data-level');
						var dataIsSubtotal = $(this).attr('data-issubtotal');

						if(dataIsSubtotal != 'undefined' && dataLevel != 'undefined' )
						{

							if(dataLevel <=  level && indexOfFirstSubtotal < 0 && dataIsSubtotal == 'subtotal' )
							{
								indexOfFirstSubtotal = index;
								if(indexOfFirstTitle < 0)
								{
									TcurrentChilds.push($(this).attr('id'));
								}
							}

							if(dataLevel <=  level && indexOfFirstSubtotal < 0 && indexOfFirstTitle < 0 && dataIsSubtotal == 'title' )
							{
								indexOfFirstTitle = index;
							}
						}

						if(indexOfFirstTitle < 0 && indexOfFirstSubtotal < 0)
						{
							TcurrentChilds.push($(this).attr('id'));

							// Add extraffield support for dolibarr > 7
							var thisId = $(this).attr('data-id');
							var thisElement = $(this).attr('data-element');
							if(thisId != undefined && thisElement != undefined )
							{
								$('[data-targetid="' + thisId + '"][data-element="extrafield"][data-targetelement="'+ thisElement +'"]').each(function(index){
									TcurrentChilds.push($(this).attr('id'));
								});
							}

						}

		    		});
		    		return TcurrentChilds;
				}

			});
			</script>
			<style type="text/css" >

            tr.ui-state-highlight td{
            	border: 1px solid #dad55e;
            	background: #fffa90;
            	color: #777620;
            }
            </style>
		<?php

		}



	}

	function printOriginObjectLine($parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$langs,$user,$db,$bc, $restrictlist, $selectedLines;

		$line = &$parameters['line'];
		$i = &$parameters['i'];

		$var = &$parameters['var'];

		$contexts = explode(':',$parameters['context']);

		if (in_array('ordercard',$contexts) || in_array('invoicecard',$contexts))
		{
			if(class_exists('TSubtotal')){ dol_include_once('/subtotal/class/subtotal.class.php'); }

			if (TSubtotal::isModSubtotalLine($line))
			{
				$object->tpl['subtotal'] = $line->id;
				if (TSubtotal::isTitle($line)) $object->tpl['sub-type'] = 'title';
				else if (TSubtotal::isSubtotal($line)) $object->tpl['sub-type'] = 'total';

				$object->tpl['sub-tr-style'] = '';
				if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT))
				{
					if($line->qty==99) $object->tpl['sub-tr-style'].= 'background:#adadcf';
					else if($line->qty==98) $object->tpl['sub-tr-style'].= 'background:#ddddff;';
					else if($line->qty<=97 && $line->qty>=91) $object->tpl['sub-tr-style'].= 'background:#eeeeff;';
					else if($line->qty==1) $object->tpl['sub-tr-style'].= 'background:#adadcf;';
					else if($line->qty==2) $object->tpl['sub-tr-style'].= 'background:#ddddff;';
					else if($line->qty==50) $object->tpl['sub-tr-style'].= '';
					else $object->tpl['sub-tr-style'].= 'background:#eeeeff;';

					//A compléter si on veux plus de nuances de couleurs avec les niveau 4,5,6,7,8 et 9
				}
				else
				{
					if($line->qty==99) $object->tpl['sub-tr-style'].= 'background:#ddffdd';
					else if($line->qty==98) $object->tpl['sub-tr-style'].= 'background:#ddddff;';
					else if($line->qty==2) $object->tpl['sub-tr-style'].= 'background:#eeeeff; ';
					else if($line->qty==50) $object->tpl['sub-tr-style'].= '';
					else $object->tpl['sub-tr-style'].= 'background:#eeffee;' ;
				}

				$object->tpl['sub-td-style'] = '';
				if ($line->qty>90) $object->tpl['sub-td-style'] = 'style="text-align:right"';


				if ($conf->global->SUBTOTAL_USE_NEW_FORMAT)
				{
					if(TSubtotal::isTitle($line) || TSubtotal::isSubtotal($line))
					{
						$object->tpl["sublabel"] = str_repeat('&nbsp;&nbsp;&nbsp;', $line->qty-1);

						if (TSubtotal::isTitle($line)) $object->tpl["sublabel"].= img_picto('', 'subtotal@subtotal').'<span style="font-size:9px;margin-left:-3px;">'.$line->qty.'</span>&nbsp;&nbsp;';
						else $object->tpl["sublabel"].= img_picto('', 'subtotal2@subtotal').'<span style="font-size:9px;margin-left:-1px;">'.(100-$line->qty).'</span>&nbsp;&nbsp;';
					}
				}
				else
				{
					$object->tpl["sublabel"] = '';
					if($line->qty<=1) $object->tpl["sublabel"] = img_picto('', 'subtotal@subtotal');
					else if($line->qty==2) $object->tpl["sublabel"] = img_picto('', 'subsubtotal@subtotal').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}

				// Get display styles and apply them
				$titleStyleItalic = strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'I') === false ? '' : ' font-style: italic;';
				$titleStyleBold =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'B') === false ? '' : ' font-weight:bold;';
				$titleStyleUnderline =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'U') === false ? '' : ' text-decoration: underline;';

				if (empty($line->label)) {
					if ($line->qty >= 91 && $line->qty <= 99 && $conf->global->SUBTOTAL_USE_NEW_FORMAT) $object->tpl["sublabel"].=  $line->description.' '.$this->getTitle($object, $line);
					else $object->tpl["sublabel"].=  $line->description;
				}
				else {

					if (! empty($conf->global->PRODUIT_DESC_IN_FORM) && !empty($line->description)) {
						$object->tpl["sublabel"].= '<span class="subtotal_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" >'.$line->label.'</span><br><div class="subtotal_desc">'.dol_htmlentitiesbr($line->description).'</div>';
					}
					else{
						$object->tpl["sublabel"].= '<span class="subtotal_label classfortooltip '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
					}

				}
				if($line->qty>90)
				{
					$total = $this->getTotalLineFromObject($object, $line, '');
					$object->tpl["sublabel"].= ' : <b>'.$total.'</b>';
				}



			}

			$object->printOriginLine($line, '', $restrictlist, '/core/tpl', $selectedLines);

			unset($object->tpl["sublabel"]);
			unset($object->tpl['sub-td-style']);
			unset($object->tpl['sub-tr-style']);
			unset($object->tpl['sub-type']);
			unset($object->tpl['subtotal']);
		}

		return 0;
	}

}
