<?php
class ActionsSubtotal
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
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
				
				
				if($action=='add_title_line' || $action=='add_total_line') {
					
					if($action=='add_title_line') {
						$title = $langs->trans('title');
						$qty = 1;
					}
					else {
						$title = $langs->trans('SubTotal');
						$qty = 99;
					}
					
	    		
					if( (float)DOL_VERSION <= 3.4 ) {
						if($object->element=='facture') $object->addline($object->id, $title, 0,$qty,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, $this->module_number);
						else if($object->element=='propal') $object->addline($object->id,$title, 0,$qty,0,0,0,0,0,'HT',0,0,9,-1, $this->module_number);
						else if($object->element=='commande') $object->addline($object->id,$title, 0,$qty,0,0,0,0,0,0,0,'HT',0,'','',9,-1, $this->module_number);
						
					}
					else {
						if($object->element=='facture') $object->addline($title, 0,$qty,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, $this->module_number);
						else if($object->element=='propal') $object->addline($title, 0,$qty,0,0,0,0,0,'HT',0,0,9,-1, $this->module_number);
						else if($object->element=='commande') $object->addline($title, 0,$qty,0,0,0,0,0,0,0,'HT',0,'','',9,-1, $this->module_number);
												
					}
				}
				
				    	
				?><script type="text/javascript">
					$(document).ready(function() {
						$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_title_line" href="javascript:;" class="butAction"><?= $langs->trans('AddTitle' )?></a></div>');
						$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a id="add_total_line" href="javascript:;" class="butAction"><?= $langs->trans('AddSubTotal')?></a></div>');
						
						
						$('#add_title_line').click(function() {
							
							$.get('?<?=$idvar ?>=<?=$object->id ?>&action=add_title_line', function() {
								document.location.href='?<?=$idvar ?>=<?=$object->id ?>';
							});
							
						});
						
						$('#add_total_line').click(function() {
							
							$.get('?<?=$idvar ?>=<?=$object->id ?>&action=add_total_line', function() {
								document.location.href='?<?=$idvar ?>=<?=$object->id ?>';
							});
							
						});
						
					});
					
				</script><?php
			}
		}
			 
			 
			 
		
		return 0;
	}
     
    function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
		
    	if (in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	
        }
		
        return 0;
    }

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		
	}

	function getTotalLineFromObject(&$object, &$line) {
		
		$rang = $line->rang;
		
		$total = 0;
		foreach($object->lines as $l) {
			
			if($l->rang>=$rang) {
				return $total;
			} 
			else if($l->special_code==$this->module_number && $l->qty!=99) {
				$total = 0;
			}
			else {
				$total +=$l->total_ht;	
			}
			
			
		}
		
		
		return $total;
	}

	function pdf_writelinedesc($parameters=false, &$object, &$action='')
	{
		
		foreach($parameters as $key=>$value) {
			${$key} = $value;
		}
			
		if ($object->lines[$i]->product_type == 9)
		{
			
 			if($object->lines[$i]->special_code == $this->module_number) {
				$line = &$object->lines[$i]; 	
				
				if($line->label=='') {
					$label = $line->desc;
					$description='';
				}
				else {
					$label = $line->label;
					$description=$line->desc;
				}
				
				if($line->qty==99) {
					$pdf->SetXY ($posx, $posy-1);
					$pdf->SetFillColor(230, 230, 230);
					$pdf->MultiCell(200-$posx, $h+2.5, '', 0, '', 1);
					
					$pdf->SetXY ($posx, $posy);
					$pdf->SetFont('', 'B', 9);
					$pdf->MultiCell($w, $h-2, $outputlangs->convToOutputCharset($label).' ', 0, 'R');
					
				}	
				else{
					
					$pdf->SetXY ($posx, $posy);
					$pdf->SetFont('', 'BU', 9);
					$pdf->MultiCell($w, $h-2, $outputlangs->convToOutputCharset($label), 0, 'L');
					
					if($description) {
						$posy = $pdf->GetY();
						$pdf->SetXY ($posx, $posy);
						$pdf->SetFont('', '', 8);
						$pdf->MultiCell($w, $h-2, $outputlangs->convToOutputCharset($description), 0, 'L');
						
						
					}
					
				}
	
				if($line->qty==99) {
					
					$total = $this->getTotalLineFromObject($object, $line);
					
					$pdf->SetFont('', 'B', 9);
					$pdf->SetXY($pdf->postotalht, $posy);
					$pdf->MultiCell($pdf->page_largeur-$pdf->marge_droite-$pdf->postotalht, 3, price($total), 0, 'R', 0);
					
				}
	
	
				$nexy = $pdf->GetY();
	
 				
 			}
			
			
		}
		else
		{
			$labelproductservice='- '.pdf_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline);
			$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1);
		}


		return 1;
	}
	
	

	function printObjectLine ($parameters, &$object, &$action, $hookmanager){
		
		global $conf,$langs,$user;
		
		$num = &$parameters['num'];
		$line = &$parameters['line'];
		$i = &$parameters['i'];

		$contexts = explode(':',$parameters['context']);
	
		if($line->special_code!=$this->module_number) {
			null;
		}	
		else if (in_array('invoicecard',$contexts) || in_array('propalcard',$contexts) || in_array('ordercard',$contexts)) 
        {
        	
			if($object->element=='facture')$idvar = 'facid';
			else $idvar='id';
					
					if($action=='savelinetitle' && $_POST['lineid']===$line->id) {
						
						$description = ($line->qty==99) ? '' : $_POST['linedescription'];
						
						if($object->element=='facture') $object->updateline($line->id,$description, 0,$line->qty,0,'','',0,0,0,'HT',0,9,0,0,null,0,$_POST['linetitle'], $this->module_number);
						else if($object->element=='propal') $object->updateline($line->id, 0,$line->qty,0,0,0,0, $description ,'HT',0,$this->module_number,0,0,0,0,$_POST['linetitle'],9);
						else if($object->element=='commande') $object->updateline($line->id,$description, 0,$line->qty,0,0,0,0,'HT',0,'','',9,0,0,null,0,$_POST['linetitle'], $this->module_number);
						
					}
					else if($action=='editlinetitle') {
						?>
						<script type="text/javascript">
							$(document).ready(function() {
								$('#addproduct').submit(function () {
									$('input[name=saveEditlinetitle]').click();
									return false;
								}) ;
							});
							
						</script>
						<?php
					}
					else {
						if( strpos($conf->global->MAIN_VERSION_LAST_INSTALL,'3.4')!==false ) {
							
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
					}
					
					/* Titre */
					//var_dump($line);
					?>
					<tr class="drag drop" rel="subtotal" id="row-<?=$line->id ?>" style="background-color:<?=   ($line->qty==99)?'#ddffdd':'#fff' ?>;">
					<td colspan="5" style="font-weight:bold;  <?=($line->qty==99)?'text-align:right':' font-style: italic;' ?> "><?
					
							if($action=='editlinetitle' && $_REQUEST['lineid']===$line->id ) {
								
								if($line->qty!=99) print img_picto('', 'subtotal@subtotal');
								else {
									if($line->label=='')$line->label = $line->description;
								}
								
								?>
								<input type="text" name="line-title" id-line="<?=$line->id ?>" value="<?=$line->label ?>" size="80" /><br />
								<?php
								
								if($line->qty!=99) {
									?>
									<textarea name="line-description" id-line="<?=$line->id ?>" cols="70" rows="2" /><?=$line->description ?></textarea>
									<?php
								}
								
							}
							else {
								
							     if($line->qty!=99) print img_picto('', 'subtotal@subtotal');
								
								 if (empty($line->label)) {
								 	print  $line->description;
								 } 
								 else {
								 	print '<span class="classfortooltip" title="'.$line->description.'">'.$line->label.'</span>';
								 } 
								
								 if($line->qty==99) { print ' : '; }
								 
								
							}
					 ?></td><?php
					  if (! empty($conf->margin->enabled) && empty($user->societe_id)) {
						 ?><td align="right" class="nowrap">&nbsp;</td>
					  	<?php if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous) {?>
					  	  <td align="right" class="nowrap">&nbsp;</td>
					  	<?php
					  }
					  if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous) {?>
					  	  <td align="right" class="nowrap">&nbsp;</td>
					  <?php } } ?>
					 
					  <?php	
						
							 if($line->qty==99) {
							/* Total */
								$total_line = $this->getTotalLineFromObject($object, $line);
								?>
								<td align="right" style="font-weight:bold;" rel="subtotal_total"><?=price($total_line) ?></td>
								<?php
								
							}
							 else {
							 	
								?>
								<td>&nbsp;</td>
								<?php
							 }	
						?>
					
					<td align="center">
						<?php
							if($action=='editlinetitle' && $_REQUEST['lineid']==$line->id ) {
								?>
								<input class="button" type="button" name="saveEditlinetitle" value="<?=$langs->trans('Save') ?>" />
								<script type="text/javascript">
									$(document).ready(function() {
										$('input[name=saveEditlinetitle]').click(function () {
											
											$.post("<?='?'.$idvar.'='.$object->id ?>",{
													action:'savelinetitle'
													,lineid:<?=$line->id ?>
													,linetitle:$('input[name=line-title]').val()
													,linedescription:$('textarea[name=line-description]').val()
											}
											,function() {
												document.location.href="<?='?'.$idvar.'='.$object->id ?>";	
											});
											
										});
										
										$('input[name=cancelEditlinetitle]').click(function () {
											document.location.href="<?='?'.$idvar.'='.$object->id ?>";
										});
										
									});
									
								</script>
								<?php
							}
							else{
								
								if ($object->statut == 0  && $user->rights->{$object->element}->creer) {
								
								?>
									<a href="<?='?'.$idvar.'='.$object->id.'&action=editlinetitle&lineid='.$line->id ?>">
										<?=img_edit() ?>		
									</a>
								<?php
								
								}								
							}
						?>
					</td>

					<td align="center">	
						<?php
							if($action=='editlinetitle' && $_REQUEST['lineid']===$line->id ) {
								?>
								<input class="button" type="button" name="cancelEditlinetitle" value="<?=$langs->trans('Cancel') ?>" />
								<?php
							}
							else{
								if ($object->statut == 0  && $user->rights->{$object->element}->creer) {
								
								?>
									<a href="<?='?'.$idvar.'='.$object->id.'&action=ask_deleteline&lineid='.$line->id ?>">
										<?=img_delete() ?>		
									</a>
								<?php								
								
								}
							}
						?>	
						
					</td>

					<?php if ($num > 1 && empty($conf->browser->phone)) { ?>
					<td align="center" class="tdlineupdown">
						<?php if ($i > 0 && ($object->statut == 0  && $user->rights->{$object->element}->creer)) { ?>
						<a class="lineupdown" href="<?='?'.$idvar.'='.$object->id.'&amp;action=up&amp;rowid='.$line->id ?>">
						<?php echo img_up(); ?>
						</a>
						<?php } ?>
						<?php if ($i < $num-1 && ($object->statut == 0  && $user->rights->{$object->element}->creer)) { ?>
						<a class="lineupdown" href="<?='?'.$idvar.'='.$object->id.'&amp;action=down&amp;rowid='.$line->id ?>">
						<?php echo img_down(); ?>
						</a>
						<?php } ?>
					</td>
				    <?php } else { ?>
				    <td align="center"<?php echo ((empty($conf->browser->phone) && ($object->statut == 0  && $user->rights->{$object->element}->creer))?' class="tdlineupdown"':''); ?>></td>
					<?php } ?>

					</tr>
					<?php
					
					
				
			
		}
		
		return 0;

	}
}