<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/** 
  @file 
  @brief This file specifies the LocalStorage implemenation

  Include this file to get a LocalStorage instance
  in the variable $CORE_LOCAL.
*/

$elog = realpath(dirname(__FILE__).'/../../log/').'/debug_lane.log';
ini_set('error_log',$elog);
//ini_set('display_errors', false);

$LOCAL_STORAGE_MECHANISM = 'COREPOS\\pos\\lib\\LocalStorage\\SessionStorage';

if (!class_exists($LOCAL_STORAGE_MECHANISM)) {
    include(__DIR__ . '/SessionStorage.php');
}
if (!class_exists('CoreLocal')) {
    include(__DIR__ . '/CoreLocal.php');
}
if (!class_exists('COREPOS\\pos\\lib\\LocalStorage\\WrappedStorage')) {
    include(__DIR__ . '/WrappedStorage.php');
}
CoreLocal::setHandler($LOCAL_STORAGE_MECHANISM);

$CORE_LOCAL = new COREPOS\pos\lib\LocalStorage\WrappedStorage();
global $CORE_LOCAL;

// this includes ini.php
CoreLocal::refresh();

if (!defined('CONF_LOADED')) {
    define('CONF_LOADED', true);
}
