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
		global $FANNIE_COUNTRY, $FANNIE_MEMBER_MODULES, $FANNIE_OP_DB;

		$this->country = (isset($FANNIE_COUNTRY)&&!empty($FANNIE_COUNTRY))?$FANNIE_COUNTRY:"US";
		$this->memNum = FormLib::get_form_value('memNum',False);
		if ($this->memNum !== False){
			$this->title .= $this->memNum;
			$this->header .= $this->memNum;

			/* start building prev/next links */
			$prev = ''; $prevLink='';
			$next = ''; $nextLink='';
			$list = FormLib::get_form_value('l');
			if (is_array($list)){
				// list mode
				for($i=0;$i<count($list);$i++){
					if ($list[$i] == $this->memNum){
						if (isset($list[$i-1]))
							$prev = $list[$i-1];
						if (isset($list[$i+1]))
							$next = $list[$i+1];
					}
				}
			}
			else {
				$dbc = FannieDB::get($FANNIE_OP_DB);
				$prevP = $dbc->prepare_statement('SELECT MAX(CardNo) FROM custdata WHERE CardNo < ?');
				$prevR = $dbc->exec_statement($prevP,array($this->memNum));
				if ($dbc->num_rows($prevR) > 0)
					$prev = array_pop($dbc->fetch_row($prevR));
				$nextP = $dbc->prepare_statement('SELECT MIN(CardNo) FROM custdata WHERE CardNo > ?');
				$nextR = $dbc->exec_statement($nextP,array($this->memNum));
				if ($dbc->num_rows($nextR) > 0)
					$next = array_pop($dbc->fetch_row($nextR));
			}

			if ($prev != ''){
				$prevLink = '<a id="prevLink" href="MemberEditor.php?memNum='.$prev;
				if (is_array($list)){
					foreach($list as $l) $prevLink .= '&l[]='.$l;	
				}
				$prevLink .= '">';
				$prevLink .= (is_array($list)) ? 'Prev Match' : 'Prev';
				$prevLink .= '</a>';
			}
			if ($next != ''){
				$nextLink = '<a id="nextLink" href="MemberEditor.php?memNum='.$next;
				if (is_array($list)){
					foreach($list as $l) $nextLink .= '&l[]='.$l;
				}
				$nextLink .= '">';
				$nextLink .= (is_array($list)) ? 'Next Match' : 'Next';
				$nextLink .= '</a>';
			}

			if (!empty($prevLink))
				$this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$prevLink;
			if (!empty($nextLink))
				$this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$nextLink;
			/* end building prev/next links */

			/* form was submitted. save input. */
			if (FormLib::get_form_value('saveBtn',False) !== False){
				$whichBtn = FormLib::get_form_value('saveBtn');
				foreach($FANNIE_MEMBER_MODULES as $mm){
					if (!class_exists($mm))
						include('modules/'.$mm.'.php');
					$instance = new $mm();
					$this->msgs .= $instance->SaveFormData($this->memNum);
				}

				if (!empty($this->msgs)){
					// implies: errors occurred
					// stay on this page
					$this->msgs .= '<hr />';
				}
				else {
					// By default, go back to search page w/ review info.
					// If user clicked Save & Next and another match is
					// available, edit the next match
					$loc = 'MemberSearchPage.php?review='.$this->memNum;
					if($whichBtn == 'Save & Next' && !empty($next)){
						$loc = 'MemberEditor.php?memNum='.$next;
						foreach($list as $l)
							$loc .= '&l[]='.$l;
					}
					header('Location: '.$loc);
					return False;
				}
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

		$list = FormLib::get_form_value('l');

		$ret .= '<form action="MemberEditor.php" method="post">';
		$ret .= sprintf('<input type="hidden" name="memNum" value="%d" />',$this->memNum);
		if (is_array($list)){
			foreach($list as $l)
				$ret .= sprintf('<input type="hidden" name="l[]" value="%d" />',$l);
		}
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
		if (is_array($list)){
			$ret .= '<input type="submit" name="saveBtn" value="Save" />';
			$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			$ret .= '<input type="submit" name="saveBtn" value="Save &amp; Next" />';
		}
		else
			$ret .= '<input type="submit" name="saveBtn" value="Save" />';
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
