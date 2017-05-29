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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class SimpleBackupTask extends FannieTask 
{
    public $name = 'Simple Backup w/ mysqldump';

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
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB, $FANNIE_PLUGIN_SETTINGS,
            $FANNIE_SERVER, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW;

        $dbs = array($FANNIE_OP_DB,$FANNIE_TRANS_DB,$FANNIE_ARCHIVE_DB);
        foreach ($dbs as $db) {
            $path = realpath($FANNIE_PLUGIN_SETTINGS['SimpleBackupDir']);
            $dir = $path . "/" . $db;
            if (!is_dir($dir) && !mkdir($dir)) {
                $this->cronMsg('Could not create backup directory: ' . $dir, FannieLogger::ERROR);
                continue;
            }

            /* sort backups in descending order, remove
               old ones from the end of the array */
            $files = scandir($dir,1);
            array_pop($files); // . directory
            array_pop($files); // .. directory
            $num = count($files);
            while ($num >= $FANNIE_PLUGIN_SETTINGS['SimpleBackupNum']) {
                if (is_file(realpath($dir."/".$files[$num-1]))) {
                    unlink($dir."/".$files[$num-1]);
                }
                $num--;
            }

            $cmd = realpath($FANNIE_PLUGIN_SETTINGS['SimpleBackupBinPath']."/mysqldump");
            if ($cmd === false) {
                $this->cronMsg('Could not locate mysqldump program', FannieLogger::ERROR);
                break; // no point in trying other databases
            }
            $cmd = escapeshellcmd($cmd);
            $cmd .= ' -q --databases 
                -h ' . escapeshellarg($FANNIE_SERVER) . '
                -u ' . escapeshellarg($FANNIE_SERVER_USER) . '
                -p' . escapeshellarg($FANNIE_SERVER_PW) . ' 
                ' . escapeshellarg($db);
            $outfile = $dir . '/' . $db . date('Ymd') . '.sql';

            $gzip = $this->gzip();
            if ($FANNIE_PLUGIN_SETTINGS['SimpleBackupGZ'] == 1 && $gzip !== false) {
                $cmd .= ' | ' . escapeshellcmd($gzip);
                $outfile .= '.gz';
            }

            $cmd .= ' > ' . escapeshellarg($outfile);
            system($cmd);

            if (file_exists($outfile)) {
                $this->cronMsg('Backup successful: ' . $outfile, FannieLogger::INFO);
            } else {
                $this->cronMsg('Error creating backup: ' . $outfile, FannieLogger::ERROR);
            }
        }
    }

    private function gzip()
    {
        if (file_exists('/bin/gzip')) {
            return '/bin/gzip';
        } elseif (file_exists('/usr/bin/gzip')) {
            return '/usr/bin/gzip';
        } else {
            return false;
        }
    }
}

