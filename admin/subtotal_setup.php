<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2014 ATM Consulting <contact@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *		\version    $Id: skeleton_page.php,v 1.19 2011/07/31 22:21:57 eldy Exp $
 *		\author		Put author name here
 *		\remarks	Put here some comments
 */
// Change this following line to use the correct relative path (../, ../../, etc)
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/subtotal.lib.php';

$langs->load("subtotal@subtotal");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

if($action=='save') {
	
	foreach($_REQUEST['TDivers'] as $name=>$param) {
		
		dolibarr_set_const($db, $name, $param);
		
	}
	
}

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}


/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/



llxHeader('','Gestion de sous-total, à propos','');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre('Gestion de subtotal',$linkback,'setup');

// Configuration header
$head = subtotalAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104777Name"),
    0,
    "subtotal@subtotal"
);

showParameters();

function showParameters() {
	global $db,$conf,$langs;
	
	$html=new Form($db);
	
	
	?><form action="<?php echo $_SERVER['PHP_SELF'] ?>" name="form1" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="action" value="save" />
	<table width="100%" class="noborder" style="background-color: #fff;">
		<tr class="liste_titre">
			<td colspan="2">Paramètres</td>
		</tr>
		
		<tr>
			<td><?php echo $langs->trans('SUBTOTAL_MANAGE_SUBSUBTOTAL') ?></td><td><?php
			
				if($conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL==0) {
					
					 ?><a href="?action=save&TDivers[SUBTOTAL_MANAGE_SUBSUBTOTAL]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
					
				}
				else {
					 ?><a href="?action=save&TDivers[SUBTOTAL_MANAGE_SUBSUBTOTAL]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
					
				}
			
			?></td>				
		</tr>
		
		<tr class="pair">
			<td><?php echo $langs->trans('SUBTOTAL_USE_NEW_FORMAT') ?></td><td><?php
			
				if(empty($conf->global->SUBTOTAL_USE_NEW_FORMAT)) {
					
					 ?><a href="?action=save&TDivers[SUBTOTAL_USE_NEW_FORMAT]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
					
				}
				else {
					 ?><a href="?action=save&TDivers[SUBTOTAL_USE_NEW_FORMAT]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
					
				}
			
			?></td>				
		</tr>
<?php
	if((float)DOL_VERSION>=3.8) {
?>		<tr class="pair">
			<td><?php echo $langs->trans('SUBTOTAL_USE_NUMEROTATION') ?></td><td><?php
			
				if(empty($conf->global->SUBTOTAL_USE_NUMEROTATION)) {
					
					 ?><a href="?action=save&TDivers[SUBTOTAL_USE_NUMEROTATION]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
					
				}
				else {
					 ?><a href="?action=save&TDivers[SUBTOTAL_USE_NUMEROTATION]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
					
				}
			
			?></td>				
		</tr>
<?php
}
?>
	</table>
	</form>
	
	<br />
		
	<table width="100%" class="noborder" style="background-color: #fff;">
		<tr class="liste_titre">
			<td colspan="2">Paramètrage de l'option "Cacher le prix des lignes des ensembles"</td>
		</tr>
		
		<tr>
			<td>Afficher la quantité sur les lignes de produit</td>
			<td style="text-align: right;">
				<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
					<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken'] ?>">
					<input type="hidden" name="action" value="set_SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY" />
					<?php echo $html->selectyesno("SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY",$conf->global->SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY,1); ?>
					<input type="submit" class="button" value="<?php echo $langs->trans("Modify") ?>">
				</form>
			</td>				
		</tr>
		
		<tr class="pair">
			<td>Masquer les totaux</td>
			<td style="text-align: right;">
				<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
					<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken'] ?>">
					<input type="hidden" name="action" value="set_SUBTOTAL_HIDE_DOCUMENT_TOTAL" />
					<?php echo $html->selectyesno("SUBTOTAL_HIDE_DOCUMENT_TOTAL",$conf->global->SUBTOTAL_HIDE_DOCUMENT_TOTAL,1); ?>
					<input type="submit" class="button" value="<?php echo $langs->trans("Modify") ?>">
				</form>
			</td>				
		</tr>
		
		<?php if ($conf->clilacevenements->enabled) { ?>
			<tr>
				<td>Afficher la quantité sur les lignes de sous-total (uniquement dans le cas d'un produit virtuel ajouté)</td>
				<td style="text-align: right;">
					<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
						<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken'] ?>">
						<input type="hidden" name="action" value="set_SUBTOTAL_SHOW_QTY_ON_TITLES" />
						<?php echo $html->selectyesno("SUBTOTAL_SHOW_QTY_ON_TITLES",$conf->global->SUBTOTAL_SHOW_QTY_ON_TITLES,1); ?>
						<input type="submit" class="button" value="<?php echo $langs->trans("Modify") ?>">
					</form>
				</td>				
			</tr>
			
			<tr class="pair">
				<td>Masquer uniquement les prix pour les produits se trouvant dans un ensemble</td>
				<td style="text-align: right;">
					<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
						<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken'] ?>">
						<input type="hidden" name="action" value="set_SUBTOTAL_ONLY_HIDE_SUBPRODUCTS_PRICES" />
						<?php echo $html->selectyesno("SUBTOTAL_ONLY_HIDE_SUBPRODUCTS_PRICES",$conf->global->SUBTOTAL_ONLY_HIDE_SUBPRODUCTS_PRICES,1); ?>
						<input type="submit" class="button" value="<?php echo $langs->trans("Modify") ?>">
					</form>
				</td>				
			</tr>
		<?php } ?>	
	</table>
	
	<br /><br />
	<?php
}

// Put here content of your page
// ...

/***************************************************
* LINKED OBJECT BLOCK
*
* Put here code to view linked object
****************************************************/
//$somethingshown=$asset->showLinkedObjectBlock();

// End of page
$db->close();
llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
