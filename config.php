<?php

		
	if(is_file('../main.inc.php'))$dir = '../';
	else  if(is_file('../../../main.inc.php'))$dir = '../../../';
	else $dir = '../../';

	include($dir."main.inc.php");
