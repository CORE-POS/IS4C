<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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

class MemberSearchPage extends FanniePage {
    protected $title = "Fannie :: Find Member";
    protected $header = "Find Members";

    public $description = '[Member Search] finds a member account by name, number, or contact info.';
    public $themed = true;

    private $mode = 'search';
    private $country;
    private $results = array();

    public function __construct()
    {
        global $FANNIE_COOP_ID;
        parent::__construct();
        if (isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto') {
            $this->auth_classes = array('editmembers');
        }
    }

    function preprocess()
    {
        global $FANNIE_COUNTRY,$FANNIE_MEMBER_MODULES,$FANNIE_OP_DB;
        $this->country = (isset($FANNIE_COUNTRY)&&!empty($FANNIE_COUNTRY))?$FANNIE_COUNTRY:"US";

        /* do search */
        if (FormLib::get_form_value('doSearch',False) !== False){
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $num = FormLib::get_form_value('memNum','');

            /* if member number is provided and exists, go
               directly to the result */
            if ($num !== ''){
                $q = $dbc->prepare_statement("SELECT cardno FROM custdata WHERE cardno=?");
                $r = $dbc->exec_statement($q,array($num));
                if ($dbc->num_rows($r) > 0){
                    header("Location: MemberEditor.php?memNum=".$num);
                    return False;
                }
            }

            /* process each available search and merge the
               results */
            FannieAPI::listModules('MemberModule');
            foreach($FANNIE_MEMBER_MODULES as $mm){
                if (class_exists($mm)) {
                    $instance = new $mm();
                    if ($instance->hasSearch()) {
                        $tmp = $instance->getSearchResults();
                        foreach ($tmp as $id => $label) {
                            if (!isset($this->results[$id])) {
                                $this->results[$id] = $label;
                            }
                        }
                    }
                }
            }

            /* if modules find exactly one member, go directly to
               the result */
            if (count($this->results) == 1){
                $num = array_pop(array_keys($this->results));
                header("Location: MemberEditor.php?memNum=".$num);
                return False;
            }

            /* search returned either zero or multiple results */
            $this->mode = 'results';
        }

        return true;
    }

    function body_content()
    {
        $ret = '';
        if ($this->mode == 'search') {
            $this->add_onload_command("\$('input#mn').focus();");
        } elseif ($this->mode == 'results') {
            $ret .= $this->results_content();
        }
        $ret .= $this->form_content();

        return $ret;
    }

    function form_content()
    {
        global $FANNIE_MEMBER_MODULES, $FANNIE_OP_DB;
        $ret = '';

        $review = FormLib::get_form_value('review',False);
        if ($review !== false) {
            $ret .= '<fieldset><legend>Review</legend>';
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $prep = $dbc->prepare_statement('SELECT LastName,FirstName FROM custdata
                    WHERE personNum=1 AND CardNo=?');
            $res = $dbc->exec_statement($prep,array($review));
            $ret .= 'Saved Member #'.$review.' (';
            if ($dbc->num_rows($res) > 0){
                $row = $dbc->fetch_row($res);
                $ret .= $row['FirstName'].' '.$row['LastName'];
            }
            $ret .= ')';
            $ret .= '<br /><a href="MemberEditor.php?memNum='.$review.'">Edit Again</a>';
            $ret .= '</fieldset>';
        }

        $ret .= '<div class="well">
            Enter criteria to find one member or a list members from which to choose.</div>';
        $ret .= '<form action="MemberSearchPage.php" method="get">';
        $ret .= '<div class="container-fluid">';
        $ret .= '<div class="form-group form-inline row">
            <label>Member Number</label>
            <input type="text" name="memNum" id="mn" class="form-control" />
            </div>';
        $searchJS = '';
        $load = array();
        FannieAPI::listModules('MemberModule');
        foreach ($FANNIE_MEMBER_MODULES as $mm) {
            if (class_exists($mm)) {
                $instance = new $mm();
                if ($instance->hasSearch()) {
                    $ret .= $instance->showSearchForm($this->country);
                    $searchJS .= $instance->getSearchJavascript();
                    foreach ($instance->getSearchLoadCommands() as $cmd) {
                        $load[] = $cmd;
                    }
                }
            }
        }
        $ret .= '</div>';
        $ret .= '<p><button type="submit" value="Search" name="doSearch" 
            class="btn btn-default">Search</button></p>';
        $ret .= '</form>';

        $ret .= '<script type="text/javascript" src="../item/autocomplete.js"></script>';
        if ($searchJS != '') {
            $ret .= '<script type="text/javascript">' . $searchJS . '</script>';
        }

        foreach ($load as $cmd) {
            $this->add_onload_command($cmd);
        }

        return $ret;
    }
    
    function results_content()
    {
        $ret = '';
        if (empty($this->results)) {
            $ret .= "<div class=\"alert alert-danger\">Error: No matching member found</div>";
        } else {
            $ret .= '<div class="well"><h3>Multiple Results</h3>';
            $list = '';
            foreach ($this->results as $cn => $name) {
                if (strlen($list) > 1900) break; // avoid excessively long URLs
                $list .= '&l[]='.$cn;
            }
            $ret .= "<ul>";
            foreach ($this->results as $cn => $name) {
                $ret .= "<li><a href=\"MemberEditor.php?memNum=$cn$list\">$cn $name</a></li>";
            }
            $ret .= "</ul></div>";
        }

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Search for member accounts. Member number should always
            yield a single match or not found. Other kinds of searches may
            return multiple members. If there are multiple results, click
            the one you want to view.</p>';
    }
}

FannieDispatch::conditionalExec(false);

?>

