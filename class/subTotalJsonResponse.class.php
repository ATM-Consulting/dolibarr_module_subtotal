<?php
/**
* SPDX-License-Identifier: GPL-3.0-or-later
* This file is part of Dolibarr module Subtotal
*/


class SubTotalJsonResponse{

	/**
	 * the call status to determine if success or fail
	 * @var int $result
	 */
	public $result = 0;

	/**
	 * data to return to call can be all type you want
	 * @var mixed
	 */
	public $data;

	/**
	 * debug data
	 * @var mixed
	 */
	public $debug;

	/**
	 * returned message used usually as set event message
	 * @var string $msg
	 */
	public $msg = '';

	/**
	 * the current newToken
	 * @var mixed|string
	 */
	public $newToken = '';

	public function __construct(){
		$this->newToken = newToken();
	}

	/**
	 * return json encoded of object
	 * @return string JSON
	 */
	public function getJsonResponse(){
		$jsonResponse = new stdClass();
		$jsonResponse->result = $this->result;
		$jsonResponse->msg = $this->msg;
		$jsonResponse->newToken = $this->newToken;
		$jsonResponse->data = $this->data;
		$jsonResponse->debug = $this->debug;

		return json_encode($jsonResponse, JSON_PRETTY_PRINT);
	}
}
