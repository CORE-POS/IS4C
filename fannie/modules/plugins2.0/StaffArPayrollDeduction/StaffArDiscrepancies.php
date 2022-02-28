<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

/**
*/
class StaffArDiscrepancies extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: Payroll Deductions';
    public $description = '[Accounts] sets which accounts will have deductions for AR
    payments as well as the amounts.';
    public $themed = true;
    protected $header = 'Payroll Account Discrepancies';
    protected $title = 'Payroll Account Discrepancies';

    protected function get_view()
    {
        $hasBalR = $this->connection->query("
            SELECT c.CardNo, c.LastName, c.FirstName
            FROM custdata AS c
            WHERE memType IN (3,9)
                AND Balance > 0
                AND personNum=1
                AND CardNo NOT IN (
                SELECT card_no FROM " . FannieDB::fqn('StaffArAccounts', 'plugin:StaffArPayrollDB') . "
            )");
        $table1 = '';
        while ($row = $this->connection->fetchRow($hasBalR)) {
            $table1 .= sprintf('<tr><td>#%d</td><td>%s, %s</td></tr>',
                $row['CardNo'], $row['LastName'], $row['FirstName']);
        }

        $nonStaffR = $this->connection->query("
            SELECT c.CardNo, c.LastName, c.FirstName
            FROM custdata AS c
            WHERE memType NOT IN (3,9)
                AND personNum=1
                AND CardNo IN (
                SELECT card_no FROM " . FannieDB::fqn('StaffArAccounts', 'plugin:StaffArPayrollDB') . "
            )");
        $table2 = '';
        while ($row = $this->connection->fetchRow($nonStaffR)) {
            $table2 .= sprintf('<tr><td>#%d</td><td>%s, %s</td></tr>',
                $row['CardNo'], $row['LastName'], $row['FirstName']);
        }

        return <<<HTML
<h3>Accounts w/ Balances not enabled for Payroll</h3>
<table class="table table-bordered">
    {$table1}
</table>
<h3>Non-staff accounts still enabled for Payroll</h3>
<table class="table table-bordered">
    {$table2}
</table>
HTML;
    }
}

FannieDispatch::conditionalExec();

