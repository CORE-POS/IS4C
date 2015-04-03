<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PatronageChecks extends FannieRESTfulPage
{
    protected $title = "Fannie :: Patronage Checks";
    protected $header = "Fannie :: Patronage Checks";
    public $themed = true;

    public function helpContent()
    {
        return '<p>First, this page requires the GiveUsMoney plugin.
            The settings for check numbering, accounts numbers, and store
            address all come from the plugin as does the PDF check
            template.</p>
            <p>Checks are generated as PDF. They are in order by postal
            code then member name. The PDFs are <em>not</em> sent directly
            to the browser since there are often several PDFs. They will
            be written to /tmp and someone will have to retrieve them.</p>
            <p>The check appears on the lower third of the page and is 
            designed to fit standard check templates. The owner and store
            addresses should align with a two-window envelope. If a file
            named "rebate_body.png" is present, that image will be placed
            on the upper two-thirds of the page.</p>
            <p>This process may take several minutes to finish.</p>';
    }

    public function preprocess()
    {
        $this->__routes[] = 'get<reprint><mem><fy>';

        return parent::preprocess();
    }

    public function get_reprint_mem_fy_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $custdata = new CustdataModel($dbc);
        $meminfo = new MeminfoModel($dbc);
        $custdata->CardNo($this->mem);
        $custdata->personNum(1);
        $custdata->load();
        $meminfo->card_no($this->mem);
        $meminfo->load();
        $patronage = new PatronageModel($dbc);
        $patronage->cardno($this->mem);
        $patronage->FY($this->fy);
        $patronage->load();

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $pdf->Image('rebate_body.png', 10, 0, 190);
        $check = new GumCheckTemplate($custdata, $meminfo, $patronage->cash_pat(), 'Rebate ' . $this->fy, $patronage->check_number());
        $check->renderAsPDF($pdf);

        $pdf->Output('Rebate_' . $this->mem . '_' . $this->fy . '.pdf', 'I');

        return false;
    }

    public function post_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $fy = FormLib::get('fy');
        $per_page = FormLib::get('per_page');

        $custdata = new CustdataModel($dbc);
        $meminfo = new MeminfoModel($dbc);

        $query = $dbc->prepare('
            SELECT p.cardno,
                p.cash_pat,
                m.zip,
                p.check_number
            FROM patronage AS p
                INNER JOIN meminfo AS m ON p.cardno=m.card_no
                INNER JOIN custdata AS c ON p.cardno=c.CardNo AND c.personNum=1
            WHERE p.FY=?
            ORDER BY m.zip,
                c.LastName,
                c.FirstName');
        $result = $dbc->execute($query, array($fy));
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);
        $filename = '';
        $this->files = array();
        $filenumber = 1;
        set_time_limit(0);
        while ($w = $dbc->fetch_row($result)) {
            if (empty($filename)) {
                $filename = $filenumber . '-' . substr($w['zip'], 0, 5);
            }
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $custdata->CardNo($w['cardno']);
            $custdata->personNum(1);
            $custdata->load();
            $meminfo->card_no($w['cardno']);
            $meminfo->load();

            if ($w['check_number'] == '') {
                $patronage = new PatronageModel($dbc);
                $patronage->cardno($w['cardno']);
                $patronage->FY($fy);
                $number = GumLib::allocateCheck($patronage, false);
                $dbc = FannieDB::get($FANNIE_OP_DB);
                $patronage->check_number($number);
                $patronage->save();
                $w['check_number'] = $number;
            }

            $pdf->AddPage();
            $pdf->Image('rebate_body.png', 10, 0, 190);
            $check = new GumCheckTemplate($custdata, $meminfo, $w['cash_pat'], 'Rebate ' . $fy, $w['check_number']);
            $check->renderAsPDF($pdf);
            if ($pdf->PageNo() == $per_page) {
                $filename .= '-' . substr($w['zip'], 0, 5) . '.pdf';
                $filenumber++;
                $pdf->Output('/tmp/' . $filename, 'F');
                $this->files[] = $filename;
                $filename = '';
                $pdf = new FPDF('P', 'mm', 'Letter');
                $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
                $pdf->SetAutoPageBreak(false);
            }
        }

        $filename .= '-End.pdf';
        $pdf->Output('/tmp/' . $filename, 'F');
        $this->files[] = $filename;

        return true;
    }

    public function post_view()
    {
        $ret = '<ul>';
        foreach ($this->files as $f) {
            $ret .= '<li>' . $f . '</li>';
        }
        $ret .= '</ul>';

        return $ret;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">
            <div class="form-group">
                <label>Fiscal Year</label>
                <select class="form-control" name="fy">';
        $result = $dbc->query('SELECT FY FROM patronage GROUP BY FY ORDER BY FY DESC');
        while ($w = $dbc->fetch_row($result)) {
            $ret .= '<option>' . $w['FY'] . '</option>';
        }
        $ret .= '</select>
            </div>
            <div class="form-group">
                <label># of pages per PDF</label>
                <input type="text" class="form-control" name="per_page" value="500" required />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Generate PDF(s)</button>
            </div>
            </form>';
        if (!class_exists('GumCheckTemplate')) {
            $ret .= '<div class="alert alert-danger">Required plugin GiveUsMoney must be enabled</div>';
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();


