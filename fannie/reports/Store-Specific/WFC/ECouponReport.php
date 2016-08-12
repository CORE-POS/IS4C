<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class ECouponReport extends FannieRESTfulPage
{
    protected $header = 'WFC Coupon Reporting';
    protected $title = 'WFC Coupon Reporting';

    protected function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $res = $dbc->query('
            SELECT coupID
            FROM houseCoupons
            WHERE description LIKE \'%APPRECIATION%\'
        ');
        $oam = array();
        while ($row = $dbc->fetchRow($res)) {
            $oam[] = $row['coupID'];
        }

        $res = $dbc->query('
            SELECT coupID
            FROM houseCoupons
            WHERE description LIKE \'%ECOUPON%\'
                OR description LIKE \'%TAST-E%\'
        ');
        $ecoup = array();
        while ($row = $dbc->fetchRow($res)) {
            $ecoup[] = $row['coupID'];
        }

        $id2upc = function ($id) { return '00499999' . str_pad($id, 5, '0', STR_PAD_LEFT); };
        $oam = array_map($id2upc, $oam);
        $ecoup = array_map($id2upc, $ecoup);

        $hidden = function($carry, $upc) { 
            return sprintf('%s<input type="hidden" name="u[]" value="%s" />', $carry, $upc);
        };
        $oam = array_reduce($oam, $hidden);
        $ecoup = array_reduce($ecoup, $hidden);
        $dates = FormLib::standardDateFields();
        $dates2 = str_replace('id="date1"', 'id="adate1"', $dates);
        $dates2 = str_replace('id="date2"', 'id="adate2"', $dates2);
        $dates2 = str_replace('#date1', '#adate1', $dates2);
        $dates2 = str_replace('#date2', '#adate2', $dates2);

        $dates3 = str_replace('id="date1"', 'id="bdate1"', $dates);
        $dates3 = str_replace('id="date2"', 'id="bdate2"', $dates3);
        $dates3 = str_replace('#date1', '#bdate1', $dates3);
        $dates3 = str_replace('#date2', '#bdate2', $dates3);

return <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">Owner Appreciation Month</div>
    <div class="panel-body">
        <form action="../../DepartmentMovement/SmartMovementReport.php" method="post">
            {$dates} 
            {$oam}
            <input type="hidden" name="lookup-type" value="u" />
            <input type="hidden" name="store" value="0" />
            <p>
                <button type="submit" class="btn btn-default">Get Report</button>
            </p>
        </form>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">E-Coupons</div>
    <div class="panel-body">
        <form action="../../DepartmentMovement/SmartMovementReport.php" method="post">
            {$dates2} 
            {$ecoup}
            <input type="hidden" name="lookup-type" value="u" />
            <input type="hidden" name="store" value="0" />
            <p>
                <button type="submit" class="btn btn-default">Get Report</button>
            </p>
        </form>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Combined</div>
    <div class="panel-body">
        <form action="../../DepartmentMovement/SmartMovementReport.php" method="post">
            {$dates3} 
            {$oam}
            {$ecoup}
            <input type="hidden" name="lookup-type" value="u" />
            <input type="hidden" name="store" value="0" />
            <p>
                <button type="submit" class="btn btn-default">Get Report</button>
            </p>
        </form>
    </div>
</div>
HTML;
    }
}

FannieDispatch::conditionalExec();

