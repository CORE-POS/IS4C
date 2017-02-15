<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    private $country;
    private $memNum;

    private $msgs = '';

    public function __construct(){
        global $FANNIE_COOP_ID;
        parent::__construct();
        // If saving, set higher priv: members_edit_full
        if (isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto') {
            $this->auth_classes = array('members_edit_full');
            $this->auth_classes[] = 'editmembers';
        }
    }

    function preprocess()
    {
        global $FANNIE_COUNTRY, $FANNIE_MEMBER_MODULES, $FANNIE_OP_DB;

        $this->country = (isset($FANNIE_COUNTRY)&&!empty($FANNIE_COUNTRY))?$FANNIE_COUNTRY:"US";
        $this->memNum = FormLib::get('memNum',False);
        if ($this->memNum !== false) {
            $this->title .= $this->memNum;
            $this->header .= $this->memNum;

            /* start building prev/next links */
            $list = FormLib::get('l');
            list($prevLink, $nextLink) = self::memLinksPrevNext($this->memNum, $list);

            if (!empty($prevLink))
                $this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$prevLink;
            if (!empty($nextLink))
                $this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$nextLink;
            $this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<a href="javascript:history.back();">Back</a>';
            $this->header .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<a href="MemberSearchPage.php">Find</a>';
            /* end building prev/next links */

            /* form was submitted. save input. */
            if (FormLib::get('saveBtn',False) !== False){
                $whichBtn = FormLib::get('saveBtn');
                /** get current account settings for reference **/
                $account = \COREPOS\Fannie\API\member\MemberREST::get($this->memNum);
                \COREPOS\Fannie\API\member\MemberModule::setAccount($account);
                FannieAPI::listModules('COREPOS\Fannie\API\member\MemberModule');
                foreach($FANNIE_MEMBER_MODULES as $mm){
                    if (class_exists($mm)) {
                        $instance = new $mm();
                        $saved = $instance->saveFormData($this->memNum, $account);
                        /**
                          The API return type is changing here. Any un-updated
                          module that still returns a string should not clobber
                          the $account info.
                        */
                        if (is_array($saved)) {
                            $account = $saved;
                        } else {
                            $this->msgs .= $saved;
                        }
                    }
                }
                $post_result = \COREPOS\Fannie\API\member\MemberREST::post($this->memNum, $account);
                if ($post_result['errors'] > 0) {
                    $this->msgs .= 'Error saving account';
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
                    }
                    if (is_array($list)) {
                        $loc .= array_reduce($list, function($c,$i){ return $c.'&l[]='.$i; }, '');
                    }
                    header('Location: '.$loc);
                    return False;
                }
            }
        } else {
            // cannot operate without a member number
            // php sapi check makes page unit-testable
            if (php_sapi_name() !== 'cli') {
                header('Location: MemberSearchPage.php');
            }
            return false;   
        }

        return true;

    // preprocess()
    }

    public static function memLinksPrevNext($card_no, $list=array())
    {
        $prev = false;
        $next = false;
        if (is_array($list) && count($list) > 0) {
            for ($i=0; $i<count($list); $i++) {
                if ($list[$i] == $card_no) {
                    if (isset($list[$i-1])) {
                        $prev = $list[$i-1];
                    }
                    if (isset($list[$i+1])) {
                        $next = $list[$i+1];
                    }
                    break;
                }
            }
            $ret = array('', '');
            if ($prev != false) {
                $ret[0] = sprintf('<a id="prevLink" href="MemberEditor.php?memNum=%d%s">Prev Match</a>',
                    $prev, array_reduce($list, function($c,$i){ return $c . '&l[]=' . $i; }, '')
                );
            }
            if ($next != false) {
                $ret[1] = sprintf('<a id="nextLink" href="MemberEditor.php?memNum=%d%s">Next Match</a>',
                    $next, array_reduce($list, function($c,$i){ return $c . '&l[]=' . $i; }, '')
                );
            }

            return $ret;
        } else {
            $prev = \COREPOS\Fannie\API\member\MemberREST::prevAccount($card_no);
            $next = \COREPOS\Fannie\API\member\MemberREST::nextAccount($card_no);
            
            $ret = array('', '');
            if ($prev != false) {
                $ret[0] = sprintf('<a id="prevLink" href="MemberEditor.php?memNum=%d">Prev</a>',
                    $prev
                );
            }
            if ($next != false) {
                $ret[1] = sprintf('<a id="nextLink" href="MemberEditor.php?memNum=%d">Next</a>',
                    $next
                );
            }
            
            return $ret;
        }
    }

    function body_content()
    {
        $ret = '';
        $list = FormLib::get('l');

        $ret .= '<form action="MemberEditor.php" method="post">';
        $ret .= sprintf('<input type="hidden" name="memNum" value="%d" />',$this->memNum);
        if (is_array($list)) {
            foreach($list as $l)
                $ret .= sprintf('<input type="hidden" name="l[]" value="%d" />',$l);
        }
        $load = array();
        $editJS = '';
        $ret .= '<div class="container-fluid">
            <div id="alert-area">';
        if (!empty($this->msgs)){
            $ret .= '<div class="alert alert-danger">' . $this->msgs . '</div>';
        }
        $current_width = 100;
        $account = \COREPOS\Fannie\API\member\MemberREST::get($this->memNum);
        \COREPOS\Fannie\API\member\MemberModule::setAccount($account);
        FannieAPI::listModules('COREPOS\Fannie\API\member\MemberModule');
        foreach ($this->config->get('MEMBER_MODULES') as $mm) {
            if (!class_exists($mm)) {
                continue;
            }
            $instance = new $mm();
            if ($current_width + $instance->width() > 100) {
                $ret .= '</div>' . "\n"
                    . '<div class="row">';
                $current_width = 0;
            }
            switch ($instance->width()) {
                case \COREPOS\Fannie\API\member\MemberModule::META_WIDTH_THIRD:
                    $ret .= '<div class="col-sm-4">' . "\n";
                    break;
                case \COREPOS\Fannie\API\member\MemberModule::META_WIDTH_HALF:
                    $ret .= '<div class="col-sm-6">' . "\n";
                    break;
                case \COREPOS\Fannie\API\member\MemberModule::META_WIDTH_FULL:
                default:
                    $ret .= '<div class="col-sm-12">' . "\n";
                    break;
            }
            $ret .= $instance->showEditForm($this->memNum, $this->country);
            $ret .= '</div>';
            $current_width += $instance->width();
            $editJS .= $instance->getEditJavascript();
            foreach ($instance->getEditLoadCommands() as $cmd) {
                $load[] = $cmd;
            }
        }
        $ret .= '</div>'; // close last module row
        $ret .= '</div>'; // close fluid-container
        $ret .= '<p>';
        if (is_array($list)) {
            $ret .= '<button type="submit" name="saveBtn" value="Save &amp; Next"
                class="btn btn-default">Save &amp; Next</button>';
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        $ret .= '<button type="submit" name="saveBtn" value="Save" 
            class="btn btn-default btn-core">Save</button>';
        $ret .= '<button type="reset" class="btn btn-default btn-reset">Reset Form</button>';
        $ret .= '</p>';
        $ret .= '</form>';

        if ($editJS != '') {
            $ret .= '<script type="text/javascript">' . $editJS . '</script>';
        }
        foreach ($load as $cmd) {
            $this->add_onload_command($cmd);
        }

        return $ret;
    }

    public function helpContent()
    {
        return '<p>View and edit a member account. The exact fields shown here
            vary depending on local configuration. Add or remove sets of fields
            on the <em>Members</em> tab of Fannie\'s install/config page.</p>
            <p>If you arrived here from a search with multiple results, the
            <em>Prev Match</em> and <em>Next Match</em> links will navigate
            through that result set. Similarly, the <em>Save &amp; Next</em>
            button will save the current member and proceed to the next.</p>';
    }

    public function unitTest($phpunit)
    {
        $modules = FannieAPI::listModules('\COREPOS\Fannie\API\member\MemberModule');
        $this->config->set('FANNIE_MEMBER_MODULES', $modules);
        $this->memNum = 1;
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

