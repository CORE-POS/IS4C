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
class SimpleBackup extends FanniePlugin 
{
    public $plugin_settings = array(
    'SimpleBackupDir' => array('default'=>'/tmp/', 'label'=>'Backup Save Directory',
            'description'=>'Backups are stored here'), 
    'SimpleBackupBinPath' => array('default'=>'/usr/bin/', 'label'=>'BIN Path',
            'description'=>'Directory containing the mysqldump program'), 
    'SimpleBackupNum' => array('default'=>1, 'label'=>'Number of Backups',
            'description' => 'Keep X newest backups'),
    'SimpleBackupGZ' => array('default'=>0, 'label'=>'Compress Backups',
            'options'=>array('Yes'=>1, 'No'=>0),
            'description' => 'Shrink backups with gzip'),
    );

    public $plugin_description = 'Plugin automating mysqldump backups';
}

