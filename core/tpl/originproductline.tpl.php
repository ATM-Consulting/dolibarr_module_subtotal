<?php
/* Copyright (C) 2010-2012  Regis Houssin  <regis.houssin@inodbox.com>
/* Copyright (C) 2017      Charlie Benke  <charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

$selected = 1;

// invoke the core template we are overriding, but inhibit direct output: instead, store it in a variable
ob_start();
include DOL_DOCUMENT_ROOT.'/core/tpl/originproductline.tpl.php';
$coreTplRow = ob_get_clean();

// If this is a subtotal line: we don't print the row from the core tpl: we override it completely because we don't want
// to show qty etc.
if ($this->tpl['subtotal'] ?? '' == $this->tpl['id'] && in_array($this->tpl['sub-type'] ?? '', array('title', 'total', 'freetext'))) {
	print '<tr class="oddeven'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'" '.(! empty($this->tpl['sub-tr-style']) ? 'style="'.$this->tpl['sub-tr-style'].'"' : '').'>';

	// We only use the overridden HTML to compute the colspan, but we don't print it
	$colspan = 1; // default
	$dom = new DOMDocument();

	// From Gemini: suppress libxml errors in case $coreTplRow contains invalid HTML
	libxml_use_internal_errors(true);
	$dom->loadHTML($coreTplRow);
	libxml_clear_errors();
	libxml_use_internal_errors(false);

	$xpath = new DOMXPath($dom);
	// Find <td> that are direct children of the first <tr>
	$tdNodes = $xpath->query('//tr[1]/td');
	if ($tdNodes && $tdNodes->length >= 2) {
		$colspan = $tdNodes->length - 1; // -1 to make room for the <td> containing the checkbox
	}
	print '<td colspan="'.$colspan.'" '.$this->tpl['sub-td-style'].'>'.$this->tpl['sublabel'].'</td>';

	if (! empty($selectedLines) && ! in_array($this->tpl['id'], $selectedLines)) {
		$selected = 0;
	}
	print '<td class="center">';
	print '<input id="cb'.$this->tpl['id'].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$this->tpl['id'].'"'.($selected ? ' checked="checked"' : '').'>';
	print '</td>';
	print '</tr>'."\n";
} else {
	print $coreTplRow;
}
?>
