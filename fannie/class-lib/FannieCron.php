<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

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
  @class FannieCron
  Module for building cronjobs
*/
class FannieCron extends FannieModule {

	public $required = True;

	public $description = "
	Base module for building cron jobs.
	";

	/**
	  Display this task in Fannie's task
	  scheduler. Disable for store-specific
	  jobs or jobs that must run as a different
	  user than the web server. Fannie cannot
	  schedule jobs for other users.
	*/
	public $advertised = False;

	protected $logfile = "dayend.log";

	/**
	  Get module's directory.
	  @return string directory path
	*/
	function get_job_directory(){
		return realpath(__DIR__.'/');
	}

	function run_module(){
		set_time_limit(0);
		chdir($this->get_job_directory);
		$this->task();
	}

	/**
	  Define your job here.
	*/
	function task(){
	}

	/**
	  Format string for logging
	  @param $str message
	  @return formatted message

	  Default format prepends datetime and module
	  filename. Override as needed.
	*/
	function cron_msg($str){
		return date('r').': '.$_SERVER['SCRIPT_FILENAME'].': '.$str."\n";
	}

	/**
	  Get command for running module via command line
	  @return command string
	*/
	function schedule_command(){
		global $FANNIE_ROOT;
		$cmd = sprintf("cd %smodules && php index.php -m%s &> %s",
			$FANNIE_ROOT,
			get_class($this),
			$FANNIE_ROOT.'logs/'.$this->log_file
		);

		return $cmd;
	}
}

/**
  @example CronJob.php
  Most jobs will only need to define a task() method. The cron_msg()
  method just adds some formatting to messages.
*/

?>
