<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EmailReport extends FannieRESTfulPage 
{
    protected $header = "Email List";
    protected $title = "Fannie :: Email List";
    public $themed = true;

    public $description = '[Email Report] lists email addresses for members by member type.';

    public function post_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $tstr = "(";
        $args = array();
        foreach ($_REQUEST['types'] as $t) {
            $tstr .= "?,";
            $args[] = (int)$t;
        }
        $tstr = rtrim($tstr,",").")";

        $q = "SELECT m.email_1 FROM
            meminfo AS m LEFT JOIN
            memContact as c ON m.card_no=c.card_no
            LEFT JOIN custdata AS a
            ON m.card_no=a.CardNo AND a.personNum=1";
        if (isset($_REQUEST['inactives'])){
            $q .= " LEFT JOIN suspensions AS s ON
                m.card_no=s.cardno";
        }
        $q .= " WHERE ";
        if (isset($_REQUEST['all']) && $_REQUEST['all'] != 'All Accounts')
            $q .= "c.pref IN (2) AND ";
        $q .= "(a.memType IN $tstr ";
        if (isset($_REQUEST['inactives'])){
            $q .= "OR (s.memType1 IN $tstr AND s.type='I')";
            /* double up arguments */
            $temp = $args;
            foreach($args as $a) $temp[] = $a;
            $args = $temp;
        }
        $q .= ") AND email_1 LIKE '%@%.%'";
        $p = $dbc->prepare($q);
        $r = $dbc->execute($p,$args);

        $ret = '<p>Matched '.$dbc->num_rows($r).' accounts';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="button" class="btn btn-default"
            onclick="$(\'#emailListing\').focus();$(\'#emailListing\').select();"
            >Select All</button></p>';
        $ret .= '<div class="form-group">
            <textarea id="emailListing" rows="15" class="form-control">';
        while ($w = $dbc->fetch_row($r)) {
            $ret .= $w[0]."\n";
        }   
        $ret .= '</textarea></div>';

        return $ret;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<div class="col-sm-4">
            <div class="panel panel-default">
                <div class="panel-heading">Include Types</div>
                <div class="panel-body">';
        $p = $dbc->prepare("SELECT memtype,memDesc FROM memtype ORDER BY memtype");
        $r = $dbc->execute($p);
        while ($w = $dbc->fetch_row($r)) {
            $ret .= sprintf('<label><input type="checkbox" value="%d" name="types[]" /> %s</label><br />',
                $w['memtype'],$w['memDesc']);
        }
        $ret .= '</div></div></div>';
        $ret .= '<div class="col-sm-4">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="inactives" />
                        Include Inactive Accounts
                    </label>
                </div>
                <div class="form-group">
                    <select class="form-control" name="all">
                        <option>All Accounts</option>
                        <option>Accounts that prefer Email</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-default">Get Emails</button>
                </div>
            </div>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Choose a member type or types. Including inactive accounts
            will use a member\'s type from the last time they were
            active instead of their current type. The <em>prefer
            email</em> option will only work if that field is being
            maintained for member accounts.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

