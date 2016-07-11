<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
// A page to search the member base.
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class InactiveReasonEditor extends FannieRESTfulPage 
{
    protected $title = 'Inactive Account Reasons';
    protected $header = 'Inactive Account Reasons';
    public $description = '[Inactive Account Reasons] edits the list of reason a customer account
    can be inactive or terminated.';
    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    public function preprocess()
    {
        $this->addRoute('post<mask><reason>');

        return parent::preprocess();
    }

    public function post_mask_reason_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $code = new ReasoncodesModel($dbc);

        for ($i=0; $i<count($this->mask); $i++) {
            $code->mask($this->mask[$i]);
            $reason = trim($this->reason[$i]);
            if ($reason === '') {
                $code->delete();
            } else {
                $code->textStr($reason);
                $code->save();
            }
        }

        return $_SERVER['PHP_SELF'];
    }

    public function get_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $code = new ReasoncodesModel($dbc);
        $ret = '<form method="post">
            <p><button type="submit" class="btn btn-default">Save Reasons</button></p>
            <table class="table table-bordered">
            <tr>
                <th>#</th>
                <th>Reason</th>
                <th>Current Accounts</th>
            </tr>';
        $countP = $dbc->prepare('
            SELECT COUNT(*)
            FROM suspensions
            WHERE (reasoncode & ?) <> 0
        ');
        for ($i=0; $i<30; $i++) {
            $code->mask(1<<$i);
            $count = $dbc->getValue($countP, array(1<<$i));
            $reason = $code->load() ? $code->textStr() : '';
            $ret .= sprintf('<tr>
                <td>%d<input type="hidden" name="mask[]" value="%d" /></td>
                <td><input type="text" class="form-control" name="reason[]" value="%s" />
                <td>%d</td>
                </tr>',
                $i+1, 1<<$i,
                $reason,
                $count
            );
        }
        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default">Save Reasons</button></p>';
        $ret .= '</form>';

        return $ret;
    }

    public function helpContent()
    {
        return '
            <p>
            When a customer account is set to an inactive
            or terminated status, one or more reasons should
            be provided to indicate why the status change 
            occurred. You may specify up to 30 reasons.
            </p>
            <p>
            The Current Accounts column shows the number of
            accounts that are currently labeled with that
            reason.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }

}

FannieDispatch::conditionalExec();

