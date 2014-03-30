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
require '../config.php';
// Change this following line to use the correct relative path from htdocs (do not remove DOL_DOCUMENT_ROOT)
dol_include_once('/core/lib/admin.lib.php');

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


/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/



llxHeader('','Gestion de sous-total, à propos','');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre('Gestion de subtotal',$linkback,'setup');

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
			<td>Activer la gestion des sous-sous-totaux</td><td><?php
			
				if($conf->global->SUBTOTAL_MANAGE_SUBSUBTOTAL==0) {
					
					 ?><a href="?action=save&TDivers[SUBTOTAL_MANAGE_SUBSUBTOTAL]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
					
				}
				else {
					 ?><a href="?action=save&TDivers[SUBTOTAL_MANAGE_SUBSUBTOTAL]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
					
				}
			
			?></td>				
		</tr>

		
	</table>
	</form>
	
	
	<br /><br />
	<?php
}
?>

<table width="100%" class="noborder">
	<tr class="liste_titre">
		<td>A propos</td>
		<td align="center">&nbsp;</td>
		</tr>
		<tr class="impair">
			<td valign="top">Module développé par </td>
			<td align="center">
				<a href="http://www.atm-consulting.fr/" target="_blank">ATM Consulting</a>
			</td>
		</td>
	</tr>
</table>
<?php

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
