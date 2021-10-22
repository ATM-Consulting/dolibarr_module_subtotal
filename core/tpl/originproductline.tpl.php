<?php
	/* Copyright (C) 2010-2012	Regis Houssin	<regis.houssin@inodbox.com>
	/* Copyright (C) 2017		Charlie Benke	<charlie@patas-monkey.com>
	/* Copyright (C) 2021		Sylvain Legrans	<sylvain.legrand@infras.fr>
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
	if (empty($conf) || ! is_object($conf))
	{
		print "Error, template page can't be called as URL";
		exit;
	}
?>
<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<?php
	if ($this->tpl['subtotal'] != $this->tpl['id'] || !in_array($this->tpl['sub-type'], array('title', 'total')))
	{
		print '	<tr class = "oddeven'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'">
					<td>'.$this->tpl['label'].'</td>
					<td>'.$this->tpl['description'].'</td>
					<td class = "right">'.$this->tpl['vat_rate'].'</td>
					<td class = "right">'.$this->tpl['price'].'</td>';
		if (!empty($conf->multicurrency->enabled))
			print '	<td class = "right">'.$this->tpl['multicurrency_price'].'</td>';
		print '		<td class = "right">'.$this->tpl['qty'].'</td>';
		if ($conf->global->PRODUCT_USE_UNITS)
			print '	<td class = "left">'.$langs->trans($this->tpl['unit']).'</td>';
		print '		<td class = "right">'.$this->tpl['remise_percent'].'</td>';
	}
	else
	{
		$rowspan	= !empty($conf->multicurrency->enabled)	? 7 : 6;
		$rowspan	+= $conf->global->PRODUCT_USE_UNITS		? 1 : 0;
		print '	<tr class = "oddeven'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'" '.(!empty($this->tpl['sub-tr-style']) ? 'style = "'.$this->tpl['sub-tr-style'].'"' : '').'>
					<td colspan = "'.$rowspan.'" '.$this->tpl['sub-td-style'].'>'.$this->tpl['sublabel'].'</td>';
	}
	$selected	= !empty($selectedLines) && !in_array($this->tpl['id'], $selectedLines) ? 0 : 1;
	print '			<td class = "center">
						<input id = "cb'.$this->tpl['id'].'" class = "flat checkforselect" type = "checkbox" name = "toselect[]" value = "'.$this->tpl['id'].'"'.($selected ? ' checked = "checked"' : '').'>
					</td>
				</tr>'."\n";
?>
<!-- END PHP TEMPLATE originproductline.tpl.php -->