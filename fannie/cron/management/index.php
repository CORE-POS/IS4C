<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
header('Location: CronManagementPage.php');
exit;
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
include($FANNIE_ROOT.'auth/login.php');


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 17Oct2012 Eric Lee Add note about Help under Command links Help tooltip in Command.

*/

/* This is a very light wrapper around cron. The
   interface replicates a crontab file, so you do
   have to understand how one works

   This tool helps by:
   1. Listing available scripts
   2. Automatically changing to fannie's "cron" directory
   3. Directing logging to fannie's logs/dayend.log

   Hiding path management makes the list of tasks
   easier to look at, but also insures a script can
   find fannie's configuration without relying on
   hard-coded paths

   This *should* display but otherwise ignore any
   jobs that weren't configured through the web
   interface. The whole tool is meant to be optional
   hand-holding for new users.
*/

if(!validateUserQuiet('admin')){
	$url = $FANNIE_URL.'auth/ui/loginform.php';
	$rd = $FANNIE_URL.'cron/management/';
	header("Location: $url?redirect=$rd");
	exit;
}

$header = "Manage Scheduled Tasks";
$page_title = "Fannie : Scheduled Tasks";
include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['enabled'])){
	$indexes = $_REQUEST['enabled'];
	if (is_array($indexes) && count($indexes) > 0){
		$tmpfn = tempnam(sys_get_temp_dir(),"");
		$fp = fopen($tmpfn,"w");
		if (isset($_REQUEST['email']) && !empty($_REQUEST['email']))
			fwrite($fp,"MAILTO=".$_REQUEST['email']."\n");
		foreach($indexes as $i){
			fprintf($fp,"%s %s %s %s %s %s\n",
				$_REQUEST['min'][$i],
				$_REQUEST['hour'][$i],
				$_REQUEST['day'][$i],
				$_REQUEST['month'][$i],
				$_REQUEST['wkdy'][$i],
				$_REQUEST['cmd'][$i]
			);
		}
		fclose($fp);

		$output = system("crontab $tmpfn 2>&1");

		/* backup crontab just in case */
		$dbc->query("TRUNCATE TABLE cronBackup");
		$dbc->query(sprintf("INSERT INTO cronBackup
				VALUES (%s,%s)",
				$dbc->now(),
				$dbc->escape(file_get_contents($tmpfn))
			    )
		);

		unlink($tmpfn);
	}
}

if (function_exists('posix_getpwuid')){
	$chk = posix_getpwuid(posix_getuid());
	echo "PHP is running as: ".$chk['name']."<br />";
	echo "Fannie will attempt to use their crontab<br /><br />";
}
else {
	echo "PHP is (probably) running as: ".get_current_user()."<br />";
	echo "This is probably Windows; this tool won't work<br /><br />";
}

if (!is_writable($FANNIE_ROOT.'logs/dayend.log')){
	echo "<i>Warning: dayend.log ({$FANNIE_ROOT}logs/dayend.log)
		is not writable. Logging task results may
		not work</i>";
}
else
	echo "Default logging will be to {$FANNIE_ROOT}logs/dayend.log";

echo "<br />Click the 'Command' link for popup Help.";
echo "<br /><br />";

$jobs = scan_scripts($FANNIE_ROOT.'cron',array());
$tab = read_crontab();

echo "<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">";
echo "<b>E-mail address</b>: <input name=\"email\" value=\"{$tab['email']}\" /><br />";
echo "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">";
echo "<tr><th>Enabled</th><th>Min</th><th>Hour</th><th>Day</th><th>Month</th><th>Wkdy</th><th>Command/Help</th></tr>";
$i = 0;
foreach($jobs as $job){
	$shortname = substr($job,strlen($FANNIE_ROOT."cron/"));

	$cmd = "cd {$FANNIE_ROOT}cron && php ./{$shortname} >> {$FANNIE_ROOT}logs/dayend.log";

	// defaults are set as once a year so someone doesn't accidentallly
	// start firing a job off every minute
	printf('<tr>
		<td><input type="checkbox" name="enabled[]" %s value="%d" /></td>
		<td><input type="text" size="2" name="min[]" value="%s" /></td>
		<td><input type="text" size="2" name="hour[]" value="%s" /></td>
		<td><input type="text" size="2" name="day[]" value="%s" /></td>
		<td><input type="text" size="2" name="month[]" value="%s" /></td>
		<td><input type="text" size="2" name="wkdy[]" value="%s" /></td>
		<td><input type="hidden" name="cmd[]" value="%s" />
		<a href="" onclick="window.open(\'help.php?fn=%s\',\'Help\',\'height=200,width=500,scrollbars=1\');return false;" title="Help">%s</a></td>
		</tr>',
		(isset($tab['jobs'][$shortname])?'checked':''),$i,
		(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['min']:'0'),
		(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['hour']:'0'),
		(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['day']:'1'),
		(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['month']:'1'),
		(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['wkdy']:'*'),
		(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['cmd']:$cmd),
		base64_encode($shortname),$shortname
	);
	if (isset($tab['jobs'][$shortname]))
		unset($tab['jobs'][$shortname]);
	$i++;
}

/* list out any jobs that WERE NOT covered
   in the above loop. Those jobs weren't
   set up by this tool and should not be edited
*/
foreach ($tab['jobs'] as $job){
	printf('<tr>
		<td><input type="checkbox" name="enabled[]" %s value="%d" /></td>
		<td><input type="text" size="2" name="min[]" value="%s" /></td>
		<td><input type="text" size="2" name="hour[]" value="%s" /></td>
		<td><input type="text" size="2" name="day[]" value="%s" /></td>
		<td><input type="text" size="2" name="month[]" value="%s" /></td>
		<td><input type="text" size="2" name="wkdy[]" value="%s" /></td>
		<td><input type="hidden" name="cmd[]" value="%s" />%s</td>
		</tr>',
		'checked',$i,
		$job['min'],
		$job['hour'],
		$job['day'],
		$job['month'],
		$job['year'],
		$job['cmd'],
		$job['cmd']
	);
	$i++;
}
echo "</table><br />";
echo '<input type="submit" value="Save" />';
echo '</form>';

include($FANNIE_ROOT.'src/footer.html');

function scan_scripts($dir,$arr){
	if (!is_dir($dir)){ 
		if (substr($dir,-4) == ".php")
			$arr[] = $dir;
		return $arr;
	}
	else if (substr($dir,-11) == "/management"){
		return $arr;
	}
	else {
		$dh = opendir($dir);
		while(($file = readdir($dh)) !== false){
			if ($file == "." || $file == "..")
				continue;
			$arr = scan_scripts($dir."/".$file,$arr);
		}
		return $arr;
	}
}

function read_crontab(){
	global $FANNIE_ROOT;
	$pp = popen('crontab -l 2>&1','r');
	$lines = array();
	while(!feof($pp))
		$lines[] = fgets($pp);
	pclose($pp);
	$ret = array(
	'jobs' => array(),
	'email' => ''
	);
	foreach($lines as $line){
		if($line === false) continue;
		$line = trim($line);
		if ($line[0] == "#") continue;
		if (substr($line,0,6) == "MAILTO")
			$ret['email'] = substr($line,7);
		$tmp = preg_split("/\s+/",$line,6);
		if (count($tmp) == 6){
			$sn = str_replace("cd {$FANNIE_ROOT}cron && php ./","",$tmp[5]);
			$sn = str_replace(" >> {$FANNIE_ROOT}logs/dayend.log","",$sn);
			$ret['jobs'][$sn] = array(
				'min' => $tmp[0],
				'hour' => $tmp[1],
				'day' => $tmp[2],
				'month' => $tmp[3],
				'wkdy' => $tmp[4],
				'cmd' => $tmp[5]
			);
		}
	}
	return $ret;
}
?>
