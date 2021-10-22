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

showParameters();

function showParameters() {
	global $db,$conf,$langs,$bc;

	$html=new Form($db);

	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_USE_NEW_FORMAT").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_USE_NEW_FORMAT');
	print '</td></tr>';

	if((float)DOL_VERSION>=3.8)
	{
	//	$var=!$var;	// InfraS change
		print '<tr class = "oddeven">';	// InfraS change
		print '<td>'.$langs->trans("SUBTOTAL_USE_NUMEROTATION").'</td>';
		print '<td align="center" width="20">&nbsp;</td>';
		print '<td align="center" width="300">';
		print ajax_constantonoff('SUBTOTAL_USE_NUMEROTATION');
		print '</td></tr>';
	}

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_ADD_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_ADD_BLOCK');
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_EDIT_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_EDIT_BLOCK');
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_REMOVE_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_REMOVE_BLOCK');
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_DUPLICATE_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_DUPLICATE_BLOCK');
	print '</td></tr>';

    $var=!$var;
    print '<tr class = "oddeven">';	// InfraS change
    print '<td>'.$langs->trans("SUBTOTAL_ALLOW_DUPLICATE_LINE").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('SUBTOTAL_ALLOW_DUPLICATE_LINE');
    print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE');
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK');
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$html->textwithpicto($langs->trans("SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE"), $langs->trans("SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE_info")).'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE">';
	print '<input type="text" name="SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE" value="'.$conf->global->SUBTOTAL_TEXT_FOR_TITLE_ORDETSTOINVOICE.'" />';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_TITLE_STYLE").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_SUBTOTAL_TITLE_STYLE">';
	print '<input type="text" placeholder="BU" name="SUBTOTAL_TITLE_STYLE" value="'.$conf->global->SUBTOTAL_TITLE_STYLE.'" />';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans("SUBTOTAL_SUBTOTAL_STYLE").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_SUBTOTAL_SUBTOTAL_STYLE">';
	print '<input type="text" placeholder="B" name="SUBTOTAL_SUBTOTAL_STYLE" value="'.$conf->global->SUBTOTAL_SUBTOTAL_STYLE.'" />';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans('SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE').'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE');
	print '</td></tr>';


	// TODO ajouter ici la partie fournisseur en ce basant sur les 3 conf du dessus


//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
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

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans('SUBTOTAL_KEEP_RECAP_FILE').'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_KEEP_RECAP_FILE');
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans('SUBTOTAL_PROPAL_ADD_RECAP').'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_PROPAL_ADD_RECAP');
	print '</td></tr>';

//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans('SUBTOTAL_COMMANDE_ADD_RECAP').'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_COMMANDE_ADD_RECAP');
	print '</td></tr>';


//	$var=!$var;	// InfraS change
	print '<tr class = "oddeven">';	// InfraS change
	print '<td>'.$langs->trans('SUBTOTAL_INVOICE_ADD_RECAP').'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_INVOICE_ADD_RECAP');
	print '</td></tr>';

	print '</table>';


    if ($conf->shippableorder->enabled) {
    print '<br />';

    print '<table width="100%" class="noborder" style="background-color: #fff;">';
    print '    <tr class="liste_titre">';
    print '        <td colspan="2">'.$langs->trans("addLineTitle_in_order_shippable_TITLE").'</td>';
    print '    </tr>';
    print '    <tr>';
    print '        <td>'.$langs->trans("addLineTitle_in_order_shippable").'</td>';

    print '        <td style="text-align: right;">';
    print '            <form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '                <input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';	// InfraS change
    print '                <input type="hidden" name="action" value="set_SUBTOTAL_SHIPPABLE_ORDER" />';
    echo $html->selectyesno("SUBTOTAL_SHIPPABLE_ORDER",$conf->global->SUBTOTAL_SHIPPABLE_ORDER,1);
	print '					<input type="submit" class="button" value="'.$langs->trans("Modify").'">';	// InfraS change
	print '				</form>';
	print '			</td>';
	print '		</tr>';
	}
	print '</table>';

}

dol_fiche_end(-1);

// Put here content of your page
// ...

/***************************************************
* LINKED OBJECT BLOCK
*
* Put here code to view linked object
****************************************************/
//$somethingshown=$asset->showLinkedObjectBlock();

// End of page
llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
$db->close();
?>