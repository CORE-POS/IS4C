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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

/**
*/
class FastBackupTask extends FannieTask 
{
    public $name = 'Fast Backup w/ mydumper';

    public $description = 'Creates backups of databases';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbs = explode(',', $settings['FastBackupDBs']);
        if (count($dbs) == 0) {
            $dbs == array("__ALL__");
        }
        foreach ($dbs as $db) {
            $dir = realpath($settings['FastBackupTarget']);
            if (!is_dir($dir) && !mkdir($dir)) {
                $this->cronMsg('Could not create backup directory: ' . $dir, FannieLogger::ALERT);
                break; // no point in trying other databases
            }

            $cmd = realpath($settings['FastBackupBinPath'] . DIRECTORY_SEPARATOR . "mydumper");
            if ($cmd === false) {
                $this->cronMsg('Could not locate mydumper program', FannieLogger::ALERT);
                break; // no point in trying other databases
            }

            $cmd = escapeshellcmd($cmd)
                . " -v 3"
                . " --no-views"
                . " --chunk-filesize 10"
                . " --less-locking"
                . " --ouput-dir " . escapeshellarg($dir)
                . " -u " . escapeshellarg($this->config->get('SERVER_USER'))
                . " -p " . escapeshellarg($this->config->get('SERVER_PW'));
            if ($db !== "__ALL__") {
                $cmd .= " -B " . escapeshellarg($db);
            }
            $lastLine = exec($cmd, $retval, $output);

            if ($retval == 0) {
                $this->cronMsg('Backup successful: ' . ($db === "__ALL__" ? "All Databases" : $db), FannieLogger::INFO);
            } else {
                $this->cronMsg('Error creating backup: ' . ($db === "__ALL__" ? "All Databases" : $db), FannieLogger::ALERT);
                $this->cronMsg('Error detail: ' . implode("\n", $ouput) . "\n" . $lastLine, FannieLogger::ALERT);
            }
        }
    }
}

