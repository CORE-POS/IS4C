<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class GiterateTask extends FannieTask
{

    public $name = 'Check for Updates';

    public $description = 'Checks for new versions of CORE. Requires
giterate submodule';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 0,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    static public function genCommand()
    {
        $path = realpath(dirname(__FILE__) . '/../../../');
        $giterate = escapeshellarg(realpath(dirname(__FILE__) . '/../../../giterate/giterate.py'));

        $args = ' --name=' . escapeshellarg('CORE POS');
        $args .= ' --path=' . escapeshellarg($path);
        $args .= ' --remote=' . escapeshellarg('https://github.com/CORE-POS/IS4C');

        $python = 'python';
        if (file_exists('/usr/bin/python26')) { // RHEL
            $python = 'python26';
        }

        return $python . ' ' . $giterate . $args;
    }

    public function run()
    {
        if (!file_exists(dirname(__FILE__) . '/../../../giterate/giterate.py')) {
            echo $this->cronMsg('Cannot check for updates; giterate not found');
            DataCache::freshen('Update check failed; giterate not found', 'month', 'GiterateTask');
            return false;
        }

        exec(self::genCommand(), $output, $exit_code);
        if ($exit_code != 0) {
            $error_msg = '';
            foreach ($output as $line) {
                $error_msg .= $line . ' ';
            }
            echo $this->cronMsg($error_msg);
            DataCache::freshen('Update check failed; ' . $error_msg, 'month', 'GiterateTask');
            return false;
        } else {
            $current = false;
            $next = false;
            $need_updates = false;
            foreach($output as $line) {
                echo $this->cronMsg($line);
                if ($line == 'Update available') {
                    $need_updates = true;
                } else if (substr($line,0, 16) == 'Current Version:') {
                    $current = substr($line, 17);
                } else if (substr($line,0, 18) == 'Available Version:') {
                    $next = substr($line, 19);
                }
            }
            if ($current === '0') {
                $current = '(updater has never run)';
            }
            $userMsg = 'Current version: ' . $current;
            if ($need_updates && $next) {
                $userMsg .= '; New version ' . $next . ' is available';
            }
            DataCache::freshen($userMsg, 'month', 'GiterateTask');

            return true;
        }
    }
}

