<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('UIGLib')) {
    include(__DIR__ . '/UIGLib.php');
}

class MyUIGTask extends FannieTask
{
    public $username_field = 'MyUnfiInvoiceUser';
    public $password_field = 'MyUnfiInvoicePass';
    public $vendor_id = 1;

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $user = $FANNIE_PLUGIN_SETTINGS[$this->username_field];
        $pass = $FANNIE_PLUGIN_SETTINGS[$this->password_field];
        $account = $FANNIE_PLUGIN_SETTINGS['UnfiAccount'];

        $cmd = __DIR__ . '/noauto/myunfi2.py'
            . ' ' . escapeshellarg('-u')
            . ' ' . escapeshellarg($user)
            . ' ' . escapeshellarg('-p')
            . ' ' . escapeshellarg($pass)
            . ' ' . escapeshellarg('-a')
            . ' ' . escapeshellarg($account);
        $ret = exec($cmd, $output);
        if ($ret != 0) {
            $this->cronMsg("UNFI download errored\n" . implode("\n", $output) . "\n", FannieLogger::ALERT);
        }

        $dir = opendir('/tmp/un');
        while (($file = readdir($dir)) !== false) {
            if (substr($file, -4) == '.zip') {
                UIGLib::import('/tmp/un/' . $file, $this->vendor_id);
                unlink('/tmp/un/' . $file);
            }
        }

    }

}

