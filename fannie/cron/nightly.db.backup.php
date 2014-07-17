<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

/* HELP

   nightly.db.backup.php

   DEPRECATED: Use SimpleBackup plugin for
   similar functionality if needed

   Backup MySQL database based
   on configuration settings
*/

include('../config.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$dbs = array($FANNIE_OP_DB,$FANNIE_TRANS_DB,$FANNIE_ARCHIVE_DB);

foreach($dbs as $db){
    $path = realpath($FANNIE_BACKUP_PATH);
    $dir = $path."/".$db;
    if (!is_dir($dir)) 
        mkdir($dir);

    /* sort backups in descending order, remove
       old ones from the end of the array */
    $files = scandir($dir,1);
    array_pop($files); // . directory
    array_pop($files); // .. directory
    $num = count($files);
    while($num >= $FANNIE_BACKUP_NUM){
        if (is_file(realpath($dir."/".$files[$num-1]))){
            unlink($dir."/".$files[$num-1]);
        }
        $num--;
    }

    $cmd = realpath($FANNIE_BACKUP_BIN."/mysqldump");
    $cmd .= " -q --databases -h \"$FANNIE_SERVER\" -u \"$FANNIE_SERVER_USER\" -p\"$FANNIE_SERVER_PW\" \"$db\"";
    $cmd = escapeshellcmd($cmd);
    if ($FANNIE_BACKUP_GZIP)
        $cmd .= " | ".escapeshellcmd(realpath($FANNIE_BACKUP_BIN."/gzip"));
    
    $outfile = $dir."/".$db.date("Ymd").".sql";
    if ($FANNIE_BACKUP_GZIP) $outfile .= ".gz";

    $cmd .= " > ".escapeshellcmd("\"$outfile\"");

    system($cmd);
}

?>
