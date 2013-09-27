<?php

define('ROOT','/var/www/dolibarr/htdocs/');
define('COREROOT','/var/www/atm/atm-core/');
define('COREHTTP','http://127.0.0.1/atm/atm-core/');
define('HTTP','http://127.0.0.1/dolibarr/');

if(!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
	include(ROOT."master.inc.php");
}
elseif(!defined('INC_FROM_DOLIBARR')) {
	include(ROOT."main.inc.php");
} else {
	global $dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass;
}

define('DB_HOST',$dolibarr_main_db_host);
define('DB_NAME',$dolibarr_main_db_name);
define('DB_USER',$dolibarr_main_db_user);
define('DB_PASS',$dolibarr_main_db_pass);
define('DB_DRIVER','mysqli');

define('DOL_PACKAGE', true);
define('USE_TBS', true);

require(COREROOT.'inc.core.php');

define('DOL_ADMIN_USER', 'admin');

/**
 * Configuration spécifique au module
 */

