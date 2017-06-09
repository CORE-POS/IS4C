<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Filesystem;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class InstaCartTask extends FannieTask 
{
    public $name = 'Submit InstaCart data';

    public $description = 'Submits product data to InstaCart via FTP';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '2',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        if (!class_exists('InstaFileV3')) {
            include(__DIR__ . '/InstaFileV3.php');
        }
        $csvfile = tempnam(sys_get_temp_dir(), 'ICT');
        $insta = new InstaFileV3($dbc, $this->config);
        $insta->getFile($csvfile);

        /**
          Upload export via (S)FTP
        */
        if (class_exists('League\\Flysystem\\Sftp\\SftpAdapter')) {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $adapter = new SftpAdapter(array(
                'host' => 'sftp.instacart.com',
                'username' => $settings['InstaCartFtpUser'],
                'password' => $settings['InstaCartFtpPw'],
                'port' => 22,
            ));
            $filesystem = new Filesystem($adapter);
            $filesystem->put(date('mdY') . '.csv', file_get_contents($csvfile));
        }

        unlink($csvfile);
    }
}

