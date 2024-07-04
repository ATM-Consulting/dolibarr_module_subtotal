<?php

if(!function_exists('isModEnabled')) {
	function isModEnabled($module) {
		global $conf;
		return !empty($conf->$module->enabled);
	}
}
