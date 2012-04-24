<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require('../config.php');
require($FANNIE_ROOT.'class-lib/FannieModule.php');
require($FANNIE_ROOT.'class-lib/FannieFunctions.php');
ini_set('display_errors',1);

unpack_symbols();

if (!isset($FANNIE_SYMBOLS) || !is_array($FANNIE_SYMBOLS)){
	echo '<b>Module system is very broken. Try going here</b>:<br />';
	printf('<a href="%sinstall/module_system/">%sinstall/module_system/</a>',
		$FANNIE_URL,$FANNIE_URL);
	exit;
}

/**
  IMPORTANT
  PHP5 feature provides failsafe to recover from
  class-not-found errors that can occur
  depending on module load order 
*/
function __autoload($class_name){
	load_class($class_name);
}

foreach($FANNIE_SYMBOLS['classes'] as $class=>$file){
	load_class($class);
}

$mod = isset($_REQUEST['m']) ? $_REQUEST['m'] : '';

if (empty($mod)){
	echo '<b>Error</b>: no module specified';
	exit;
}
elseif (!class_exists($mod)){
	echo '<b>Error</b>: module '.$mod.' not found';
	exit;
}

$instance = new $mod();
$instance->run_module();

?>
