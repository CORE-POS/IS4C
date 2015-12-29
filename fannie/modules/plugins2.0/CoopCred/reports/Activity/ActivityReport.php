<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Canada

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
/* TODO
 * 12Sep2015 Handle date-range parameters.
 * 12Sep2015 Run without required parameters return empty page, not form_content.
 * 25Mar2015 Re-run for a different date range:
 *           - form_content() and a link to it OR
 *           - a date formlet in the report page.
 * 25Mar2015 A version of this for any Program, Any member,
 *            i.e. pickers for each each, chained.
 */

include(dirname(__FILE__) . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ActivityReport extends FannieReportPage 
{
    public $themed = true;
    public $description = '[Coop Cred] lists all Coop Cred transactions for a given member in a given program';
    public $report_set = 'CoopCred';
    protected $title = "Fannie : Coop Cred Activity Report";
    protected $header = "Coop Cred Activity Report";

    protected $errors = array();
    // headers vary by Program
    protected $report_headers = array('Date', 'Receipt', 'Amount', 'Type');
    protected $sort_direction = 1;
    protected $required_fields = array('memNum', 'programID');
    protected $cardNo = 0;
    protected $programID = 0;
    protected $programBankID = 0;
    protected $programName = '';
    protected $memberFullName = '';

    public function preprocess()
    {
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_LIST,$FANNIE_PLUGIN_SETTINGS;

        if (!isset($FANNIE_PLUGIN_LIST) || !in_array('CoopCred', $FANNIE_PLUGIN_LIST)) {
            $this->errors[] = "The Coop Cred plugin is not enabled.";
            return False;
        }

        if (array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) &&
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'] != "") {
                $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        } else {
            $this->errors[] = "The Coop Cred database is not assigned in the " .
                "Coop Cred plugin configuration.";
            return False;
        }

        $this->cardNo = (int)FormLib::get('memNum',0);
        $this->programID = (int)FormLib::get('programID',0);

        $ccpModel = new CCredProgramsModel($dbc);
        $ccpModel->programID($this->programID);
        $prog = array_pop($ccpModel->find());
        if ($prog != null) {
            $this->programName = $prog->programName();
            $this->programBankID = $prog->bankID();
        } else {
            $this->errors[] = "Error: Program ID {$this->programID} is not known.";
            return False;
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cdModel = new CustdataModel($dbc);
        $cdModel->CardNo($this->cardNo);
        $cdModel->personNum(1);
        $mem = array_pop($cdModel->find());
        if ($mem != null) {
            $this->memberFullName = $mem->FirstName() . " " . $mem->LastName();
        } else {
            $noop = 1;
            $this->errors[] = "Error: Member {$this->cardNo} is not known.";
            return False;
        }

        /* 25Mar2015 Under bootstrap the non-sortable format doesn't really work.
         */
        $this->sortable = True;

        return parent::preprocess();
    }

    public function report_description_content()
    {
        $desc = sprintf("For: %s (#%d) in: %s",
            $this->memberFullName,
            $this->cardNo,
            $this->programName);
        $desc = "<h2>$desc</h2>";
        return array($desc);
    }

    public function fetch_report_data()
    {
        global $FANNIE_TRANS_DB, $FANNIE_URL;
        global $FANNIE_PLUGIN_SETTINGS;

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);

        $fromCols = "programID,cardNo,charges,payments,tdate,transNum";
        $fromSpec = "(SELECT $fromCols FROM CCredHistory " .
            "UNION SELECT $fromCols FROM CCredHistoryToday)";

        $q = $dbc->prepare("SELECT charges,transNum,payments,
                year(tdate) AS year, month(tdate) AS month, day(tdate) AS day
                FROM $fromSpec AS s 
                WHERE s.cardNo=? AND programID=?
                ORDER BY tdate DESC");
        $args=array($this->cardNo,$this->programID);
        $r = $dbc->execute($q,$args);

        $data = array();
        $rrp  = "{$FANNIE_URL}admin/LookupReceipt/RenderReceiptPage.php";
        while($w = $dbc->fetch_row($r)) {
            if ($w['charges'] == 0 && $w['payments'] == 0) {
                continue;
            }
            $record = array();
            // This Y/M/D format is sortable.
            $record[] = sprintf('%d/%d/%d',$w['year'],$w['month'],$w['day']);
            if (FormLib::get('excel') !== '') {
                $record[] = $w['transNum'];
            } else {
                // Receipt#, linked to Receipt Renderer, new tab
                $record[] = sprintf("<a href='{$rrp}?year=%d&month=%d&day=%d&receipt=%s' " .
                    "target='_CCRA_%s'" .
                    ">%s</a>",
                    $w['year'],$w['month'],$w['day'],
                    $w['transNum'],
                    $w['transNum'],
                    $w['transNum']
                );

            }
            // Amount
            $record[] = sprintf('%.2f', ($w['charges'] != 0
                ? (-1 * $w['charges']) : $w['payments']));
            $record[] = $this->getTypeLabel($w['charges'],$w['payments'],
                $this->cardNo,$this->programBankID,$this->programID);
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $ret = array();
        $total = 0.0;
        foreach($data as $record) {
            $total += $record[2];
        }
        if ($total > -0.01) {
            $total = ($total < 0)?0:$total;
            $balanceColour = "black";
            // Probably program-dependent, in config.
            $totalLabel = "Available for<br />purchases:";
        } else {
            $balanceColour = "red";
            $totalLabel = "Owes the<br />Coop:";
        }
        $ret[0] = "<span style='color:{$balanceColour};'>" .
            $totalLabel . "</span>";
        $ret[1] = '--';
        $ret[2] = "<span style='color:{$balanceColour};'>" .
            sprintf("%0.2f",$total) . "</span>";
        $ret[3] = '--';

        return $ret;
    }

    /* Return the appropriate label for the amount.
     * Needs to be externally configurable.
     *  Maybe in the CCredPrograms record.
     */
    private function getTypeLabel ($charges, $payment, $memberNumber,
        $bankNumber, $programID) {
        $label = "None";
        if ($memberNumber != $bankNumber) {
            if ($programID == 1) {
                $label = ($charges!=0?'Purchase':'Earning');
            } elseif ($programID == 2) {
                $label = ($charges!=0?'Purchase':'Earning');
            } else {
                $label = ($charges!=0?'Charge':'Payment');
            }
        } else {
            if ($payment < 0) {
                $label = "Distribution";
            } else {
                $label = "Input";
            }
        }
        return $label;
    }

    public function form_content()
    {

        $ret = '';
        if (isset($this->errors[0])) {
            $ret .= "<p style='font-family:Arial; font-size:1.5em;'>";
            $ret .= "<b>Errors in previous run:</b>";
            $sep = "<br />";
            foreach ($this->errors as $error) {
                $ret .= "{$sep}$error";
            }
            $ret .= "</p>";
        }

        /* Needs Program <select>
         * Would like chained or AJAX <select> of Members in the Program.
         *  Maybe not in v.1
         */
        $ret .= "<form method='get' action='{$_SERVER['PHP_SELF']}'>
            <b>Member #</b> <input type='text' name='memNum' value='{$this->memNum}'
            size='6' />
            <br />
            <b>Program #</b> <input type='text' name='programID' value='{$this->programID}'
            size='3' />
            <br /><br />
            <input type='submit' value='Report Activity' />
            </form>";

        return $ret;
    }

}

FannieDispatch::conditionalExec();

