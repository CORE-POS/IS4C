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
include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include_once($FANNIE_ROOT.'src/tmp_dir.php');


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 14Mar2013 Andy Theuninck class overhaul w/ select options
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

class CronManagementPage extends FanniePage {

	protected $header = "Manage Scheduled Tasks";
	protected $title = "Fannie : Scheduled Tasks";
	protected $must_authenticate = True;
	protected $auth_classes = array('admin');

	function preprocess(){
		global $FANNIE_OP_DB;
		if (is_array(FormLib::get_form_value('enabled'))){

			$indexes = FormLib::get_form_value('enabled');
			$min = FormLib::get_form_value('min',array());
			$hour = FormLib::get_form_value('hour',array());
			$day = FormLib::get_form_value('day',array());
			$month = FormLib::get_form_value('month',array());
			$wkdy = FormLib::get_form_value('wkdy',array());
			$cmd = FormLib::get_form_value('cmd',array());
			$email = FormLib::get_form_value('email','');

			if (is_array($indexes) && count($indexes) > 0){
				$tmpfn = tempnam(sys_get_temp_dir(),"");
				$fp = fopen($tmpfn,"w");
				if (!empty($email))
					fwrite($fp,"MAILTO=".$email."\n");
				foreach($indexes as $i){
					fprintf($fp,"%s %s %s %s %s %s\n",
						$min[$i],
						$hour[$i],
						$day[$i],
						$month[$i],
						$wkdy[$i],
						$cmd[$i]
					);
				}
				fclose($fp);

				$output = system("crontab $tmpfn 2>&1");

				/* backup crontab just in case */
				$dbc = FannieDB::get($FANNIE_OP_DB);
				$trun = $dbc->prepare_statement("TRUNCATE TABLE cronBackup");
				$dbc->exec_statement($trun);
				$prep = $dbc->prepare_statement('INSERT INTO cronBackup VALUES ('.$dbc->now().', ?)');
				$dbc->exec_statement($prep,array(file_get_contents($tmpfn)));

				unlink($tmpfn);
			}
		}
		return True;
	}

	function body_content(){
		global $FANNIE_ROOT;
		$ret = '';

		if (function_exists('posix_getpwuid')){
			$chk = posix_getpwuid(posix_getuid());
			$ret .= "PHP is running as: ".$chk['name']."<br />";
			$ret .= "Fannie will attempt to use their crontab<br /><br />";
		}
		else {
			$ret .= "PHP is (probably) running as: ".get_current_user()."<br />";
			$ret .= "This is probably Windows; this tool won't work<br /><br />";
		}

		if (!is_writable($FANNIE_ROOT.'logs/dayend.log')){
			$ret .= "<i>Warning: dayend.log ({$FANNIE_ROOT}logs/dayend.log)
				is not writable. Logging task results may
				not work</i>";
		}
		else
			$ret .= "Default logging will be to {$FANNIE_ROOT}logs/dayend.log";

		$ret .= "<br />Click the 'Command' link for popup Help.";
		$ret .= "<br /><br />";

		$jobs = $this->scan_scripts($FANNIE_ROOT.'cron',array());
		$tab = $this->read_crontab();

		if (True) {
		$mode = FormLib::get_form_value('mode','simple');

		$ret .= "<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">";
		$ret .= sprintf ('<input type="hidden" name="mode" value="%s" />',$mode);
		if ($mode == 'simple'){
			$ret .= '<a href="CronManagementPage.php?mode=advanced">Switch to Advanced View</a><br />';
		}
		else {
			$ret .= '<a href="CronManagementPage.php?mode=simple">Switch to Simple View</a><br />';
		}
		$ret .= "<b>E-mail address</b>: <input name=\"email\" value=\"{$tab['email']}\" /><br />";

		$ret .= "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">";
		$ret .= "<tr><th>Enabled</th><th>Min</th><th>Hour</th><th>Day</th><th>Month</th><th>Wkdy</th><th>Command/Help</th></tr>";
		$i = 0;
		foreach($jobs as $job){
			$shortname = substr($job,strlen($FANNIE_ROOT."cron/"));
			$nicename = rtrim($shortname,'php');
			$nicename = rtrim($nicename,'.');
			$nicename = str_replace('.',' ',$nicename);

			$cmd = "cd {$FANNIE_ROOT}cron && php ./{$shortname} >> {$FANNIE_ROOT}logs/dayend.log";

			$simple = $this->simple_row($shortname,$nicename,$cmd,$tab,$i);
			if ($simple !== False && $mode == 'simple')
				$ret .= $simple;
			else
				$ret .= $this->advanced_row($shortname,$nicename,$cmd,$tab,$i);
			// defaults are set as once a year so someone doesn't accidentallly
			// start firing a job off every minute
			if (isset($tab['jobs'][$shortname]))
				unset($tab['jobs'][$shortname]);
			$i++;
		}

		/* list out any jobs that WERE NOT covered
		   in the above loop. Those jobs weren't
		   set up by this tool and should not be edited
		*/
		foreach ($tab['jobs'] as $job){
			$ret .= sprintf('<tr>
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

		$ret .= "</table><br />";
		$ret .= '<input type="submit" value="Save" />';
		$ret .= '</form>';
		//JIG
		}

		return $ret;
	}

	function simple_row($shortname,$nicename,$cmd,$tab,$i){
		$ret = '<tr>';
		$ret .= sprintf('<td><input type="checkbox" name="enabled[]" %s value="%d" /></td>',
			(isset($tab['jobs'][$shortname])?'checked':''),$i);
		
		$ret .= sprintf('<td><input type="text" size="2" name="min[]" value="%s" /></td>',
			(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['min']:'0'));

		$vals = array('*'=>'*',0=>'12AM');
		for($i=1;$i<24;$i++){
			$vals[$i] = (($i>12)?($i-12):$i) . (($i>11)?'PM':'AM');
		}
		$ret .= '<td><select name="hour[]">';
		$matched = False;
		foreach($vals as $k=>$v){
			$ret .= sprintf('<option value="%s"',$k);
			if ("$k" == (isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['hour']:'0')){
				$ret .= ' selected';
				$matched = True;
			}
			$ret .= '>'.$v.'</option>';
		}
		$ret .= '</select></td>';
		if (!$matched) return False;

		$vals = array('*'=>'*');
		for($i=1;$i<32;$i++){
			$vals[$i] = $i;
		}
		$ret .= '<td><select name="day[]">';
		$matched = False;
		foreach($vals as $k=>$v){
			$ret .= sprintf('<option value="%s"',$k);
			if ("$k" == (isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['day']:'1')){
				$ret .= ' selected';
				$matched = True;
			}
			$ret .= '>'.$v.'</option>';
		}
		$ret .= '</select></td>';
		if (!$matched) return False;

		$vals = array('*'=>'*');
		for($i=1;$i<13;$i++){
			$vals[$i] = date('M',mktime(0,0,0,$i,1,2000));
		}
		$ret .= '<td><select name="month[]">';
		$matched = False;
		foreach($vals as $k=>$v){
			$ret .= sprintf('<option value="%s"',$k);
			if ("$k" === (isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['month']:'1')){
				$ret .= ' selected';
				$matched = True;
			}
			$ret .= '>'.$v.'</option>';
		}
		$ret .= '</select></td>';
		if (!$matched) return False;

		$vals = array('*'=>'*');
		$ts = time();
		while(date('w',$ts) != 0)
			$ts = mktime(0,0,0,date('n',$ts),date('j',$ts)+1,date('Y'));
		for($i=0;$i<7;$i++){
			$vals[$i] = date('D',$ts);
			$ts = mktime(0,0,0,date('n',$ts),date('j',$ts)+1,date('Y'));
		}
		$ret .= '<td><select name="wkdy[]">';
		$matched = False;
		foreach($vals as $k=>$v){
			$ret .= sprintf('<option value="%s"',$k);
			if ("$k" === (isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['wkdy']:'*')){
				$ret .= ' selected';
				$matched = True;
			}
			$ret .= '>'.$v.'</option>';
		}
		$ret .= '</select></td>';
		if (!$matched) return False;

		$ret .= sprintf('
			<td><input type="hidden" name="cmd[]" value="%s" />
			<a href="" onclick="window.open(\'help.php?fn=%s\',\'Help\',\'height=200,width=500,scrollbars=1\');return false;" title="Help">%s</a></td>
			</tr>',
			(isset($tab['jobs'][$shortname])?$tab['jobs'][$shortname]['cmd']:$cmd),
			base64_encode($shortname),$nicename
		);

		return $ret;
	}

	function advanced_row($shortname,$nicename,$cmd,$tab,$i){
		// defaults are set as once a year so someone doesn't accidentallly
		// start firing a job off every minute
		return sprintf('<tr>
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
			base64_encode($shortname),$nicename
		);

	}

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
				$arr = $this->scan_scripts($dir."/".$file,$arr);
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
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new CronManagementPage();
	$obj->draw_page();
}

?>
