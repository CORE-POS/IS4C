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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemberEditor extends FanniePage {

    /** list of either auth_class(es) or array(auth_class, start, end) tuple(s) */
    /* 19May13 Wait on this til better established.
    protected $auth_classes = array('members_view');
    */
    protected $title = "Fannie :: Member "; 
    protected $header = "Member ";

    public $description = '[Member Editor] is the primary tool for viewing and editing member accounts.';

    private $country;
    private $memNum;

    private $msgs = '';

    /*
    */
  public function __construct(){
    global $FANNIE_COOP_ID;
        parent::__construct();
        // If saving, set higher priv: members_edit_full
        $this->auth_classes = array('members_edit_full');
    if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' )
        $this->auth_classes[] = 'editmembers';
  }

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
                $prevP = $dbc->prepare_statement('SELECT MAX(CardNo) AS prev
                                                  FROM custdata 
                                                  WHERE CardNo < ?');
                $prevR = $dbc->exec_statement($prevP,array($this->memNum));
                if ($dbc->num_rows($prevR) > 0) {
                    $prevW = $dbc->fetch_row($prevR);
                    $prev = $prevW['prev'];
                }
                $nextP = $dbc->prepare_statement('SELECT MIN(CardNo) AS next 
                                                  FROM custdata 
                                                  WHERE CardNo > ?');
                $nextR = $dbc->exec_statement($nextP,array($this->memNum));
                if ($dbc->num_rows($nextR) > 0) {
                    $nextW = $dbc->fetch_row($nextR);
                    $next = $nextW['next'];
                }
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
            $this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<a href="javascript:history.back();">Back</a>';
            $this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<a href="MemberSearchPage.php">Find</a>';
            /* end building prev/next links */

            /* form was submitted. save input. */
            if (FormLib::get_form_value('saveBtn',False) !== False){
                $whichBtn = FormLib::get_form_value('saveBtn');
                foreach($FANNIE_MEMBER_MODULES as $mm){
                    if (!class_exists($mm))
                        include('modules/'.$mm.'.php');
                    $instance = new $mm();
                    $this->msgs .= $instance->saveFormData($this->memNum);
                }

                $dbc = FannieDB::get($FANNIE_OP_DB);
                $custdata = new CustdataModel($dbc);
                $custdata->CardNo($this->memNum);
                $members = $custdata->find();
                if (is_array($members)) {
                    foreach($members as $m) {
                        $m->pushToLanes();
                    }
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
            // php sapi check makes page unit-testable
            if (php_sapi_name() !== 'cli') {
                header('Location: MemberSearchPage.php');
            }
            return False;   
        }
        return True;

    // preprocess()
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
        $load = array();
        $editJS = '';
        foreach($FANNIE_MEMBER_MODULES as $mm){
            if (!class_exists($mm))
                include('modules/'.$mm.'.php');
            $instance = new $mm();
            $ret .= '<div style="float:left;">';
            $ret .= $instance->showEditForm($this->memNum, $this->country);
            $ret .= '</div>';
            $editJS .= $instance->getEditJavascript();
            foreach ($instance->getEditLoadCommands() as $cmd) {
                $load[] = $cmd;
            }
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
        $ret .= '<input type="reset" value="Reset Form" />';
        $ret .= '</form>';

        if ($editJS != '') {
            $ret .= '<script type="text/javascript">' . $editJS . '</script>';
        }
        foreach ($load as $cmd) {
            $this->add_onload_command($cmd);
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
