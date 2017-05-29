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

use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\MiscLib;

if (!class_exists("AutoLoader")) include("lib/AutoLoader.php");

COREPOS\pos\lib\LocalStorage\LaneCache::clear();

AutoLoader::loadMap();

CoreState::initiateSession();

/**
  Avoid infinite redirect. If a page discovers the current
  session is invalid it redirects to here. If this script
  can't initiate the session there's no way to continue.
  The issue is most likely a failing DB connection
*/
if (CoreLocal::get('CashierNo') === '') {
    trigger_error('Cannot initialize system', E_USER_ERROR);
    echo "Initialization failed; check configuration" . PHP_EOL;
    exit;
}

CoreLocal::set('ValidJson', false);
CoreLocal::refresh();
CoreLocal::migrateSettings();

if (MiscLib::pingport('127.0.0.1:15674', 'not a database')) {
    CoreLocal::set('MQ', true);
} else {
    CoreLocal::set('MQ', false);
}

/**
  Go to login screen if no one is signed in
  Go to lock screen if someone is signed in
*/
if (!headers_sent()) {
    $my_url = MiscLib::base_url();
    if (CoreLocal::get('LoggedIn') == 0) {
        header("Location: {$my_url}gui-modules/login2.php");
    } else {
        header("Location: {$my_url}gui-modules/login3.php");
    }
}

