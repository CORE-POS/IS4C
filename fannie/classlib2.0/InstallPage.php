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
/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	* 15Apr2013 Eric Lee 
	* - Want it to have the default authentication assignments from FanniePage
	* o Leave window_dressing value but have it mean something different,
	*    less elaborate, by using different header and footer includes.
*/
include_once(dirname(__FILE__).'/FanniePage.php');

/**
  @class InstallPage
  Class for Fannie Install-and-config pages, not using Fannie Admin menu.
*/
class InstallPage extends FanniePage {

	public $required = True;

	public $description = "
	Base class for install-and-config pages not using Admin menu.
	";


	public function __construct() {
		parent::__construct();
	}

	/**
	  Get the standard install-page header
	  @return An HTML string
	*/
	function get_header(){
		global $FANNIE_ROOT;
		ob_start();
		$page_title = $this->title;
		$header = $this->header;
		include($FANNIE_ROOT.'src/header_install.html');
		return ob_get_clean();

	}

	/**
	  Get the standard install-page footer
	  @return An HTML string
	*/
	function get_footer(){
		global $FANNIE_ROOT, $FANNIE_AUTH_ENABLED, $FANNIE_URL;
		ob_start();
		include($FANNIE_ROOT.'src/footer_install.html');
		return ob_get_clean();
	}

}

?>
