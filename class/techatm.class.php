<?php
/* Copyright (C) 2025 ATM Consulting <support@atm-consulting.fr>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.

SPDX-License-Identifier: GPL-3.0-or-later
This file is part of Dolibarr module Subtotal
*/

namespace subtotal;

/**
 * Class TechATM
 * Class utilisée pour des mises à jours technique du module
 * @package subtotal
 */
class TechATM
{

	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var int  a http_response_header parsed reponse code
	 */
	public $reponse_code;

	/**
	 * @var array  the last call $http_response_header
	 */

	public $http_response_header;

	/**
	 * @var array  the last call $http_response_header parsed <- for most common usage (see self::parseHeaders() function)
	 */
	public $TResponseHeader;

	/**
	 * @var mixed Data returned by the last call
	 */
	public $data;

	/**
	 * url vers le domaine des appels techniques
	 */
	const ATM_TECH_URL = 'https://tech.atm-consulting.fr';


	/**
	 * il s'agit de la version de ce cette class
	 * Si jamais on change la façon de faire
	 * Au moins on peut gérer des redescentes d'info différentes ex json au lieu de html simple
	 */
	const CALL_VERSION = 1.0;

	/**
	 *  Constructor
	 *
	 *  @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Get the HTML content of the About page for a module.
	 *
	 * @param DolibarrModules $moduleDescriptor Module descriptor object
	 * @param bool            $useCache         Use local cache if available
	 * @return string                          HTML content of the about page
	 */
	public function getAboutPage($moduleDescriptor, $useCache = true)
	{
		global $langs;

		$url = self::ATM_TECH_URL.'/modules/modules-page-about.php';
		$url.= '?module='.$moduleDescriptor->name;
		$url.= '&id='.$moduleDescriptor->numero;
		$url.= '&version='.$moduleDescriptor->version;
		$url.= '&langs='.$langs->defaultlang;
		$url.= '&callv='.self::CALL_VERSION;

		$cachePath = DOL_DATA_ROOT . "/modules-atm/temp/about_page";
		$cacheFileName = dol_sanitizeFileName($moduleDescriptor->name.'_'.$langs->defaultlang).'.html';
		$cacheFilePath = $cachePath.'/'.$cacheFileName;

		if ($useCache && is_readable($cacheFilePath)) {
			$lastChange = filemtime($cacheFilePath);
			if ($lastChange > time() - 86400) {
				$content = @file_get_contents($cacheFilePath);
				if ($content !== false) {
					return $content;
				}
			}
		}

		$content = $this->getContents($url);

		if (!$content) {
			$content = '';
			// About page goes here
			$content.= '<div style="float: left;"><img src="../img/Dolibarr_Preferred_Partner_logo.png" /></div>';
			$content.= '<div>'.$langs->trans('ATMAbout').'</div>';
			$content.= '<hr/><center>';
			$content.= '<a href="http://www.atm-consulting.fr" target="_blank"><img src="../img/ATM_logo.jpg" /></a>';
			$content.= '</center>';
		}

		if ($useCache) {
			if (!is_dir($cachePath)) {
				$res = dol_mkdir($cachePath, DOL_DATA_ROOT);
			} else {
				$res = true;
			}

			if ($res) {
				$comment = '<!-- Generated from '.$url.' -->'."\r\n";

				file_put_contents(
					$cacheFilePath,
					$comment.$content
				);
			}
		}

		return $content;
	}

	/**
	 * Get URL to the documentation page of a module.
	 *
	 * @param string $moduleTechMane Name of the technical module
	 * @return string                URL to the module documentation
	 */
	public static function getModuleDocUrl($moduleTechMane)
	{
		$url = self::ATM_TECH_URL.'/modules/doc-redirect.php';
		$url.= '?module='.$moduleTechMane;
		return $url;
	}

	/**
	 * Build the URL to fetch the latest version of a module.
	 *
	 * @param DolibarrModules $moduleDescriptor Module descriptor object
	 * @return string                           URL to retrieve latest module version info
	 */
	public static function getLastModuleVersionUrl($moduleDescriptor)
	{
		$url = self::ATM_TECH_URL.'/modules/modules-last-version.php';
		$url.= '?module='.$moduleDescriptor->name;
		$url.= '&number='.$moduleDescriptor->numero;
		$url.= '&version='.$moduleDescriptor->version;
		$url.= '&dolversion='.DOL_VERSION;
		$url.= '&callv='.self::CALL_VERSION;
		return $url;
	}


	/**
	 * @param string $url Url to fetch
	 * @return false|object
	 */
	public function getJsonData($url)
	{
		$this->data = false;
		$res = @file_get_contents($url);
		$this->http_response_header = $http_response_header;
		$this->TResponseHeader = self::parseHeaders($http_response_header);
		if ($res !== false) {
			$pos = strpos($res, '{');
			if ($pos > 0) {
				// cela signifie qu'il y a une erreur ou que la sortie n'est pas propre
				$res = substr($res, $pos);
			}

			$this->data = json_decode($res);
		}

		return $this->data;
	}

	/**
	 * @param string $url Url to fetch
	 * @return false|string
	 */
	public function getContents($url)
	{
		$this->data = false;
		$res = @file_get_contents($url);
		$this->http_response_header = $http_response_header;
		$this->TResponseHeader = self::parseHeaders($http_response_header);
		if ($res !== false) {
			$this->data = $res;
		}
		return $this->data;
	}

	/**
	 * Get message from HTTP code
	 *
	 * @param int $code HTTP code
	 * @return string Message
	 */
	public static function httpResponseCodeMsg($code = null)
	{
		if ($code !== null) {
			switch ($code) {
				case 100:
					$text = 'Continue';
					break;
				case 101:
					$text = 'Switching Protocols';
					break;
				case 200:
					$text = 'OK';
					break;
				case 201:
					$text = 'Created';
					break;
				case 202:
					$text = 'Accepted';
					break;
				case 203:
					$text = 'Non-Authoritative Information';
					break;
				case 204:
					$text = 'No Content';
					break;
				case 205:
					$text = 'Reset Content';
					break;
				case 206:
					$text = 'Partial Content';
					break;
				case 300:
					$text = 'Multiple Choices';
					break;
				case 301:
					$text = 'Moved Permanently';
					break;
				case 302:
					$text = 'Moved Temporarily';
					break;
				case 303:
					$text = 'See Other';
					break;
				case 304:
					$text = 'Not Modified';
					break;
				case 305:
					$text = 'Use Proxy';
					break;
				case 400:
					$text = 'Bad Request';
					break;
				case 401:
					$text = 'Unauthorized';
					break;
				case 402:
					$text = 'Payment Required';
					break;
				case 403:
					$text = 'Forbidden';
					break;
				case 404:
					$text = 'Not Found';
					break;
				case 405:
					$text = 'Method Not Allowed';
					break;
				case 406:
					$text = 'Not Acceptable';
					break;
				case 407:
					$text = 'Proxy Authentication Required';
					break;
				case 408:
					$text = 'Request Time-out';
					break;
				case 409:
					$text = 'Conflict';
					break;
				case 410:
					$text = 'Gone';
					break;
				case 411:
					$text = 'Length Required';
					break;
				case 412:
					$text = 'Precondition Failed';
					break;
				case 413:
					$text = 'Request Entity Too Large';
					break;
				case 414:
					$text = 'Request-URI Too Large';
					break;
				case 415:
					$text = 'Unsupported Media Type';
					break;
				case 500:
					$text = 'Internal Server Error';
					break;
				case 501:
					$text = 'Not Implemented';
					break;
				case 502:
					$text = 'Bad Gateway';
					break;
				case 503:
					$text = 'Service Unavailable';
					break;
				case 504:
					$text = 'Gateway Time-out';
					break;
				case 505:
					$text = 'HTTP Version not supported';
					break;
				default:
					$text = 'Unknown http status code "' . htmlentities($code) . '"';
					break;
			}

			return $text;
		} else {
			return $text = 'Unknown http status code NULL';
		}
	}

	/**
	 * Parse headers
	 *
	 * @param array $headers Headers
	 * @return array Parsed headers
	 */
	public static function parseHeaders($headers)
	{
		$head = array();
		if (!is_array($headers)) {
			return $head;
		}

		foreach ($headers as $k=>$v) {
			$t = explode(':', $v, 2);
			if ( isset($t[1]) )
				$head[ trim($t[0]) ] = trim($t[1]);
			else {
				$head[] = $v;
				if ( preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out) )
					$head['reponse_code'] = intval($out[1]);
			}
		}
		return $head;
	}
}
