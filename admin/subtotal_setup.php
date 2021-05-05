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
	if ($code == 'SUBTOTAL_TFIELD_TO_KEEP_WITH_NC') $value = implode(',', $value);

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
	global $db,$conf,$langs,$bc;

	$html=new Form($db);

	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SUBTOTAL_USE_LEVEL").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_USE_LEVEL');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_ADD_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_ADD_BLOCK');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_EDIT_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_EDIT_BLOCK');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_REMOVE_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_REMOVE_BLOCK');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_DUPLICATE_BLOCK").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_DUPLICATE_BLOCK');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
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

	$var=!$var;
	print '<tr '.$bc[$var].'>';
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

	$var=!$var;
	print '<tr '.$bc[$var].'>';
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

	print '</table><br />';




}

dol_fiche_end();

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
