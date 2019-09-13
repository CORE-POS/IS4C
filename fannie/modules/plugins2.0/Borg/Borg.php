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

/**
*/
class Borg extends COREPOS\Fannie\API\FanniePlugin 
{
    public $plugin_settings = array(
    'BorgRepo' => array('default'=>'', 'label'=>'Borg Repo',
            'description'=>'Backup target location, e.g. user@host:/path/repo-name'), 
    'BorgBinPath' => array('default'=>'/usr/bin/', 'label'=>'BIN Path',
            'description'=>'Directory containing the borg program'), 
    'BorgDaily' => array('default'=>7, 'label'=>'Daily snapshots',
            'description'=>'Number of daily backups to keep'), 
    'BorgMonthly' => array('default'=>3, 'label'=>'Monthly snapshots',
            'description'=>'Number of monthly backups to keep'), 
    'BorgBackupPlugin' => array('default'=>'Simple','label'=>'Backup Plugin',
            'options' => array('SimpleBackup' => 'Simple', 'FastBackup' => 'Fast', 'n/a'=>'n/a'),
            'description'=>'Backup plugin being used (use n/a to set target manually)'),
    'BorgManualTarget' => array('default'=>'', 'label'=>'Manual target',
            'description'=>'Specific path to backup when plugin chosen is n/a'), 
    );

    public $plugin_description = 'Plugin to ship database backups to a borgbackup repo';
}

