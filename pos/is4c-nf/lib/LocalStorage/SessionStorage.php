<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

if (!class_exists("LocalStorage")) include_once(realpath(dirname(__FILE__).'/LocalStorage.php'));

/**
  @class SessionStorage

  A LocalStorage implementation using
  PHP sessions.

  The module will try to start session as
  needed but performance is better if
  session.auto_start is enabled in php.ini.
*/
class SessionStorage extends LocalStorage {
	function SessionStorage(){
		if(ini_get('session.auto_start')==0 && !headers_sent())
                        @session_start();
	}

	function get($key){
		if ($this->is_immutable($key)) return $this->immutables[$key];
		if (!isset($_SESSION["$key"])) return "";
		return $_SESSION["$key"];
	}

	function set($key,$val,$immutable=False){
		if ($immutable)
			$this->immutable_set($key,$val);
		else
			$_SESSION["$key"] = $val;
		$this->debug($key,$val);
	}
}

?>
