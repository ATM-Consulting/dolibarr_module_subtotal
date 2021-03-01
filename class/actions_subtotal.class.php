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

	function printFieldListSelect($parameters, &$object, &$action, $hookmanager) {

		global $type_element, $where;

		$contexts = explode(':',$parameters['context']);

		if(in_array('consumptionthirdparty',$contexts) && in_array($type_element, array('propal', 'order', 'invoice', 'supplier_order', 'supplier_invoice', 'supplier_proposal'))) {
			$mod_num = TSubtotal::$module_number;

			// Not a title (can't use TSubtotal class methods in sql)
			$where.= ' AND (d.special_code != '.$mod_num.' OR d.product_type != 9 OR d.qty > 9)';
			// Not a subtotal (can't use TSubtotal class methods in sql)
			$where.= ' AND (d.special_code != '.$mod_num.' OR d.product_type != 9 OR d.qty < 90)';
			// Not a free line text (can't use TSubtotal class methods in sql)
			$where.= ' AND (d.special_code != '.$mod_num.' OR d.product_type != 9 OR d.qty != 50)';

		}

		return 0;
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

		return 0;
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

		if(in_array('ordercard',$contexts) || in_array('ordersuppliercard',$contexts) || in_array('propalcard',$contexts) || in_array('supplier_proposalcard',$contexts) || in_array('invoicecard',$contexts) || in_array('invoicesuppliercard',$contexts) || in_array('invoicereccard',$contexts) || in_array('expeditioncard',$contexts)) {

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
			elseif($object->element == 'shipping')
			{
				$createRight = true; // No rights management for shipments
			}

			if ($object->statut == 0  && $createRight) {


				if($object->element=='facture')$idvar = 'facid';
				else $idvar='id';

				if(in_array($action, array('add_title_line', 'add_total_line', 'add_subtitle_line', 'add_subtotal_line', 'add_free_text')) )
				{
					$level = GETPOST('level', 'int'); //New avec SUBTOTAL_USE_NEW_FORMAT

					if($action=='add_title_line') {
						$title = GETPOST('title', 'none');
						if(empty($title)) $title = $langs->trans('title');
						$qty = $level<1 ? 1 : $level ;
					}
					else if($action=='add_free_text') {
						$title = GETPOST('title', 'restricthtml');

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
						$title = GETPOST('title', 'none');
						if(empty($title)) $title = $langs->trans('subtitle');
						$qty = 2;
					}
					else if($action=='add_subtotal_line') {
						$title = $langs->trans('SubSubTotal');
						$qty = 98;
					}
					else {
						$title = GETPOST('title', 'none') ? GETPOST('title', 'none') : $langs->trans('SubTotal');
						$qty = $level ? 100-$level : 99;
					}
					dol_include_once('/subtotal/class/subtotal.class.php');

					if (!empty($conf->global->SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE) && $qty < 10) TSubtotal::addSubtotalMissing($object, $qty);

	    			TSubtotal::addSubTotalLine($object, $title, $qty);
				}
				else if($action==='ask_deleteallline') {
						$form=new Form($db);

						$lineid = GETPOST('lineid','integer');
						$TIdForGroup = TSubtotal::getLinesFromTitleId($object, $lineid, true);

						$nbLines = count($TIdForGroup);

						$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('deleteWithAllLines'), $langs->trans('ConfirmDeleteAllThisLines',$nbLines), 'confirm_delete_all_lines','',0,1);
						print $formconfirm;
				}

				if (!empty($conf->global->SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE))
				{
					$this->showSelectTitleToAdd($object);
				}


				if($object->element != 'shipping' && $action!='editline') {
					// New format is for 3.8
					$this->printNewFormat($object, $conf, $langs, $idvar);
				}
			}
		}
		elseif ((!empty($parameters['currentcontext']) && $parameters['currentcontext'] == 'orderstoinvoice') || in_array('orderstoinvoice',$contexts) || in_array('orderstoinvoicesupplier',$contexts))
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
		if ($line->fk_prev_id != null && !empty($line->fk_prev_id)) return false; // Si facture de situation
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
						 dialog_html += '<input id="token" name="token" type="hidden" value="<?php echo ((float) DOL_VERSION < 11.0) ?  $_SESSION['newtoken'] : newToken(); ?>" />';

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
							for (var i=1;i<10;i++)
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
									params.title = (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && "sub-total-title" in CKEDITOR.instances ? CKEDITOR.instances["sub-total-title"].getData() : $(this).find('#sub-total-title').val());
									params.under_title = $(this).find('select[name=under_title]').val();
									params.free_text = $(this).find('select[name=free_text]').val();
									params.level = $(this).find('select[name=subtotal_line_level]').val();
									params.token = $(this).find('input[name=token]').val();

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


	function formBuilddocOptions($parameters, &$object) {
	/* Réponse besoin client */

		global $conf, $langs, $bc;

		$action = GETPOST('action', 'none');
		$TContext = explode(':',$parameters['context']);
		if (
				in_array('invoicecard',$TContext)
		        || in_array('invoicesuppliercard',$TContext)
				|| in_array('propalcard',$TContext)
				|| in_array('ordercard',$TContext)
		        || in_array('ordersuppliercard',$TContext)
				|| in_array('invoicereccard',$TContext)
			)
	        {
	            $hideInnerLines	= isset( $_SESSION['subtotal_hideInnerLines_'.$parameters['modulepart']][$object->id] ) ?  $_SESSION['subtotal_hideInnerLines_'.$parameters['modulepart']][$object->id] : 0;
	            $hidedetails	= isset( $_SESSION['subtotal_hidedetails_'.$parameters['modulepart']][$object->id] ) ?  $_SESSION['subtotal_hidedetails_'.$parameters['modulepart']][$object->id] : 0;
				$hidepricesDefaultConf = !empty($conf->global->SUBTOTAL_HIDE_PRICE_DEFAULT_CHECKED)?$conf->global->SUBTOTAL_HIDE_PRICE_DEFAULT_CHECKED:0;
				$hideprices= isset( $_SESSION['subtotal_hideprices_'.$parameters['modulepart']][$object->id] ) ?  $_SESSION['subtotal_hideprices_'.$parameters['modulepart']][$object->id] : $hidepricesDefaultConf;

				$var=false;
				$out = '';
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

		     	$var=!$var;
		     	$out.= '<tr '.$bc[$var].'>
		     			<td colspan="4" align="right">
		     				<label for="hideprices">'.$langs->trans('SubTotalhidePrice').'</label>
		     				<input type="checkbox" id="hideprices" name="hideprices" value="1" '.(( $hideprices ) ? 'checked="checked"' : '' ).' />
		     			</td>
		     			</tr>';



				if (
					(in_array('propalcard',$TContext) && !empty($conf->global->SUBTOTAL_PROPAL_ADD_RECAP))
					|| (in_array('ordercard',$TContext) && !empty($conf->global->SUBTOTAL_COMMANDE_ADD_RECAP))
				    || (in_array('ordersuppliercard',$TContext) && !empty($conf->global->SUBTOTAL_COMMANDE_ADD_RECAP))
					|| (in_array('invoicecard',$TContext) && !empty($conf->global->SUBTOTAL_INVOICE_ADD_RECAP))
				    || (in_array('invoicesuppliercard',$TContext) && !empty($conf->global->SUBTOTAL_INVOICE_ADD_RECAP))
					|| (in_array('invoicereccard',$TContext)  && !empty($conf->global->SUBTOTAL_INVOICE_ADD_RECAP ))
				)
				{
					$var=!$var;
					$out.= '
						<tr '.$bc[$var].'>
							<td colspan="4" align="right">
								<label for="subtotal_add_recap">'.$langs->trans('subtotal_add_recap').'</label>
								<input type="checkbox" id="subtotal_add_recap" name="subtotal_add_recap" value="1" '.( GETPOST('subtotal_add_recap', 'none') ? 'checked="checked"' : '' ).' />
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

            $substitutionarray['line_not_modsubtotal'] = true;
            $substitutionarray['line_modsubtotal'] = false;
            $substitutionarray['line_modsubtotal_total'] = false;
            $substitutionarray['line_modsubtotal_title'] = false;

			if($line->product_type == 9 && $line->special_code == $this->module_number) {
				$substitutionarray['line_modsubtotal'] = 1;
                $substitutionarray['line_not_modsubtotal'] = false;

				$substitutionarray['line_price_ht']
					 = $substitutionarray['line_price_vat']
					 = $substitutionarray['line_price_ttc']
					 = $substitutionarray['line_vatrate']
					 = $substitutionarray['line_qty']
					 = $substitutionarray['line_up']
					 = '';

				if($line->qty>90) {
					$substitutionarray['line_modsubtotal_total'] = true;

					//list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, '', 1);
                    $TInfo = $this->getTotalLineFromObject($object, $line, '', 1);

					$substitutionarray['line_price_ht'] = price($TInfo[0]);
					$substitutionarray['line_price_vat'] = price($TInfo[1]);
					$substitutionarray['line_price_ttc'] = price($TInfo[2]);
				} else {
					$substitutionarray['line_modsubtotal_title'] = true;
				}


			}
			else{
				$substitutionarray['line_not_modsubtotal'] = true;
				$substitutionarray['line_modsubtotal'] = 0;
			}

		}

		return 0;
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

			if(empty($object->lines) && method_exists($object, 'fetch_lines')) $object->fetch_lines();

			foreach($objFrom->lines as $k=> &$lineOld) {

					if($lineOld->product_type == 9 && $lineOld->info_bits > 0 ) {

							$line = & $object->lines[$k];

							$idLine = (int) ($line->id ? $line->id : $line->rowid);

							if($line->info_bits != $lineOld->info_bits) {
								$db->query("UPDATE ".MAIN_DB_PREFIX.$line->table_element."
								SET info_bits=".(int)$lineOld->info_bits."
								WHERE rowid = ".$idLine."
								");
							}

					}


			}


		}

		return 0;
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
				if(empty($_SESSION[$sessname]) || !is_array($_SESSION[$sessname][$object->id]) ) $_SESSION[$sessname] = array(); // prevent old system
				$_SESSION[$sessname][$object->id] = $hideInnerLines;

				$hidedetails= GETPOST('hidedetails', 'int');
				if(empty($_SESSION[$sessname2]) || !is_array($_SESSION[$sessname2][$object->id]) ) $_SESSION[$sessname2] = array(); // prevent old system
				$_SESSION[$sessname2][$object->id] = $hidedetails;

				$hideprices= GETPOST('hideprices', 'int');
				if(empty($_SESSION[$sessname3]) || !is_array($_SESSION[$sessname3][$object->id]) ) $_SESSION[$sessname3] = array(); // prevent old system
				$_SESSION[$sessname3][$object->id] = $hideprices;

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
		else if($action === 'confirm_delete_all_lines' && GETPOST('confirm', 'none')=='yes') {

			$Tab = TSubtotal::getLinesFromTitleId($object, GETPOST('lineid', 'int'), true);
			foreach($Tab as $line) {
                $result = 0;

				$idLine = $line->id;
				/**
				 * @var $object Facture
				 */
				if($object->element=='facture') $result = $object->deleteline($idLine);
				/**
				 * @var $object Facture fournisseur
				 */
				else if($object->element=='invoice_supplier')
				{
					$result = $object->deleteline($idLine);
				}
				/**
				 * @var $object Propal
				 */
				else if($object->element=='propal') $result = $object->deleteline($idLine);
				/**
				 * @var $object Propal Fournisseur
				 */
				else if($object->element=='supplier_proposal') $result = $object->deleteline($idLine);
				/**
				 * @var $object Commande
				 */
				else if($object->element=='commande')
				{
					if ((float) DOL_VERSION >= 5.0) $result = $object->deleteline($user, $idLine);
					else $result = $object->deleteline($idLine);
				}
				/**
				 * @var $object Commande fournisseur
				 */
				else if($object->element=='order_supplier')
				{
                    			$result = $object->deleteline($idLine);
				}
				/**
				 * @var $object Facturerec
				 */
				else if($object->element=='facturerec') $result = $object->deleteline($idLine);
				/**
				 * @var $object Expedition
				 */
				else if($object->element=='shipping') $result = $object->deleteline($user, $idLine);

                if ($result < 0) $error++;
			}

            if ($error) {
                setEventMessages($object->error, $object->errors, 'errors');
                $db->rollback();
            } else {
                $db->commit();
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

	function changeRoundingMode($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;
		if (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && !empty($object->table_element_line) && in_array($object->element, array('commande', 'facture', 'propal')))
		{
			if ($object->element == 'commande')
				$obj = new OrderLine($object->db);
			if ($object->element == 'propal')
				$obj = new PropaleLigne($object->db);
			if ($object->element == 'facture')
				$obj = new FactureLigne($object->db);
			if (!empty($parameters['fk_element']))
			{

				if($obj->fetch($parameters['fk_element'])){
					$obj->id= $obj->rowid;
					if (empty($obj->array_options))
						$obj->fetch_optionals();
					if (!empty($obj->array_options['options_subtotal_nc']))
						return 1;
				}
			}
		}

		return 0;
	}

	function getArrayOfLineForAGroup(&$object, $lineid) {
		$qty_line = 0;
        $qty_end_line = 0;
		$found = false;
		$Tab= array();

		foreach($object->lines as $l) {
		    $lid = (!empty($l->rowid) ? $l->rowid : $l->id);

			if($lid == $lineid && $l->qty > 0 && $l->qty < 10) {
				$found = true;
				$qty_line = $l->qty;
                $qty_end_line = 100 - $qty_line;
			}

			if($found) {
                if ($l->special_code == $this->module_number && $lid != $lineid && ($l->qty <= $qty_line || $l->qty >= $qty_end_line)) {
                    if ($l->qty == $qty_end_line) $Tab[] = $lid;
                    break;
                }
                else $Tab[] = $lid;
			}
		}

		return $Tab;
	}

	function getTotalLineFromObject(&$object, &$line, $use_level=false, $return_all=0) {
		global $conf;

		$rang = $line->rang;
		$qty_line = $line->qty;
		$lvl = 0;
        if (TSubtotal::isSubtotal($line)) $lvl = TSubtotal::getNiveau($line);

		$title_break = TSubtotal::getParentTitleOfLine($object, $rang, $lvl);

		$total = 0;
		$total_tva = 0;
		$total_ttc = 0;
		$TTotal_tva = array();


		$sign=1;
		if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

		if (GETPOST('action', 'none') == 'builddoc') $builddoc = true;
		else $builddoc = false;

		dol_include_once('/subtotal/class/subtotal.class.php');

		$TLineReverse = array_reverse($object->lines);

		foreach($TLineReverse as $l)
		{
			$l->total_ttc = doubleval($l->total_ttc);
			$l->total_ht = doubleval($l->total_ht);

			//print $l->rang.'>='.$rang.' '.$total.'<br/>';
            if ($l->rang>=$rang) continue;
            if (!empty($title_break) && $title_break->id == $l->id) break;
            elseif (!TSubtotal::isModSubtotalLine($l))
            {
                // TODO retirer le test avec $builddoc quand Dolibarr affichera le total progression sur la card et pas seulement dans le PDF
                if ($builddoc && $object->element == 'facture' && $object->type==Facture::TYPE_SITUATION)
                {
                    if ($l->situation_percent > 0 && !empty($l->total_ht))
                    {
                        $prev_progress = 0;
                        $progress = 1;
                        if (method_exists($l, 'get_prev_progress'))
                        {
                            $prev_progress = $l->get_prev_progress($object->id);
                            $progress = ($l->situation_percent - $prev_progress) / 100;
                        }

                        $result = $sign * ($l->total_ht / ($l->situation_percent / 100)) * $progress;
                        $total+= $result;
                        // TODO check si les 3 lignes du dessous sont corrects
                        $total_tva += $sign * ($l->total_tva / ($l->situation_percent / 100)) * $progress;
                        $TTotal_tva[$l->tva_tx] += $sign * ($l->total_tva / ($l->situation_percent / 100)) * $progress;
                        $total_ttc += $sign * ($l->total_tva / ($l->total_ttc / 100)) * $progress;

                    }
                }
                else
                {
                    $total += $l->total_ht;
                    $total_tva += $l->total_tva;
                    $TTotal_tva[$l->tva_tx] += $l->total_tva;
                    $total_ttc += $l->total_ttc;
                }
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
		global $conf,$subtotal_last_title_posy,$langs;

		$hideInnerLines = GETPOST('hideInnerLines', 'int');
		if (!empty($conf->global->SUBTOTAL_ONE_LINE_IF_HIDE_INNERLINES) && $hideInnerLines && !empty($subtotal_last_title_posy))
		{
			$posy = $subtotal_last_title_posy;
			$subtotal_last_title_posy = null;
		}

		$hidePriceOnSubtotalLines = GETPOST('hide_price_on_subtotal_lines', 'int');

		if($object->element == 'shipping' || $object->element == 'delivery')
		{
			$hidePriceOnSubtotalLines = 1;
		}

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

		if(!empty($object->subtotalPdfModelInfo->cols)){
			include_once __DIR__ . '/staticPdf.model.php';
			$staticPdfModel = new ModelePDFStatic($object->db);
			$staticPdfModel->marge_droite 	= $object->subtotalPdfModelInfo->marge_droite;
			$staticPdfModel->marge_gauche 	= $object->subtotalPdfModelInfo->marge_gauche;
			$staticPdfModel->page_largeur 	= $object->subtotalPdfModelInfo->page_largeur;
			$staticPdfModel->page_hauteur 	= $object->subtotalPdfModelInfo->page_hauteur;
			$staticPdfModel->cols 			= $object->subtotalPdfModelInfo->cols;
			$staticPdfModel->defaultTitlesFieldsStyle 	= $object->subtotalPdfModelInfo->defaultTitlesFieldsStyle;
			$staticPdfModel->defaultContentsFieldsStyle = $object->subtotalPdfModelInfo->defaultContentsFieldsStyle;
			$staticPdfModel->prepareArrayColumnField($object, $langs);

			$pdf->SetXY($object->subtotalPdfModelInfo->marge_droite, $posy);
			$pdf->MultiCell($object->subtotalPdfModelInfo->page_largeur - $object->subtotalPdfModelInfo->marge_gauche - $object->subtotalPdfModelInfo->marge_droite, $cell_height, '', 0, '', 1);
		}
		else{
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell($pdf->page_largeur - $pdf->marge_droite, $cell_height, '', 0, '', 1);
		}

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
					//					list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, '', 1);

					$TInfo = $this->getTotalLineFromObject($object, $line, '', 1);
					$TTotal_tva = $TInfo[3];
					$total_to_print = price($TInfo[0]);

                    $line->total_ht = $TInfo[0];
					$line->total = $TInfo[0];
					if (!TSubtotal::isModSubtotalLine($line)) $line->total_tva = $TInfo[1];
					$line->total_ttc = $TInfo[2];
				}
			}

			$pdf->SetXY($pdf->postotalht, $posy);
			if($set_pagebreak_margin) $pdf->SetAutoPageBreak( $pageBreakOriginalValue , $bMargin);

			if(!empty($object->subtotalPdfModelInfo->cols)){
				$staticPdfModel->printStdColumnContent($pdf, $posy, 'totalexcltax', $total_to_print);
			}
			else{
				$pdf->MultiCell($pdf->page_largeur-$pdf->marge_droite-$pdf->postotalht, 3, $total_to_print, 0, 'R', 0);
			}
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

		$line = $object->lines[$i];

		if($object->element == 'shipping' || $object->element == 'delivery')
		{
			dol_include_once('/commande/class/commande.class.php');
			$line = new OrderLine($object->db);
			$line->fetch($object->lines[$i]->fk_origin_line);
		}


		if($line->special_code == $this->module_number && $line->product_type == 9) {
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
		elseif (!empty($conf->global->SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY))
		{
			$hideInnerLines = GETPOST('hideInnerLines', 'int');
			$hidedetails = GETPOST('hidedetails', 'int');
			if (empty($hideInnerLines) && !empty($hidedetails))
			{
				$this->resprints = $object->lines[$parameters['i']]->qty;
			}
		}

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;

		/** Attention, ici on peut ce retrouver avec un objet de type stdClass à cause de l'option cacher le détail des ensembles avec la notion de Non Compris (@see beforePDFCreation()) et dû à l'appel de TSubtotal::hasNcTitle() */
		if (empty($object->lines[$i]->id)) return 0; // hideInnerLines => override $object->lines et Dolibarr ne nous permet pas de mettre à jour la variable qui conditionne la boucle sur les lignes (PR faite pour 6.0)

		if(empty($object->lines[$i]->array_options)) $object->lines[$i]->fetch_optionals();

		if (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && (!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i])) )
		{
			if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
			{
				$this->resprints = ' ';
				return 1;
			}
		}

		return 0;
	}

	function pdf_getlinetotalexcltax($parameters=array(), &$object, &$action='') {
	    global $conf, $hideprices, $hookmanager;

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;

		if($this->isModSubtotalLine($parameters,$object) ){

			$this->resprints = ' ';

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}

		}
		elseif (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS))
		{
			if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
			{
				if (!empty($object->lines[$i]->array_options['options_subtotal_nc']))
				{
					$this->resprints = ' ';
					return 1;
				}

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
		}
		if (GETPOST('hideInnerLines', 'int') && !empty($conf->global->SUBTOTAL_REPLACE_WITH_VAT_IF_HIDE_INNERLINES)){
		    $this->resprints = price($object->lines[$i]->total_ht);
		}

		// Si la gestion C/NC est active et que je suis sur un ligne dont l'extrafield est coché
		if (
			!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) &&
			(!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i]))
		)
		{
			// alors je dois vérifier si la méthode fait partie de la conf qui l'exclue
			if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
			{
				$this->resprints = ' ';

				// currentcontext à modifier celon l'appel
				$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinetotalexcltax', 'currentcontext'=>'subtotal_hide_nc', 'i' => $i);
				return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
			}
		}
		// Cache le prix pour les lignes standards dolibarr qui sont dans un ensemble
		else if (!empty($hideprices))
		{
			// Check if a title exist for this line && if the title have subtotal
			$lineTitle = TSubtotal::getParentTitleOfLine($object, $object->lines[$i]->rang);
			if ($lineTitle && TSubtotal::titleHasTotalLine($object, $lineTitle, true))
			{

				$this->resprints = ' ';

				// currentcontext à modifier celon l'appel
				$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinetotalexcltax', 'currentcontext'=>'subtotal_hideprices', 'i' => $i);
				return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
			}
		}

		return 0;
	}

	/**
	 * Remplace le retour de la méthode qui l'appelle par un standard 1 ou autre chose celon le hook
	 * @return int 1, 0, -1
	 */
	private function callHook(&$object, &$hookmanager, $action, $params, $defaultReturn = 1)
	{
		$reshook=$hookmanager->executeHooks('subtotalHidePrices',$params, $object, $action);
		if ($reshook < 0)
		{
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
			return -1;
		}
		elseif (empty($reshook))
		{
			$this->resprints .= $hookmanager->resprints;
		}
		else
		{
			$this->resprints = $hookmanager->resprints;

			// override return (use  $this->results['overrideReturn'] or $this->resArray['overrideReturn'] in other module action_xxxx.class.php )
			if(isset($hookmanager->resArray['overrideReturn']))
			{
				return $hookmanager->resArray['overrideReturn'];
			}
		}

		return $defaultReturn;
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

		if (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && (!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i])) )
		{
			if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
			{
				$this->resprints = ' ';
				return 1;
			}
		}

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

		if (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && (!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i])) )
		{
			if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
			{
				$this->resprints = ' ';
				return 1;
			}
		}

		return 0;
	}

	function pdf_getlineupexcltax($parameters=array(), &$object, &$action='') {
	    global $conf,$hideprices,$hookmanager;

		if(is_array($parameters)) $i = & $parameters['i'];
		else $i = (int)$parameters;

		if($this->isModSubtotalLine($parameters,$object) ) {
			$this->resprints = ' ';

            $line = $object->lines[$i];

            // On récupère les montants du bloc pour les afficher dans la ligne de sous-total
            if(TSubtotal::isSubtotal($line)) {
                $parentTitle = TSubtotal::getParentTitleOfLine($object, $line->rang);

                if(is_object($parentTitle) && empty($parentTitle->array_options)) $parentTitle->fetch_optionals();
                if(! empty($parentTitle->array_options['options_show_total_ht'])) {
                    $TTotal = TSubtotal::getTotalBlockFromTitle($object, $parentTitle);
                    $this->resprints = price($TTotal['total_unit_subprice']);
                }
            }

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}

		// Si la gestion C/NC est active et que je suis sur un ligne dont l'extrafield est coché
		if (
		!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) &&
		(!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i]))
		)
		{
		    // alors je dois vérifier si la méthode fait partie de la conf qui l'exclue
		    if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
		    {
		        $this->resprints = ' ';

		        // currentcontext à modifier celon l'appel
		        $params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlineupexcltax', 'currentcontext'=>'subtotal_hide_nc', 'i' => $i);
		        return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)

		    }
		}
		// Cache le prix pour les lignes standards dolibarr qui sont dans un ensemble
		else if (!empty($hideprices))
		{

		    // Check if a title exist for this line && if the title have subtotal
		    $lineTitle = TSubtotal::getParentTitleOfLine($object, $object->lines[$i]->rang);
		    if ($lineTitle && TSubtotal::titleHasTotalLine($object, $lineTitle, true))
		    {

		        $this->resprints = ' ';

		        // currentcontext à modifier celon l'appel
		        $params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlineupexcltax', 'currentcontext'=>'subtotal_hideprices', 'i' => $i);
		        return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
		    }
		}

		return 0;
	}

	function pdf_getlineremisepercent($parameters=array(), &$object, &$action='') {
	    global $conf,$hideprices,$hookmanager;

        if(is_array($parameters)) $i = & $parameters['i'];
        else $i = (int) $parameters;

		if($this->isModSubtotalLine($parameters,$object) ) {
			$this->resprints = ' ';

            $line = $object->lines[$i];

            // Affichage de la remise
            if(TSubtotal::isSubtotal($line)) {
                $parentTitle = TSubtotal::getParentTitleOfLine($object, $line->rang);

                if(empty($parentTitle->array_options)) $parentTitle->fetch_optionals();
                if(! empty($parentTitle->array_options['options_show_reduc'])) {
                    $TTotal = TSubtotal::getTotalBlockFromTitle($object, $parentTitle);
                    $this->resprints = price((1-$TTotal['total_ht'] / $TTotal['total_subprice'])*100, 0, '', 1, 2, 2).'%';
                }
            }

			if((float)DOL_VERSION<=3.6) {
				return '';
			}
			else if((float)DOL_VERSION>=3.8) {
				return 1;
			}
		}
		elseif (!empty($hideprices)
		        || (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && (!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i])) )
		        )
		    {
		        if (!empty($hideprices) || !in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
		        {
		            $this->resprints = ' ';
		            return 1;
		        }
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

		if (!empty($hideprices)
				|| (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && (!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i])) )
		)
		{
			if (!empty($hideprices) || !in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
			{
				$this->resprints = ' ';
				return 1;
			}
		}

		return 0;
	}

	function pdf_getlinevatrate($parameters=array(), &$object, &$action='') {
	    global $conf,$hideprices,$hookmanager;

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
		// Si la gestion C/NC est active et que je suis sur un ligne dont l'extrafield est coché
		if (
		!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) &&
		(!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i]))
		)
		{
		    // alors je dois vérifier si la méthode fait partie de la conf qui l'exclue
		    if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
		    {
		        $this->resprints = ' ';

		        // currentcontext à modifier celon l'appel
		        $params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinevatrate', 'currentcontext'=>'subtotal_hide_nc', 'i' => $i);
		        return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
		    }
		}
		// Cache le prix pour les lignes standards dolibarr qui sont dans un ensemble
		else if (!empty($hideprices))
		{

		    // Check if a title exist for this line && if the title have subtotal
		    $lineTitle = TSubtotal::getParentTitleOfLine($object, $object->lines[$i]->rang);
		    if ($lineTitle && TSubtotal::titleHasTotalLine($object, $lineTitle, true))
		    {

		        $this->resprints = ' ';

		        // currentcontext à modifier celon l'appel
		        $params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinevatrate', 'currentcontext'=>'subtotal_hideprices', 'i' => $i);
		        return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
		    }
		}

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

		if (!empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && (!empty($object->lines[$i]->array_options['options_subtotal_nc']) || TSubtotal::hasNcTitle($object->lines[$i])) )
		{
			if (!in_array(__FUNCTION__, explode(',', $conf->global->SUBTOTAL_TFIELD_TO_KEEP_WITH_NC)))
			{
				$this->resprints = ' ';
				return 1;
			}
		}

		return 0;
	}

	function add_numerotation(&$object) {
		global $conf;

		if(!empty($conf->global->SUBTOTAL_USE_NUMEROTATION)) {

			$TLineTitle = $TTitle = $TLineSubtotal = array();
			$prevlevel = 0;
			dol_include_once('/subtotal/class/subtotal.class.php');

			foreach($object->lines as $k=>&$line)
			{
				if ($line->id > 0 && $this->isModSubtotalLine($k, $object) && $line->qty <= 10)
				{
					$TLineTitle[] = &$line;
				}
				else if ($line->id > 0 && TSubtotal::isSubtotal($line))
				{
					$TLineSubtotal[] = &$line;
				}

			}

			if (!empty($TLineTitle))
			{
				$TTitleNumeroted = $this->formatNumerotation($TLineTitle);

				$TTitle = $this->getTitlesFlatArray($TTitleNumeroted);

				if (!empty($TLineSubtotal))
				{
					foreach ($TLineSubtotal as &$stLine)
					{
						$parentTitle = TSubtotal::getParentTitleOfLine($object, $stLine->rang);
						if (!empty($parentTitle) && array_key_exists($parentTitle->id, $TTitle))
						{
							$stLine->label = $TTitle[$parentTitle->id]['numerotation'] . ' ' . $stLine->label;
						}
					}
				}
			}
		}

	}

	private function getTitlesFlatArray($TTitleNumeroted = array(), &$resArray = array())
	{
		if (is_array($TTitleNumeroted) && !empty($TTitleNumeroted))
		{
			foreach ($TTitleNumeroted as $tn)
			{
				$resArray[$tn['line']->id] = $tn;
				if (array_key_exists('children', $tn))
				{
					$this->getTitlesFlatArray($tn['children'], $resArray);
				}

			}
		}

		return $resArray;
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

		$object->subtotalPdfModelInfo = new stdClass(); // see defineColumnFiel method in this class
		$object->subtotalPdfModelInfo->cols = false;

		// var_dump($object->lines);
		dol_include_once('/subtotal/class/subtotal.class.php');

		$i = $parameters['i'];
		foreach($parameters as $key=>$value) {
			${$key} = $value;
		}

		$this->setDocTVA($pdf, $object);

		$this->add_numerotation($object);

        foreach($object->lines as $k => &$l) {
            if(TSubtotal::isSubtotal($l)) {
                $parentTitle = TSubtotal::getParentTitleOfLine($object, $l->rang);
                if(is_object($parentTitle) && empty($parentTitle->array_options)) $parentTitle->fetch_optionals();
                if(! empty($parentTitle->id) && ! empty($parentTitle->array_options['options_show_reduc'])) {
                    $l->remise_percent = 100;    // Affichage de la réduction sur la ligne de sous-total
                }
            }


            // Pas de hook sur les colonnes du PDF expédition, on unset les bonnes variables
            if(($object->element == 'shipping' || $object->element == 'delivery') && $this->isModSubtotalLine($k, $object))
			{
				$l->qty = $l->qty_asked;
				unset($l->qty_asked, $l->qty_shipped, $l->volume, $l->weight);
			}
        }

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
						//list($total, $total_tva, $total_ttc, $TTotal_tva) = $this->getTotalLineFromObject($object, $line, '', 1);

						$TInfo = $this->getTotalLineFromObject($object, $line, '', 1);

						if (TSubtotal::getNiveau($line) == 1) $line->TTotal_tva = $TInfo[3];
						$line->total_ht = $TInfo[0];
						$line->total_tva = $TInfo[1];
						$line->total = $line->total_ht;
						$line->total_ttc = $TInfo[2];

//                        $TTitle = TSubtotal::getParentTitleOfLine($object, $line->rang);
//                        $parentTitle = array_shift($TTitle);
//                        if(! empty($parentTitle->id) && ! empty($parentTitle->array_option['options_show_total_ht'])) {
//                            exit('la?');
//                            $line->remise_percent = 100;    // Affichage de la réduction sur la ligne de sous-total
//                            $line->update();
//                        }
					}
//                    if(TSub)

				}

				if ($hideInnerLines)
				{
				    if(!empty($conf->global->SUBTOTAL_REPLACE_WITH_VAT_IF_HIDE_INNERLINES))
				    {
				        if($line->tva_tx != '0.000' && $line->product_type!=9){

    				        // on remplit le tableau de tva pour substituer les lignes cachées
    				        $TTvas[$line->tva_tx]['total_tva'] += $line->total_tva;
    				        $TTvas[$line->tva_tx]['total_ht'] += $line->total_ht;
    				        $TTvas[$line->tva_tx]['total_ttc'] += $line->total_ttc;
    				    }
    					if($line->product_type==9 && $line->rowid>0)
    					{
    					    //Cas où je doit cacher les produits et afficher uniquement les sous-totaux avec les titres
    					    // génère des lignes d'affichage des montants HT soumis à tva
    					    $nbtva = count($TTvas);
    					    if(!empty($nbtva)){
    					        foreach ($TTvas as $tx =>$val){
    					            $l = clone $line;
    					            $l->product_type = 1;
    					            $l->special_code = '';
    					            $l->qty = 1;
    					            $l->desc = $langs->trans('AmountBeforeTaxesSubjectToVATX%', $langs->transnoentitiesnoconv('VAT'), price($tx));
    					            $l->tva_tx = $tx;
    					            $l->total_ht = $val['total_ht'];
    					            $l->total_tva = $val['total_tva'];
    					            $l->total = $line->total_ht;
    					            $l->total_ttc = $val['total_ttc'];
    					            $TLines[] = $l;
    					            array_shift($TTvas);
    					       }
    					    }

    					    // ajoute la ligne de sous-total
    					    $TLines[] = $line;
    					}
				    } else {

				        if($line->product_type==9 && $line->rowid>0)
				        {
				            // ajoute la ligne de sous-total
				            $TLines[] = $line;
				        }
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
			if(!empty($nbtva) && $hideInnerLines && !empty($conf->global->SUBTOTAL_REPLACE_WITH_VAT_IF_HIDE_INNERLINES))
			{
			    foreach ($TTvas as $tx =>$val){
			        $l = clone $line;
			        $l->product_type = 1;
			        $l->special_code = '';
			        $l->qty = 1;
			        $l->desc = $langs->trans('AmountBeforeTaxesSubjectToVATX%', $langs->transnoentitiesnoconv('VAT'), price($tx));
			        $l->tva_tx = $tx;
			        $l->total_ht = $val['total_ht'];
			        $l->total_tva = $val['total_tva'];
			        $l->total = $line->total_ht;
			        $l->total_ttc = $val['total_ttc'];
			        $TLines[] = $l;
			        array_shift($TTvas);
			    }
			}

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

		// même si le foreach du dessu fait ce qu'il faut, l'IDE n'aime pas
		$outputlangs = $parameters['outputlangs'];
		$i = $parameters['i'];
		$posx = $parameters['posx'];
		$h = $parameters['h'];
		$w = $parameters['w'];

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

				if($object->element == 'delivery' && ! empty($object->commande->expeditions[$line->fk_origin_line])) unset($object->commande->expeditions[$line->fk_origin_line]);

				if($line->info_bits>0) { // PAGE BREAK
					$pdf->addPage();
					$posy = $pdf->GetY();
				}

				$label = $line->label;
				$description= !empty($line->desc) ? $outputlangs->convToOutputCharset($line->desc) : $outputlangs->convToOutputCharset($line->description);

				if(empty($label)) {
					$label = $description;
					$description='';
				}

				if($line->qty>90) {
					if ($conf->global->SUBTOTAL_USE_NEW_FORMAT)	$label .= ' '.$this->getTitle($object, $line);

					$pageBefore = $pdf->getPage();
					$this->pdf_add_total($pdf,$object, $line, $label, $description,$posx, $posy, $w, $h);
					$pageAfter = $pdf->getPage();

					if($pageAfter>$pageBefore) {
						//print "ST $pageAfter>$pageBefore<br>";
						$pdf->rollbackTransaction(true);
						$pdf->addPage('', '', true);
						$posy = $pdf->GetY();
						$this->pdf_add_total($pdf, $object, $line, $label, $description, $posx, $posy, $w, $h);
						$posy = $pdf->GetY();
						//print 'add ST'.$pdf->getPage().'<br />';
					}

					// On delivery PDF, we don't want quantities to appear and there are no hooks => setting text color to background color;
					if($object->element == 'delivery')
					{
						switch($line->qty)
						{
							case 99:
								$grey = 220;
								break;

							case 98:
								$grey = 230;
								break;

							default:
								$grey = 240;
						}

						$pdf->SetTextColor($grey, $grey, $grey);
					}

					$posy = $pdf->GetY();
					return 1;
				}
				else if ($line->qty < 10) {
					$pageBefore = $pdf->getPage();

					$this->pdf_add_title($pdf,$object, $line, $label, $description,$posx, $posy, $w, $h);
					$pageAfter = $pdf->getPage();

					if($object->element == 'delivery')
					{
						$pdf->SetTextColor(255,255,255);
					}

					$posy = $pdf->GetY();
					return 1;
				}

			return 0;
		}
		elseif (empty($object->lines[$parameters['i']]))
		{
			$this->resprints = -1;
		}

        return 0;
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
	function printObjectLine ($parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$langs,$user,$db,$bc;

		$num = &$parameters['num'];
		$line = &$parameters['line'];
		$i = &$parameters['i'];

		$var = &$parameters['var'];

		$contexts = explode(':',$parameters['context']);
		if($parameters['currentcontext'] === 'paiementcard') return 0;
		$originline = null;

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
		elseif($object->element == 'commande' && in_array('ordershipmentcard', $contexts))
		{
			// H4cK 4n0nYm0u$-style : $line n'est pas un objet instancié mais provient d'un fetch_object d'une requête SQL
			$line->id = $line->rowid;
			$line->product_type = $line->type;
		}
		elseif($object->element == 'shipping' || $object->element == 'delivery')
		{
			if(empty($line->origin_line_id) && ! empty($line->fk_origin_line))
			{
				$line->origin_line_id = $line->fk_origin_line;
			}

			$originline = new OrderLine($db);
			$originline->fetch($line->fk_origin_line);

			foreach(get_object_vars($line) as $property => $value)
			{
				if(empty($originline->{ $property }))
				{
					$originline->{ $property } = $value;
				}
			}

			$line = $originline;
		}
 		if($object->element=='facture')$idvar = 'facid';
        else $idvar='id';
		if($line->special_code!=$this->module_number || $line->product_type!=9) {
			if ($object->statut == 0  && $createRight && !empty($conf->global->SUBTOTAL_ALLOW_DUPLICATE_LINE) && $object->element !== 'invoice_supplier')
            {
                if(!(TSubtotal::isModSubtotalLine($line)) && ( $line->fk_prev_id === null ) && !($action == "editline" && GETPOST('lineid', 'int') == $line->id)) {
                    echo '<a name="duplicate-'.$line->id.'" href="' . $_SERVER['PHP_SELF'] . '?' . $idvar . '=' . $object->id . '&action=duplicate&lineid=' . $line->id . '"><i class="fa fa-clone" aria-hidden="true"></i></a>';

                    ?>
                        <script type="text/javascript">
                            $(document).ready(function() {
                                $("a[name='duplicate-<?php echo $line->id; ?>']").prependTo($('#row-<?php echo $line->id; ?>').find('.linecoledit'));
                            });
                        </script>
                    <?php
                }

            }
			return 0;
		}
		else if (in_array('invoicecard',$contexts) || in_array('invoicesuppliercard',$contexts) || in_array('propalcard',$contexts) || in_array('supplier_proposalcard',$contexts) || in_array('ordercard',$contexts) || in_array('ordersuppliercard',$contexts) || in_array('invoicereccard',$contexts))
        {


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
			if($object->element == 'order_supplier') (float) DOL_VERSION < 7.0 ? $colspan = 3 : $colspan = 6;
			if($object->element == 'invoice_supplier') (float) DOL_VERSION < 7.0 ? $colspan = 4: $colspan = 7;
			if($object->element == 'supplier_proposal') (float) DOL_VERSION < 6.0 ? $colspan = 4 : $colspan = 3;
			if(!empty($conf->multicurrency->enabled) && ((float) DOL_VERSION < 8.0 || $object->multicurrency_code != $conf->currency)) {
				$colspan++; // Colonne PU Devise
			}
			if($object->element == 'commande' && $object->statut < 3 && !empty($conf->shippableorder->enabled)) $colspan++;

			$margins_hidden_by_module = empty($conf->affmarges->enabled) ? false : !($_SESSION['marginsdisplayed']);
			if(!empty($conf->margin->enabled) && !$margins_hidden_by_module) $colspan++;
			if(!empty($conf->global->DISPLAY_MARGIN_RATES) && !$margins_hidden_by_module) $colspan++;
			if(!empty($conf->global->DISPLAY_MARK_RATES) && !$margins_hidden_by_module) $colspan++;
			if($object->element == 'facture' && !empty($conf->global->INVOICE_USE_SITUATION) && $object->type == Facture::TYPE_SITUATION) $colspan++;
			if(!empty($conf->global->PRODUCT_USE_UNITS)) $colspan++;
			// Compatibility module showprice
			if(!empty($conf->showprice->enabled)) $colspan++;

			/* Titre */
			//var_dump($line);

			// HTML 5 data for js
            $data = $this->_getHtmlData($parameters, $object, $action, $hookmanager);


			?>
			<tr <?php echo $bc[$var]; $var=!$var; echo $data; ?> rel="subtotal" id="row-<?php echo $line->id ?>" style="<?php
					if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT))
					{
						if($line->qty==99) print 'background:#adadcf';
						else if($line->qty==98) print 'background:#ddddff;';
						else if($line->qty<=97 && $line->qty>=91) print 'background:#eeeeff;';
						else if($line->qty==1) print 'background:#adadcf;';
						else if($line->qty==2) print 'background:#ddddff;';
						else if($line->qty==50) print '';
						else print 'background:#eeeeff;';

						//A compléter si on veux plus de nuances de couleurs avec les niveau 4,5,6,7,8 et 9
					}
					else
					{
						if($line->qty==99) print 'background:#ddffdd';
						else if($line->qty==98) print 'background:#ddddff;';
						else if($line->qty==2) print 'background:#eeeeff; ';
						else if($line->qty==50) print '';
						else print 'background:#eeffee;' ;
					}

			?>;">

				<?php if(! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
				<td class="linecolnum"><?php echo $i + 1; ?></td>
				<?php } ?>

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
						if (!empty($line->fk_prev_id) && $line->fk_prev_id != null) $readonlyForSituation = 'readonly';

						if (!$isFreeText) echo '<input type="text" name="line-title" id-line="'.$line->id.'" value="'.$newlabel.'" size="80" '.$readonlyForSituation.'/>&nbsp;';

						if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT) && (TSubtotal::isTitle($line) || TSubtotal::isSubtotal($line)) )
						{
							$select = '<select name="subtotal_level">';
							for ($j=1; $j<10; $j++)
							{
								if (!empty($readonlyForSituation)) {
									if ($qty_displayed == $j) $select .= '<option selected="selected" value="'.$j.'">'.$langs->trans('Level').' '.$j.'</option>';
								} else $select .= '<option '.($qty_displayed == $j ? 'selected="selected"' : '').' value="'.$j.'">'.$langs->trans('Level').' '.$j.'</option>';
							}
							$select .= '</select>&nbsp;';

							echo $select;
						}


						echo '<div class="subtotal_underline" style="margin-left:24px; line-height: 25px;">';
                        echo '<div>';
                        echo '<input style="vertical-align:sub;"  type="checkbox" name="line-pagebreak" id="subtotal-pagebreak" value="8" '.(($line->info_bits > 0) ? 'checked="checked"' : '') .' />&nbsp;';
                        echo '<label for="subtotal-pagebreak">'.$langs->trans('AddBreakPageBefore').'</label>';
                        echo '</div>';

                        if (TSubtotal::isTitle($line))
                        {
                            $form = new Form($db);
                            echo '<div>';
                            echo '<label for="subtotal_tva_tx">'.$form->textwithpicto($langs->trans('subtotal_apply_default_tva'), $langs->trans('subtotal_apply_default_tva_help')).'</label>';
                            echo '<select id="subtotal_tva_tx" name="subtotal_tva_tx" class="flat"><option selected="selected" value="">-</option>';
                            if (empty($readonlyForSituation)) echo str_replace('selected', '', $form->load_tva('subtotal_tva_tx', '', $parameters['seller'], $parameters['buyer'], 0, 0, '', true));
                            echo '</select>';
                            echo '</div>';

                            if (!empty($conf->global->INVOICE_USE_SITUATION) && $object->element == 'facture' && $object->type == Facture::TYPE_SITUATION)
                            {
                                echo '<div>';
                                echo '<label for="subtotal_progress">'.$langs->trans('subtotal_apply_progress').'</label> <input id="subtotal_progress" name="subtotal_progress" value="" size="1" />%';
                                echo '</div>';
                            }
                            echo '<div>';
                            echo '<input style="vertical-align:sub;"  type="checkbox" name="line-showTotalHT" id="subtotal-showTotalHT" value="9" '.(($line->array_options['options_show_total_ht'] > 0) ? 'checked="checked"' : '') .' />&nbsp;';
                            echo '<label for="subtotal-showTotalHT">'.$langs->trans('ShowTotalHTOnSubtotalBlock').'</label>';
                            echo '</div>';

                            echo '<div>';
                            echo '<input style="vertical-align:sub;"  type="checkbox" name="line-showReduc" id="subtotal-showReduc" value="1" '.(($line->array_options['options_show_reduc'] > 0) ? 'checked="checked"' : '') .' />&nbsp;';
                            echo '<label for="subtotal-showReduc">'.$langs->trans('ShowReducOnSubtotalBlock').'</label>';
                            echo '</div>';
                        }
                        else if ($isFreeText) echo TSubtotal::getFreeTextHtml($line, (bool) $readonlyForSituation);
						echo '</div>';

						if (TSubtotal::isTitle($line))
						{
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

							$TKey = null;
							if ($line->element == 'propaldet') $TKey = explode(',', $conf->global->SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET);
							elseif ($line->element == 'commandedet') $TKey = explode(',', $conf->global->SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET);
							elseif ($line->element == 'facturedet') $TKey = explode(',', $conf->global->SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET);
							// TODO ajouter la partie fournisseur

							if (!empty($TKey))
							{
								$extrafields = new ExtraFields($this->db);
								$extrafields->fetch_name_optionals_label($object->table_element_line);
								foreach ($extrafields->attributes[$line->element]['param'] as $code => $val)
								{
									if (in_array($code, $TKey) && $extrafields->attributes[$line->element]['list'][$code] > 0)
									{
										echo '<div class="sub-'.$code.'">';
										echo '<label class="">'.$extrafields->attributes[$line->element]['label'][$code].'</label>';
										echo $extrafields->showInputField($code, $line->array_options['options_'.$code], '', '', 'subtotal_');
										echo '</div>';
									}
								}
							}
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


						 // Get display styles and apply them
						 $titleStyleItalic = strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'I') === false ? '' : ' font-style: italic;';
						 $titleStyleBold =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'B') === false ? '' : ' font-weight:bold;';
						 $titleStyleUnderline =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'U') === false ? '' : ' text-decoration: underline;';

						 if (empty($line->label)) {
							if ($line->qty >= 91 && $line->qty <= 99 && $conf->global->SUBTOTAL_USE_NEW_FORMAT) print  $line->description.' '.$this->getTitle($object, $line);
							else print  $line->description;
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
					echo '<td class="linecolht nowrap" align="right" style="font-weight:bold;" rel="subtotal_total">'.price($total_line).'</td>';
					if (!empty($conf->multicurrency->enabled) && ((float) DOL_VERSION < 8.0 || $object->multicurrency_code != $conf->currency)) {
						echo '<td class="linecoltotalht_currency">&nbsp;</td>';
					}
				} else {
					echo '<td class="linecolht movetitleblock">&nbsp;</td>';
					if (!empty($conf->multicurrency->enabled) && ((float) DOL_VERSION < 8.0 || $object->multicurrency_code != $conf->currency)) {
						echo '<td class="linecoltotalht_currency">&nbsp;</td>';
					}
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
							if(TSubtotal::isTitle($line) && ( $line->fk_prev_id === null )) echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=duplicate&lineid='.$line->id.'">'. img_picto($langs->trans('Duplicate'), 'duplicate@subtotal').'</a>';
						}

						if ($object->statut == 0  && $createRight && !empty($conf->global->SUBTOTAL_ALLOW_EDIT_BLOCK))
						{
							echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=editline&lineid='.$line->id.'#row-'.$line->id.'">'.img_edit().'</a>';
						}
					}

				}

				?>
			</td>

			<td align="center" class="nowrap linecoldelete">
				<?php

				if ($action != 'editline' && $action != 'selectlines') {
						if ($object->statut == 0  && $createRight && !empty($conf->global->SUBTOTAL_ALLOW_REMOVE_BLOCK))
						{

							if ($line->fk_prev_id === null)
							{
								echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=ask_deleteline&lineid='.$line->id.'">'.img_delete().'</a>';
							}

							if(TSubtotal::isTitle($line) && ($line->fk_prev_id === null) )
							{
								if ((float) DOL_VERSION >= 8.0) {
									$img_delete = img_delete($langs->trans('deleteWithAllLines'), ' style="color:#be3535 !important;" class="pictodelete pictodeleteallline"');
								} elseif ((float) DOL_VERSION >= 3.8) {
									$img_delete = img_picto($langs->trans('deleteWithAllLines'), 'delete_all.3.8@subtotal',' class="pictodelete" ');
								} else {
									$img_delete = img_picto($langs->trans('deleteWithAllLines'), 'delete_all@subtotal');
								}

								echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$idvar.'='.$object->id.'&action=ask_deleteallline&lineid='.$line->id.'">'.$img_delete.'</a>';
							}
						}
					}
				?>
			</td>

			<?php
			if ($object->statut == 0  && $createRight && !empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && TSubtotal::isTitle($line) && $action != 'editline')
			{
				echo '<td class="subtotal_nc">';
				echo '<input id="subtotal_nc-'.$line->id.'" class="subtotal_nc_chkbx" data-lineid="'.$line->id.'" type="checkbox" name="subtotal_nc" value="1" '.(!empty($line->array_options['options_subtotal_nc']) ? 'checked="checked"' : '').' />';
				echo '</td>';
			}

			if ($num > 1 && empty($conf->browser->phone)) { ?>
			<td align="center" class="linecolmove tdlineupdown">
			</td>
			<?php } else { ?>
			<td align="center"<?php echo ((empty($conf->browser->phone) && ($object->statut == 0  && $createRight ))?' class="tdlineupdown"':''); ?>></td>
			<?php } ?>


			<?php  if($action == 'selectlines'){ // dolibarr 8 ?>
			<td class="linecolcheck" align="center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php echo $i+1; ?>]" value="<?php echo $line->id; ?>" ></td>
			<?php } ?>

			</tr>
			<?php


			// Affichage des extrafields à la Dolibarr (car sinon non affiché sur les titres)
			if(TSubtotal::isTitle($line) && !empty($conf->global->SUBTOTAL_ALLOW_EXTRAFIELDS_ON_TITLE)) {

				require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

				// Extrafields
				$extrafieldsline = new ExtraFields($db);
				$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);

				$colspan+=3; $mode = 'view';
				if($action === 'editline' && $line->rowid == GETPOST('lineid', 'int')) $mode = 'edit';

				$ex_element = $line->element;
				$line->element = 'tr_extrafield_title '.$line->element; // Pour pouvoir manipuler ces tr
				print $line->showOptionals($extrafieldsline, $mode, array('style'=>' style="background:#eeffee;" ','colspan'=>$colspan));
				$isExtraSelected = false;
				foreach($line->array_options as $option) {
					if(!empty($option) && $option != "-1") {
						$isExtraSelected = true;
						break;
					}
				}

				if($mode === 'edit') {
					?>
					<script>
						$(document).ready(function(){

							var all_tr_extrafields = $("tr.tr_extrafield_title");
							<?php
							// Si un extrafield est rempli alors on affiche directement les extrafields
							if(!$isExtraSelected) {
								echo 'all_tr_extrafields.hide();';
								echo 'var trad = "'.$langs->trans('showExtrafields').'";';
								echo 'var extra = 0;';
							} else {
								echo 'all_tr_extrafields.show();';
								echo 'var trad = "'.$langs->trans('hideExtrafields').'";';
								echo 'var extra = 1;';
							}
							?>

							$("div .subtotal_underline").append(
									'<a id="printBlocExtrafields" onclick="return false;" href="#">' + trad + '</a>'
									+ '<input type="hidden" name="showBlockExtrafields" id="showBlockExtrafields" value="'+ extra +'" />');

							$(document).on('click', "#printBlocExtrafields", function() {
								var btnShowBlock = $("#showBlockExtrafields");
								var val = btnShowBlock.val();
								if(val == '0') {
									btnShowBlock.val('1');
									$("#printBlocExtrafields").html("<?php print $langs->trans('hideExtrafields'); ?>");
									$(all_tr_extrafields).show();
								} else {
									btnShowBlock.val('0');
									$("#printBlocExtrafields").html("<?php print $langs->trans('showExtrafields'); ?>");
									$(all_tr_extrafields).hide();
								}
							});
						});
					</script>
					<?php
				}
				$line->element = $ex_element;

			}

			return 1;

		}
		elseif(($object->element == 'commande' && in_array('ordershipmentcard', $contexts)) || (in_array('expeditioncard', $contexts) && $action == 'create'))
		{
			$colspan = 4;

			// HTML 5 data for js
			$data = $this->_getHtmlData($parameters, $object, $action, $hookmanager);
?>
			<tr <?php echo $bc[$var]; $var=!$var; echo $data; ?> rel="subtotal" id="row-<?php echo $line->id ?>" style="<?php
					if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT))
					{
						if($line->qty==99) print 'background:#adadcf';
						else if($line->qty==98) print 'background:#ddddff;';
						else if($line->qty<=97 && $line->qty>=91) print 'background:#eeeeff;';
						else if($line->qty==1) print 'background:#adadcf;';
						else if($line->qty==2) print 'background:#ddddff;';
						else if($line->qty==50) print '';
						else print 'background:#eeeeff;';

						//A compléter si on veux plus de nuances de couleurs avec les niveau 4,5,6,7,8 et 9
					}
					else
					{
						if($line->qty==99) print 'background:#ddffdd';
						else if($line->qty==98) print 'background:#ddddff;';
						else if($line->qty==2) print 'background:#eeeeff; ';
						else if($line->qty==50) print '';
						else print 'background:#eeffee;' ;
					}

			?>;">

				<td style="<?php TSubtotal::isFreeText($line) ? '' : 'font-weight:bold;'; ?>  <?php echo ($line->qty>90)?'text-align:right':'' ?> "><?php


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


						 // Get display styles and apply them
						 $titleStyleItalic = strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'I') === false ? '' : ' font-style: italic;';
						 $titleStyleBold =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'B') === false ? '' : ' font-weight:bold;';
						 $titleStyleUnderline =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'U') === false ? '' : ' text-decoration: underline;';

						 if (empty($line->label)) {
							if ($line->qty >= 91 && $line->qty <= 99 && $conf->global->SUBTOTAL_USE_NEW_FORMAT) print  $line->description.' '.$this->getTitle($object, $line);
							else print  $line->description;
						 }
						 else {

							if (! empty($conf->global->PRODUIT_DESC_IN_FORM) && !empty($line->description)) {
								print '<span class="subtotal_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" >'.$line->label.'</span><br><div class="subtotal_desc">'.dol_htmlentitiesbr($line->description).'</div>';
							}
							else{
								print '<span class="subtotal_label classfortooltip '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
							}

						 }
						//if($line->qty>90) print ' : ';
						if($line->info_bits > 0) echo img_picto($langs->trans('Pagebreak'), 'pagebreak@subtotal');

			?>
				</td>
				 <td colspan="<?php echo $colspan; ?>">
<?php
						if(in_array('expeditioncard', $contexts) && $action == 'create')
						{
							$fk_entrepot = GETPOST('entrepot_id', 'int');
?>

						<input type="hidden" name="idl<?php echo $i; ?>" value="<?php echo $line->id; ?>" />
						<input type="hidden" name="qtyasked<?php echo $i; ?>" value="<?php echo $line->qty; ?>" />
						<input type="hidden" name="qdelivered<?php echo $i; ?>" value="0" />
						<input type="hidden" name="qtyl<?php echo $i; ?>" value="<?php echo $line->qty; ?>" />
						<input type="hidden" name="entl<?php echo $i; ?>" value="<?php echo $fk_entrepot; ?>" />
<?php
						}
?>
					 </td>
			</tr>
<?php
			return 1;
		}
		elseif ($object->element == 'shipping' || $object->element == 'delivery')
		{
			global $form;

			$alreadysent = $parameters['alreadysent'];

			$shipment_static = new Expedition($db);
			$warehousestatic = new Entrepot($db);
			$extrafieldsline = new ExtraFields($db);
			$extralabelslines=$extrafieldsline->fetch_name_optionals_label($object->table_element_line);

			$colspan = 4;
			if($object->origin && $object->origin_id > 0) $colspan++;
			if(! empty($conf->stock->enabled)) $colspan++;
			if(! empty($conf->productbatch->enabled)) $colspan++;
			if($object->statut == 0) $colspan++;
			if($object->statut == 0 && empty($conf->global->SUBTOTAL_ALLOW_REMOVE_BLOCK)) $colspan++;

			if($object->element == 'delivery') $colspan = 2;

			print '<!-- origin line id = '.$line->origin_line_id.' -->'; // id of order line

			// HTML 5 data for js
			$data = $this->_getHtmlData($parameters, $object, $action, $hookmanager);
			?>
			<tr <?php echo $bc[$var]; $var=!$var; echo $data; ?> rel="subtotal" id="row-<?php echo $line->id ?>" style="<?php
					if (!empty($conf->global->SUBTOTAL_USE_NEW_FORMAT))
					{
						if($line->qty==99) print 'background:#adadcf';
						else if($line->qty==98) print 'background:#ddddff;';
						else if($line->qty<=97 && $line->qty>=91) print 'background:#eeeeff;';
						else if($line->qty==1) print 'background:#adadcf;';
						else if($line->qty==2) print 'background:#ddddff;';
						else if($line->qty==50) print '';
						else print 'background:#eeeeff;';

						//A compléter si on veux plus de nuances de couleurs avec les niveau 4,5,6,7,8 et 9
					}
					else
					{
						if($line->qty==99) print 'background:#ddffdd';
						else if($line->qty==98) print 'background:#ddddff;';
						else if($line->qty==2) print 'background:#eeeeff; ';
						else if($line->qty==50) print '';
						else print 'background:#eeffee;' ;
					}

			?>;">

			<?php
			// #
			if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
			{
				print '<td align="center">'.($i+1).'</td>';
			}
			?>

			<td style="<?php TSubtotal::isFreeText($line) ? '' : 'font-weight:bold;'; ?>  <?php echo ($line->qty>90)?'text-align:right':'' ?> "><?php


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


			// Get display styles and apply them
			$titleStyleItalic = strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'I') === false ? '' : ' font-style: italic;';
			$titleStyleBold =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'B') === false ? '' : ' font-weight:bold;';
			$titleStyleUnderline =  strpos($conf->global->SUBTOTAL_TITLE_STYLE, 'U') === false ? '' : ' text-decoration: underline;';

			if (empty($line->label)) {
				if ($line->qty >= 91 && $line->qty <= 99 && $conf->global->SUBTOTAL_USE_NEW_FORMAT) print  $line->description.' '.$this->getTitle($object, $line);
				else print  $line->description;
			}
			else {
				if (! empty($conf->global->PRODUIT_DESC_IN_FORM) && !empty($line->description)) {
					print '<span class="subtotal_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" >'.$line->label.'</span><br><div class="subtotal_desc">'.dol_htmlentitiesbr($line->description).'</div>';
				}
				else{
					print '<span class="subtotal_label classfortooltip '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
				}
			}
			//if($line->qty>90) print ' : ';
			if($line->info_bits > 0) echo img_picto($langs->trans('Pagebreak'), 'pagebreak@subtotal');

			?>
				</td>
				<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
			<?php

			if ($object->element == 'shipping' && $object->statut == 0 && ! empty($conf->global->SUBTOTAL_ALLOW_REMOVE_BLOCK))
			{
				print '<td class="linecoldelete nowrap" width="10">';
				$lineid = $line->id;
				if($line->element === 'commandedet') {
					foreach($object->lines as $shipmentLine) {
						if(!empty($shipmentLine->fk_origin_line) && $shipmentLine->fk_origin == 'orderline' && $shipmentLine->fk_origin_line == $line->id) {
							$lineid = $shipmentLine->id;
						}
					}
				}
				if ($line->fk_prev_id === null)
				{
					echo '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=deleteline&amp;lineid='.$lineid.'">'.img_delete().'</a>';
				}

				if(TSubtotal::isTitle($line) && ($line->fk_prev_id === null) )
				{
					if ((float) DOL_VERSION >= 8.0) {
						$img_delete = img_delete($langs->trans('deleteWithAllLines'), ' style="color:#be3535 !important;" class="pictodelete pictodeleteallline"');
					} elseif ((float) DOL_VERSION >= 3.8) {
						$img_delete = img_picto($langs->trans('deleteWithAllLines'), 'delete_all.3.8@subtotal',' class="pictodelete" ');
					} else {
						$img_delete = img_picto($langs->trans('deleteWithAllLines'), 'delete_all@subtotal');
					}

					echo '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=ask_deleteallline&amp;lineid='.$lineid.'">'.$img_delete.'</a>';
				}

				print '</td>';
			}

			print "</tr>";

			// Display lines extrafields
			if ($object->element == 'shipping' && ! empty($conf->global->SUBTOTAL_ALLOW_EXTRAFIELDS_ON_TITLE) && is_array($extralabelslines) && count($extralabelslines)>0) {
				$line = new ExpeditionLigne($db);
				$line->fetch_optionals($line->id);
				print '<tr class="oddeven">';
				print $line->showOptionals($extrafieldsline, 'view', array('style'=>$bc[$var], 'colspan'=>$colspan),$i);
			}

			return 1;
		}

		return 0;

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
			/** @var Commande $object */

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


	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $conf,$langs;

		if ($object->statut == 0 && !empty($conf->global->SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS) && $action != 'editline')
		{

		    if($object->element == 'invoice_supplier' || $object->element == 'order_supplier')
		    {
		        foreach ($object->lines as $line)
		        {
		            // fetch optionals attributes and labels
		            require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
		            $extrafields=new ExtraFields($this->db);
		            $extralabels=$extrafields->fetch_name_optionals_label($object->table_element_line,true);
		            $line->fetch_optionals($line->id,$extralabels);
		        }
		    }

			$TSubNc = array();
			foreach ($object->lines as &$l)
			{
				$TSubNc[$l->id] = (int) $l->array_options['options_subtotal_nc'];
			}

			$form = new Form($db);
			?>
			<script type="text/javascript">
				$(function() {
					var subtotal_TSubNc = <?php echo json_encode($TSubNc); ?>;
					$("#tablelines tr").each(function(i, item) {
						if ($(item).children('.subtotal_nc').length == 0)
						{
							var id = $(item).attr('id');

							if ((typeof id != 'undefined' && id.indexOf('row-') == 0) || $(item).hasClass('liste_titre'))
							{
								$(item).children('td:last-child').before('<td class="subtotal_nc"></td>');

								if ($(item).attr('rel') != 'subtotal' && typeof $(item).attr('id') != 'undefined')
								{
									var idSplit = $(item).attr('id').split('-');
									$(item).children('td.subtotal_nc').append($('<input type="checkbox" id="subtotal_nc-'+idSplit[1]+'" class="subtotal_nc_chkbx" data-lineid="'+idSplit[1]+'" value="1" '+(typeof subtotal_TSubNc[idSplit[1]] != 'undefined' && subtotal_TSubNc[idSplit[1]] == 1 ? 'checked="checked"' : '')+' />'));
								}
							}
							else
							{
								$(item).append('<td class="subtotal_nc"></td>');
							}
						}
					});

					$('#tablelines tr.liste_titre:first .subtotal_nc').html(<?php echo json_encode($form->textwithtooltip($langs->trans('subtotal_nc_title'), $langs->trans('subtotal_nc_title_help'))); ?>);

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

				});

			</script>
			<?php
		}

		$this->_ajax_block_order_js($object);

		return 0;
	}

	function afterPDFCreation($parameters, &$pdf, &$action, $hookmanager)
	{
		global $conf;

		$object = $parameters['object'];

		if ((!empty($conf->global->SUBTOTAL_PROPAL_ADD_RECAP) && $object->element == 'propal') || (!empty($conf->global->SUBTOTAL_COMMANDE_ADD_RECAP) && $object->element == 'commande') || (!empty($conf->global->SUBTOTAL_INVOICE_ADD_RECAP) && $object->element == 'facture'))
		{
			if (GETPOST('subtotal_add_recap', 'none')) {
				dol_include_once('/subtotal/class/subtotal.class.php');
				TSubtotal::addRecapPage($parameters, $pdf);
			}
		}

		return 0;
	}

	/** Overloading the getlinetotalremise function : replacing the parent's function with the one below
	 * @param      $parameters  array           meta datas of the hook (context, etc...)
	 * @param      $object      CommonObject    the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param      $action      string          current action (if set). Generally create or edit or null
	 * @param      $hookmanager HookManager     current hook manager
	 * @return     void
	 */
	function getlinetotalremise($parameters, &$object, &$action, $hookmanager)
	{
	    // Les lignes NC ne sont pas censées afficher de montant total de remise, nouveau hook en v11 dans pdf_sponge
	    if (! empty($object->lines[$parameters['i']]->array_options['options_subtotal_nc']))
	    {
            $this->resprints = '';
            return 1;
	    }

		return 0;
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
				var moveBlockCol= titleRow.find('td.linecolht');


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

/**
     * @param $parameters
     * @param $object
     * @param $action
     * @param $hookmanager
     */
	function handleExpeditionTitleAndTotal($parameters, &$object, &$action, $hookmanager){
        global $conf;
        //var_dump($parameters['line']);
	    dol_include_once('subtotal/class/subtotal.class.php');
        $currentcontext = explode(':', $parameters['context']);

	    if ( in_array('shippableorderlist',$currentcontext)) {

            //var_dump($parameters['line']);
            if(TSubtotal::isModSubtotalLine($parameters['line'])) {

                $confOld = $conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT;
                $conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT = 0;
                $res =  $parameters['shipping']->addline($parameters['TEnt_comm'][$object->order->id], $parameters['line']->id, $parameters['line']->qty);
                $conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT = $confOld;
            }

        }

    }

	/**
	 * Overloading the defineColumnField function
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonDocGenerator object      $pdfDoc         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function defineColumnField($parameters, &$pdfDoc, &$action, $hookmanager)
	{

		// If this model is column field compatible it will add info to change subtotal behavior
		$parameters['object']->subtotalPdfModelInfo->cols = $pdfDoc->cols;

		// HACK Pour passer les paramettres du model dans les hooks sans infos
		$parameters['object']->subtotalPdfModelInfo->marge_droite 	= $pdfDoc->marge_droite;
		$parameters['object']->subtotalPdfModelInfo->marge_gauche 	= $pdfDoc->marge_gauche;
		$parameters['object']->subtotalPdfModelInfo->page_largeur 	= $pdfDoc->page_largeur;
		$parameters['object']->subtotalPdfModelInfo->page_hauteur 	= $pdfDoc->page_hauteur;
		$parameters['object']->subtotalPdfModelInfo->format 		= $pdfDoc->format;
		$parameters['object']->subtotalPdfModelInfo->defaultTitlesFieldsStyle = $pdfDoc->subtotalPdfModelInfo->defaultTitlesFieldsStyle;
		$parameters['object']->subtotalPdfModelInfo->defaultContentsFieldsStyle = $pdfDoc->subtotalPdfModelInfo->defaultContentsFieldsStyle;

	}
}
