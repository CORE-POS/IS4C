<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN

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
include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');
include('MemberModule.php');

$dbc = FannieDB::get($FANNIE_OP_DB);

class MemberEditor extends FanniePage {
	protected $title = "Fannie :: Member "; 
	protected $header = "Member ";

	private $country;
	private $memNum;

	private $msgs = '';

	function preprocess(){
		global $FANNIE_COUNTRY, $FANNIE_MEMBER_MODULES;

		$this->country = (isset($FANNIE_COUNTRY)&&!empty($FANNIE_COUNTRY))?$FANNIE_COUNTRY:"US";
		$this->memNum = FormLib::get_form_value('memNum',False);
		if ($this->memNum !== False){
			$this->title .= $this->memNum;
			$this->header .= $this->memNum;

			/* form was submitted. save input. */
			if (FormLib::get_form_value('saveBtn',False) !== False){
				foreach($FANNIE_MEMBER_MODULES as $mm){
					if (!class_exists($mm))
						include('modules/'.$mm.'.php');
					$instance = new $mm();
					$this->msgs .= $instance->SaveFormData($this->memNum);
				}
				if (!empty($this->msgs))
					$this->msgs .= '<hr />';
			}
		}
		else {
			// cannot operate without a member number
			header('Location: MemberSearchPage.php');
			return False;	
		}
		return True;
	}

	function body_content(){
		global $FANNIE_MEMBER_MODULES;
		$ret = '';
		if (!empty($this->msgs)){
			$ret .= $this->msgs;
		}
		$ret .= '<form action="MemberEditor.php" method="post">';
		$ret .= sprintf('<input type="hidden" name="memNum" value="%d" />',$this->memNum);
		foreach($FANNIE_MEMBER_MODULES as $mm){
			if (!class_exists($mm))
				include('modules/'.$mm.'.php');
			$instance = new $mm();
			$ret .= '<div style="float:left;">';
			$ret .= $instance->ShowEditForm($this->memNum, $this->country);
			$ret .= '</div>';
		}
		$ret .= '<div style="clear:left;"></div>';
		$ret .= '<hr />';
		$ret .= '<input type="submit" name="saveBtn" value="Save Changes" />';
		$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$ret .= '<input type="reset" value="Undo Changes" />';
		$ret .= '</form>';
		return $ret;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new MemberEditor();
	$obj->draw_page();
}

?>
