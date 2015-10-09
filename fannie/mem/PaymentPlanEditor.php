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

class PaymentPlanEditor extends FannieRESTfulPage 
{
    protected $title = 'Payment Plan Editor';
    protected $header = 'Payment Plan Editor';
    public $description = '[Payment Plan Editor] defines equity payment schedules.';
    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    public function preprocess()
    {
        $this->addRoute('post<id><name><final><initial><recurring><cycle><basis><overdue><reason>');

        return parent::preprocess();
    }

    public function post_id_name_final_initial_recurring_cycle_basis_overdue_reason_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $plan = new EquityPaymentPlansModel($dbc);
        $plan->equityPaymentPlanID($this->id);
        $plan->name($this->name);
        $plan->finalBalance($this->final);
        $plan->initialPayment($this->initial);
        $plan->recurringPayment($this->recurring);
        $plan->billingCycle($this->cycle);
        $plan->dueDateBasis($this->basis);
        $plan->overDueLimit($this->overdue);
        $plan->reasonMask($this->reason);
        $plan->save();

        return filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $this->id;
    }

    public function put_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $plan = new EquityPaymentPlansModel($dbc);
        $plan->name('NEW PLAN');
        $plan->initialPayment(20);
        $plan->recurringPayment(80);
        $plan->finalBalance(100);
        $planID = $plan->save();
        if ($planID !== false) {
            return filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $planID;
        }

        return true;
    }

    public function put_view()
    {
        return '<div class="alert alert-danger">Error: failed to create new plan</div>'
            . $this->get_view();
    }

    public function get_id_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $codes = new ReasoncodesModel($dbc);
        $plan = new EquityPaymentPlansModel($dbc);
        $plan->equityPaymentPlanID($this->id);
        if (!$plan->load()) {
            return '<div class="alert alert-danger">Error: plan does not exist</div>';
        }
        
        $ret = '<script type="text/javascript">
        function validateBillingCycle()
        {
            var val = $(\'input[name=cycle]\').val();
            if (val.match(/^\s*\d+[dDwWmMyY]\s*$/)) {
                $(\'#billing-cycle-valid\').addClass(\'collapse\');
                return true;
            } else {
                $(\'#billing-cycle-valid\').removeClass(\'collapse\');
                $(\'#billing-cycle-valid\').html(\'<div class="alert alert-danger">Invalid Entry</div>\');
                $(\'input[name=cycle]\').focus();
                return false;
            }
        }
        </script>';
        $ret .= '<form method="post" onsubmit="return validateBillingCycle();">';

        $ret .= sprintf('
            <div class="form-group">
                <label>Name</label>
                <input class="form-control" type="text" name="name" value="%s" />
            </div>', $plan->name());

        $ret .= sprintf('
            <div class="form-group">
                <label>Total Equity Required</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" class="form-control" name="final" value="%.2f" />
                </div>
            </div>', $plan->finalBalance());

        $ret .= sprintf('
            <div class="form-group">
                <label>Initial Payment</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" class="form-control" name="initial" value="%.2f" />
                </div>
            </div>', $plan->initialPayment());

        $ret .= sprintf('
            <div class="form-group">
                <label>Recurring Payment</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" class="form-control" name="recurring" value="%.2f" />
                </div>
            </div>', 
            $plan->recurringPayment()
        );

        $ret .= sprintf('
            <div class="form-group">
                <div id="billing-cycle-valid" class="collapse"></div>
                <label>Billing Cycle %s</label>
                <input class="form-control" type="text" name="cycle" value="%s" onchange="validateBillingCycle();" />
            </div>', 
            \COREPOS\Fannie\API\lib\FannieHelp::toolTip('Enter a number followed by D, W, M, or Y for days, weeks, months, or years'),
            $plan->billingCycle()
        );

        $ret .= sprintf('<div class="form-group">
            <label>Calculate Next Due Date based on</label>
            <select name="basis" class="form-control">
                <option value="0" %s>Join Date</option>
                <option value="1" %s>Date of Last Equity Payment</option>
            </select>
            </div>',
            ($plan->dueDateBasis() == 0 ? 'selected' : ''),
            ($plan->dueDateBasis() == 1 ? 'selected' : '')
        );

        $ret .= sprintf('
            <div class="form-group">
                <label>Overdue Limit (in days)</label>
                <input class="form-control" type="text" name="overdue" value="%d" />
            </div>', 
            $plan->overDueLimit()
        );

        $ret .= '<div class="form-group">
            <label>Reason Label for Overdue Accounts</label>
            <select class="form-control" name="reason">';
        $ret .= $codes->toOptions($plan->reasonMask());
        $ret .= '</select></div>';

        $ret .= sprintf('<input type="hidden" name="id" value="%d" />', $plan->equityPaymentPlanID());

        $ret .= '<p><button type="submit" class="btn btn-default">Save Plan</button></p>
            </form>';

        return $ret;
    }

    public function get_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $plans = new EquityPaymentPlansModel($dbc);
        $ret = '<table class="table table-bordered">
            <tr>
                <th>Name</th>
                <th>Payment Amount</th>
                <th>Frequency</th>
            </tr>';
        foreach ($plans->find('name') as $plan) {
            $ret .= sprintf('
                <tr>
                    <td>%s</td>
                    <td>%.2f</td>
                    <td>%s</td>
                    <td><a class="btn btn-default btn-xs" href="%s?id=%d">%s</a></td>
                </tr>',
                $plan->name(),
                $plan->recurringPayment(),
                $plan->billingCycle(),
                filter_input(INPUT_SERVER, 'PHP_SELF'), $plan->equityPaymentPlanID(),
                \COREPOS\Fannie\API\lib\FannieUI::editIcon()
            );
        }
        $ret .= '</table>';
        $ret .= '<p>
            <a href="?_method=put" class="btn btn-default">Create New Plan</a>
            </p>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $values = new \COREPOS\common\mvc\ValueContainer();
        $values->_method = 'get';
        $this->setForm($values);
        $this->readRoutes();
        
        $page = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($page));

        $values->_method = 'put';
        $this->setForm($values);
        $this->readRoutes();
        $this->put_handler();

        $values->_method = 'get';
        $this->setForm($values);
        $this->readRoutes();

        $newpage = $this->get_view();
        $phpunit->assertNotEquals($newpage, $page);

        $model = new EquityPaymentPlansModel($this->connection);
        $model->equityPaymentPlanID(1);
        $phpunit->assertEquals(true, $model->load());

        $values->_method = 'get';
        $values->id = 1;
        $this->setForm($values);
        $this->readRoutes();
        $page = $this->get_id_view();
        $phpunit->assertEquals(false, strstr($page, 'plan does not exist'));

        $values->_method = 'post';
        $values->id = 1;
        $values->name = 'Test';
        $values->final = '123';
        $values->initial = '12';
        $values->recurring = '23';
        $values->cycle = '5W';
        $values->basis = 1;
        $values->overdue = 90;
        $values->reason = 4;
        $this->setForm($values);
        $this->readRoutes();
        $this->post_id_name_final_initial_recurring_cycle_basis_overdue_reason_handler();

        $model->reset();
        $model->equityPaymentPlanID(1);
        $model->load();
        $phpunit->assertEquals($values->name, $model->name());
        $phpunit->assertEquals($values->final, $model->finalBalance());
        $phpunit->assertEquals($values->initial, $model->initialPayment());
        $phpunit->assertEquals($values->recurring, $model->recurringPayment());
        $phpunit->assertEquals($values->cycle, $model->billingCycle());
        $phpunit->assertEquals($values->basis, $model->dueDateBasis());
        $phpunit->assertEquals($values->overdue, $model->overDueLimit());
        $phpunit->assertEquals($values->reason, $model->reasonMask());
    }

    public function helpContent()
    {
        return '
            <p>
            A payment plan can be assigned to a member to
            define how much equity they need to pay in total
            and how their payments to reach the total
            should be scheduled.
            </p> 
            <p>
            Elements of a payment plan are:
            <ul>
            <li>Name - this is for organizational purposes if
            more than one plan exists.</li>
            <li>The three equity payment options fit together to
            define the total investment required, the minimum payment
            to get started (the voting share in many jurisdictions),
            and the incremental payments required to reach the total
            investment requirement. Example:
                <ul>
                <li>Total Equity Required is $100.</li>
                <li>Inital Payment is $20.</li>
                <li>Recurring Payment is $10.</li>
                <li>The member makes a $20 payment when opening their 
                account then will make eight additional $10 payments
                to reach $100 in total equity.</li>
                </ul>
            </li>
            <li>Billing Cycle defines how frequently the recurring payment
            is required. This is a number of days, weeks, months, or years.
            Examples:
                <ul>
                <li>1Y => 1 payment per year</li>
                <li>6M => 1 payment every six months</li>
                <li>90D => 1 payment every 90 days</li>
                <li>13W => 1 payment every 13 weeks</li>
                </ul>
            </li>
            <li>Calculate next due date changes how the billing cycle moves.
            For simplicity, assume an annual billing cycle. Using join date
            as the basis for next payment, if a member joins on January 1, 2000
            their next payment will be due on January 1, 2001 then January 1, 2002
            etc. If the basis is instead based on the last equity payment, making
            a payment on December 30, 2000 would move the next due date to December
            30, 2001.
            </li>
            <li>The overdue limit allows accounts to automatically change to an
            inactive status when a payment is missed. Setting a limit of zero
            means accounts will never be automatically disabled. A limit of one or
            more will disable the account after that many days have passed since
            the due date.
            </li>
            <li>Reason label is applied to accounts when deactivated for an overdue
            payment. If accounts are not automatically disabled this setting does
            not do anything.</li>
            </ul>
            </p>';
    }
}

FannieDispatch::conditionalExec();

