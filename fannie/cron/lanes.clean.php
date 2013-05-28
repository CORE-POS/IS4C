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

   lanes.clean.php

   Empty out old entries in localtrans_today

*/

include('../config.php');
include($FANNIE_ROOT.'src/cron_msg.php');
include($FANNIE_ROOT.'src/SQLManager.php');

set_time_limit(0);

foreach($FANNIE_LANES as $ln){
	$sql = new SQLManager($ln['host'],$ln['type'],$ln['trans'],$ln['user'],$ln['pw']);
	if ($sql === False){
		cron_msg("Could not clear lane: ".$ln['host']);
		continue;
	}

	$cleanQ = "DELETE FROM localtrans_today WHERE ".$sql->datediff($sql->now(),'datetime')." <> 0";
	$cleanR = $sql->query($cleanQ,$ln['trans']);
	$cleanQ = "DELETE FROM localtrans WHERE ".$sql->datediff($sql->now(),'datetime')." > 30";
	$cleanR = $sql->query($cleanQ,$ln['trans']);

	$cleanQ = 'DELETE FROM efsnetTokens WHERE '.$sql->datediff($sql->now(),'expireDay').' <> 0 ';
	$cleanR = $sql->query($cleanQ,$ln['trans']);
}


?>
