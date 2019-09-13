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
class BorgTask extends FannieTask 
{
    public $name = 'Borg';

    public $description = 'Send backups to a borg repo';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $target = $this->getTarget($settings);
        if ($target == '' || !file_exists($target)) {
            $this->cronMsg('Backup target not found: ' . $target, FannieLogger::ALERT);
            return;
        }

        $cmd = realpath($settings['BorgBinPath'] . DIRECTORY_SEPARATOR . "borg");
        if ($cmd === false) {
            $this->cronMsg('Borg command not found', FannieLogger::ALERT);
            return;
        }

        $repo = $settings['BorgRepo'];
        $backup = $cmd
            . ' create '
            . ' --stats '
            . ' --list '
            . escapeshellarg($repo . '::core-db-{now:%Y-%m-%d}')
            . ' ' . escapeshellarg($target);

        $prune = $cmd
            . ' prune '
            . ' -v '
            . ' --list '
            . escapeshellarg($repo);
        if (((int)$settings['BorgDaily']) > 0) {
            $prune .= ' --keep-daily=' . ((int)$settings['BorgDaily']);
        }
        if (((int)$settings['BorgMonthly']) > 0) {
            $prune .= ' --keep-monthly=' . ((int)$settings['BorgMonthly']);
        }
        $prune .= ' --prefix="core-db-" ';

        $this->cronMsg('Backup command: ' . $backup, FannieLogger::INFO);
        $lastLine = exec($backup, $retval, $output);
        if ($retval == 0) {
            $this->cronMsg('Borg backup complete', FannieLogger::INFO);
        } else {
            $this->cronMsg('Borg backup failed', FannieLogger::ALERT);
            $this->cronMsg('Detail: ' . implode("\n", $output) . "\n" . $lastLine, FannieLogger::ALERT);
        }

        $this->cronMsg('Prune command: ' . $prune, FannieLogger::INFO);
        $lastLine = exec($prune, $retval, $output);
        if ($retval == 0) {
            $this->cronMsg('Borg prune complete', FannieLogger::INFO);
        } else {
            $this->cronMsg('Borg prune failed', FannieLogger::ALERT);
            $this->cronMsg('Detail: ' . implode("\n", $output) . "\n" . $lastLine, FannieLogger::ALERT);
        }
    }

    private function getTarget($settings)
    {
        if ($settings['BorgBackupPlugin'] == 'Simple') {
            return trim($settings['SimpleBackupDir']);
        } elseif ($settings['BorgBackupPlugin'] == 'Fast') {
            return trim($settings['FastBackupTarget']);
        } 

        return trim($settings['BorgBackupManual']);
    }
}

