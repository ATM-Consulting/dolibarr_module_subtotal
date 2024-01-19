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

		dolibarr_set_const($db, $name, $param,'chaine', 0, '', $conf->entity);

	}

}

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	$value = GETPOST($code, 'none');
	if (in_array($code, array(
		'SUBTOTAL_TFIELD_TO_KEEP_WITH_NC'
		, 'SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET'
		, 'SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET'
		, 'SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET'
        , 'SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS'
	))) $value = implode(',', $value);

	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		if ($code == 'SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS' && $value == 1) _createExtraComprisNonCompris();

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
    -1,
    "subtotal@subtotal"
);

$html=new Form($db);

$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

print '<tr class="oddeven">';
print '<td>';
print $html->textwithtooltip( $langs->trans("SUBTOTAL_USE_NEW_FORMAT") , $langs->trans("SUBTOTAL_USE_NEW_FORMAT_HELP"),2,1,img_help(1,''));
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_USE_NEW_FORMAT');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>';
print $langs->trans("CONCAT_TITLE_LABEL_IN_SUBTOTAL_LABEL");
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CONCAT_TITLE_LABEL_IN_SUBTOTAL_LABEL');
print '</td></tr>';

if((float)DOL_VERSION>=3.8)
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("SUBTOTAL_USE_NUMEROTATION").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_USE_NUMEROTATION');
	print '</td></tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ALLOW_ADD_BLOCK").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ALLOW_ADD_BLOCK');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ALLOW_EDIT_BLOCK").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ALLOW_EDIT_BLOCK');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ALLOW_REMOVE_BLOCK").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ALLOW_REMOVE_BLOCK');
print '</td></tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ALLOW_DUPLICATE_BLOCK").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ALLOW_DUPLICATE_BLOCK');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ALLOW_DUPLICATE_LINE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ALLOW_DUPLICATE_LINE');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$html->textwithpicto($langs->trans("SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE"), $langs->trans("SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE_info")).'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE">';
print '<input type="text" name="SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE" value="' . getDolGlobalString('SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE').'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_TITLE_STYLE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_TITLE_STYLE">';
print '<input type="text" placeholder="BU" name="SUBTOTAL_TITLE_STYLE" value="' . getDolGlobalString('SUBTOTAL_TITLE_STYLE').'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_SUBTOTAL_STYLE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_SUBTOTAL_STYLE">';
print '<input type="text" placeholder="B" name="SUBTOTAL_SUBTOTAL_STYLE" value="' . getDolGlobalString('SUBTOTAL_SUBTOTAL_STYLE').'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_TITLE_BACKGROUNDCOLOR").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_TITLE_BACKGROUNDCOLOR">';
print '<input type="color" name="SUBTOTAL_TITLE_BACKGROUNDCOLOR" value="'.(!getDolGlobalString('SUBTOTAL_TITLE_BACKGROUNDCOLOR')?'#ffffff': getDolGlobalInt('SUBTOTAL_TITLE_BACKGROUNDCOLOR') ).'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_SUBTOTAL_BACKGROUNDCOLOR").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_SUBTOTAL_BACKGROUNDCOLOR">';
print '<input type="color" name="SUBTOTAL_SUBTOTAL_BACKGROUNDCOLOR" value="'.(!getDolGlobalString('SUBTOTAL_SUBTOTAL_BACKGROUNDCOLOR')?'#ebebeb':getDolGlobalString('SUBTOTAL_SUBTOTAL_BACKGROUNDCOLOR') ).'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_ONE_LINE_IF_HIDE_INNERLINES", $langs->transnoentitiesnoconv('HideInnerLines')).'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ONE_LINE_IF_HIDE_INNERLINES');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_REPLACE_WITH_VAT_IF_HIDE_INNERLINES", $langs->transnoentitiesnoconv('HideInnerLines')).'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_REPLACE_WITH_VAT_IF_HIDE_INNERLINES');
print '</td></tr>';

if ((double) DOL_VERSION >= 4.0)
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS">';
	print $html->selectyesno("SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS", getDolGlobalString('SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS'),1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("SUBTOTAL_TFIELD_TO_KEEP_WITH_NC").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_SUBTOTAL_TFIELD_TO_KEEP_WITH_NC">';
	$TField = array(
		'pdf_getlineqty' => $langs->trans('Qty'),
		'pdf_getlinevatrate' => $langs->trans('VAT'),
		'pdf_getlineupexcltax' => $langs->trans('PriceUHT'),
		'pdf_getlinetotalexcltax' => $langs->trans('TotalHT'),
		'pdf_getlineunit' => $langs->trans('Unit'),
		'pdf_getlineremisepercent' => $langs->trans('Discount')
	);
	print $html->multiselectarray('SUBTOTAL_TFIELD_TO_KEEP_WITH_NC', $TField, explode(',', getDolGlobalString('SUBTOTAL_TFIELD_TO_KEEP_WITH_NC')), 0, 0, '', 0, 0, 'style="min-width:100px"');
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';
}

print '<tr class="oddeven">';
print '<td>'.$html->textwithpicto($langs->trans("SUBTOTAL_NONCOMPRIS_UPDATE_PA_HT"), $langs->trans("SUBTOTAL_NONCOMPRIS_UPDATE_PA_HT_info")).'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_NONCOMPRIS_UPDATE_PA_HT');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans('SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans('SUBTOTAL_ALLOW_EXTRAFIELDS_ON_TITLE').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_ALLOW_EXTRAFIELDS_ON_TITLE');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET">';
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label('propaldet');
print Form::multiselectarray("SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET", $extralabels, explode(',',  getDolGlobalString('SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET')));
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET">';
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label('commandedet');
print Form::multiselectarray("SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET", $extralabels, explode(',',  getDolGlobalString('SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET')));
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET">';
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label('facturedet');
print Form::multiselectarray("SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET", $extralabels, explode(',',  getDolGlobalString('SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET')));
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$langs->loadLangs(array('propal', 'orders', 'bills', 'supplier', 'supplier_proposal'));
$TElementType = array(
	'propal' => $langs->trans('Proposal'),
	'commande' => $langs->trans('Order'),
	'facture' => $langs->trans('Invoice'),
	'supplier_proposal' => $langs->trans('SupplierProposal'),
	'order_supplier' => $langs->trans('SupplierOrder'),
	'invoice_supplier' => $langs->trans('SupplierInvoice'),
);
$TSubtotalDefaultQtyOnElements = array();
if (getDolGlobalString('SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS')) {
	$TSubtotalDefaultQtyOnElements = explode(',',  getDolGlobalString('SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS'));
}
print '<tr class="oddeven">';
print '<td>'.$html->textwithpicto($langs->trans("SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS"), $langs->trans("SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS_info")).'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS">';
print Form::multiselectarray('SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS', $TElementType, $TSubtotalDefaultQtyOnElements);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// TODO ajouter ici la partie fournisseur en ce basant sur les 3 conf du dessus



print '<tr class="oddeven">';
print '<td>'.$langs->trans('NO_TITLE_SHOW_ON_EXPED_GENERATION').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('NO_TITLE_SHOW_ON_EXPED_GENERATION');
print '</td></tr>';
print '</table><br />';



$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("RecapGeneration").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans('SUBTOTAL_KEEP_RECAP_FILE').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_KEEP_RECAP_FILE');
print '</td></tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans('SUBTOTAL_PROPAL_ADD_RECAP').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_PROPAL_ADD_RECAP');
print '</td></tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans('SUBTOTAL_COMMANDE_ADD_RECAP').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_COMMANDE_ADD_RECAP');
print '</td></tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans('SUBTOTAL_INVOICE_ADD_RECAP').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('SUBTOTAL_INVOICE_ADD_RECAP');
print '</td></tr>';

print '</table>';

?>
<br />

<table width="100%" class="noborder" style="background-color: #fff;">
	<tr class="liste_titre">
		<td colspan="2">Paramètrage de l'option "Cacher le prix des lignes des ensembles"</td>
	</tr>

<?php
print '<tr class="oddeven" >';
print '<td>'.$langs->trans('SUBTOTAL_HIDE_PRICE_DEFAULT_CHECKED').'</td>';
print '<td align="center" >';
print ajax_constantonoff('SUBTOTAL_HIDE_PRICE_DEFAULT_CHECKED');
print '</td></tr>';
?>

	<tr>
		<td>Afficher la quantité sur les lignes de produit</td>
		<td style="text-align: right;">
			<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
				<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken'] ?>">
				<input type="hidden" name="action" value="set_SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY" />
				<?php echo $html->selectyesno("SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY", getDolGlobalString('SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY'),1); ?>
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
				<?php echo $html->selectyesno("SUBTOTAL_HIDE_DOCUMENT_TOTAL",getDolGlobalString('SUBTOTAL_HIDE_DOCUMENT_TOTAL'),1); ?>
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
					<?php echo $html->selectyesno("SUBTOTAL_SHOW_QTY_ON_TITLES",getDolGlobalString('SUBTOTAL_SHOW_QTY_ON_TITLES'),1); ?>
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
					<?php echo $html->selectyesno("SUBTOTAL_ONLY_HIDE_SUBPRODUCTS_PRICES",getDolGlobalString('SUBTOTAL_ONLY_HIDE_SUBPRODUCTS_PRICES'),1); ?>
					<input type="submit" class="button" value="<?php echo $langs->trans("Modify") ?>">
				</form>
			</td>
		</tr>
	<?php } ?>
</table>



<?php if ($conf->shippableorder->enabled) { ?>
<br />

<table width="100%" class="noborder" style="background-color: #fff;">
	<tr class="liste_titre">
		<td colspan="2"><?= $langs->trans("addLineTitle_in_order_shippable_TITLE") ?> </td>
	</tr>
	<tr>
		<td> <?php echo $langs->trans("addLineTitle_in_order_shippable") ?> </td>

		<td style="text-align: right;">
			<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
				<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken'] ?>">
				<input type="hidden" name="action" value="set_SUBTOTAL_SHIPPABLE_ORDER" />
				<?php echo $html->selectyesno("SUBTOTAL_SHIPPABLE_ORDER",getDolGlobalString('SUBTOTAL_SHIPPABLE_ORDER'),1); ?>
					<input type="submit" class="button" value="<?php echo $langs->trans("Modify") ?>">
				</form>
			</td>
		</tr>
	<?php } ?>
</table>

<br /><br />
<?php


dol_fiche_end(-1);


/***************************************************
* LINKED OBJECT BLOCK
*
* Put here code to view linked object
****************************************************/
//$somethingshown=$asset->showLinkedObjectBlock();

// End of page
llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
$db->close();
