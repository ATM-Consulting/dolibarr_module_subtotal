<?php

namespace subtotal;

/* Copyright (C) 2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *	\file       htdocs/core/class/commonhookactions.class.php
 *	\ingroup    core
 *	\brief      File of parent class of all other hook actions classes
 */


if (file_exists(DOL_DOCUMENT_ROOT . '/core/class/commonhookactions.class.php')){

	require_once DOL_DOCUMENT_ROOT . '/core/class/commonhookactions.class.php';
	/**
	 *	Parent class of all other hook actions classes
	 */
	abstract class RetroCompatCommonHookActions extends \CommonHookActions
	{

	}
}else{
	/**
	 *	Parent class of all other hook actions classes
	 */
	abstract class RetroCompatCommonHookActions
	{
		/**
		 * @var string	String of results.
		 */
		public $resprints;

		/**
		 * @var array 	Array of results.
		 */
		public $results = array();
	}

}



