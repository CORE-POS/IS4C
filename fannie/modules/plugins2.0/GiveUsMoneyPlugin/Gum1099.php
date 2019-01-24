<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class Gum1099 extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Generic 1099] creates 1099 forms';

    protected $header = '1099 Generator';
    protected $title = '1099 Generator';

    protected function post_handler()
    {
        $custdata = new CustdataModel($this->connection);
        $meminfo = new MeminfoModel($this->connection);
        $custdata->LastName(FormLib::get('name'));
        $meminfo->street(FormLib::get('addr'));
        $meminfo->city(FormLib::get('city'));
        $meminfo->state(FormLib::get('state'));
        $meminfo->zip(FormLib::get('zip'));

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $class = 'GumTaxMiscFormTemplate';
        $fields = array();
        $amount = FormLib::get('amount');
        switch (FormLib::get('type')) {
            case 'DIV':
                $class = 'GumTaxDividendFormTemplate';
                $fields[1] = $amount;
                break;
            case 'INT':
                $class = 'GumTaxFormTemplate';
                $fields[1] = $amount;
                break;
            default:
                $fields[3] = $amount;
                $fields[18] = $amount;
                break;
        }
        $form = new $class($custdata, $meminfo, FormLib::get('tid'), FormLib::get('year'), $fields);
        $form->renderAsPDF($pdf, 5);
        $form->renderAsPDF($pdf, 90);
        $form->renderAsPDF($pdf, 175);
        $pdf->Output('1099.pdf', 'I');

        return false;
    }

    protected function get_view()
    {
        $year = date('Y');
        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Name</label>
        <input type="text" class="form-control" name="name" />
    </div>
    <div class="form-group">
        <label>Address</label>
        <input type="text" class="form-control" name="addr" />
    </div>
    <div class="form-group">
        <label>City</label>
        <input type="text" class="form-control" name="city" />
    </div>
    <div class="form-group">
        <label>State</label>
        <input type="text" class="form-control" name="state" />
    </div>
    <div class="form-group">
        <label>Zip</label>
        <input type="text" class="form-control" name="zip" />
    </div>
    <div class="form-group">
        <label>Tax Year</label>
        <input type="text" class="form-control" name="year" value="{$year}" />
    </div>
    <div class="form-group">
        <label>Type</label>
        <select class="form-control" name="type">
            <option>MISC</option>
            <option>DIV</option>
            <option>INT</option>
        </select>
    </div>
    <div class="form-group">
        <label>Amount</label>
        <input type="text" class="form-control" name="amount"  />
    </div>
    <div class="form-group">
        <label>Tax ID</label>
        <input type="text" class="form-control" name="tid"  />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Generate Form</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

