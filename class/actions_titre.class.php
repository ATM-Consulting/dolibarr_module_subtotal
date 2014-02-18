<?php
class ActionsTitre
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */
      
 
      
    function formObjectOptions($parameters, &$object, &$action, $hookmanager) 
    {  
      	global $langs,$db;
		
		if (in_array('ordercard',explode(':',$parameters['context']))) 
        {
        	
		}
		elseif (in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	
    		if($action=='add_title_line') {
				$object->addline($langs->trans('title'), 0,1,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
    		}
			else if($action=='add_total_line') {
				$object->addline($langs->trans('total'), 0,99,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
    		}
			
			    	
			?><script type="text/javascript">
				$(document).ready(function() {
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a href="?facid=<?=$object->id ?>&action=add_title_line" class="butAction">Ajouter un titre</a></div>');
					$('div.fiche div.tabsAction').append('<div class="inline-block divButAction"><a href="?facid=<?=$object->id ?>&action=add_total_line" class="butAction">Ajouter un total</a></div>');
					
				});
				
			</script><?
			
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

	function printObjectLine ($parameters, &$object, &$action, $hookmanager){
		
		global $conf;
		
		$num = &$parameters['num'];
		$line = &$parameters['line'];
		$i = &$parameters['i'];
	
		if($line->special_code!=104777) {
			null;
		}	
		else if (in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        		if($line->qty==99) {
					/* Total */
					
						?>
					<tr class="drag drop" id="row-<?=$line->id ?>" style="background-color:#ccc; font-weight:bold;">
					<td colspan="6"><?=$line->description ?></td>
					<td align="center">
						<a href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&action=editline&lineid='.$line->id,1)?>">
							<?=img_edit() ?>		
						</a>
					</td>

					<td align="center">		
						<a href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&action=ask_deleteline&lineid='.$line->id,1)?>">
							<?=img_delete() ?>		
						</a>
					</td>

					<?php if ($num > 1 && empty($conf->browser->phone)) { ?>
					<td align="center" class="tdlineupdown">
						<?php if ($i > 0) { ?>
						<a class="lineupdown" href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&amp;action=up&amp;rowid='.$line->id,1) ?>">
						<?php echo img_up(); ?>
						</a>
						<?php } ?>
						<?php if ($i < $num-1) { ?>
						<a class="lineupdown" href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&amp;action=down&amp;rowid='.$line->id,1) ?>">
						<?php echo img_down(); ?>
						</a>
						<?php } ?>
					</td>
				    <?php } else { ?>
				    <td align="center"<?php echo (empty($conf->browser->phone)?' class="tdlineupdown"':''); ?>></td>
					<?php } ?>

					</tr>
					<?
					
					
				}
				else {
					
					/* Titre */
					//var_dump($line);
					?>
					<tr class="drag drop" id="row-<?=$line->id ?>" style="background-color:#fff; font-weight:bold;">
					<td colspan="6"><?=$line->description ?></td>
					<td align="center">
						<a href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&action=editline&lineid='.$line->id,1)?>">
							<?=img_edit() ?>		
						</a>
					</td>

					<td align="center">		
						<a href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&action=ask_deleteline&lineid='.$line->id,1)?>">
							<?=img_delete() ?>		
						</a>
					</td>

					<?php if ($num > 1 && empty($conf->browser->phone)) { ?>
					<td align="center" class="tdlineupdown">
						<?php if ($i > 0) { ?>
						<a class="lineupdown" href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&amp;action=up&amp;rowid='.$line->id,1) ?>">
						<?php echo img_up(); ?>
						</a>
						<?php } ?>
						<?php if ($i < $num-1) { ?>
						<a class="lineupdown" href="<?=dol_buildpath('/compta/facture.php?id='.$object->id.'&amp;action=down&amp;rowid='.$line->id,1) ?>">
						<?php echo img_down(); ?>
						</a>
						<?php } ?>
					</td>
				    <?php } else { ?>
				    <td align="center"<?php echo (empty($conf->browser->phone)?' class="tdlineupdown"':''); ?>></td>
					<?php } ?>

					</tr>
					<?
					
				}
			
		}
		
		return 0;

		return 0;
	}
}