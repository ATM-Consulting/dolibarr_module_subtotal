<?php

		
	if(is_file('../main.inc.php')) include("../main.inc.php");
	else  if(is_file('../../../main.inc.php')) include("../../../main.inc.php");
	else include("../../main.inc.php");