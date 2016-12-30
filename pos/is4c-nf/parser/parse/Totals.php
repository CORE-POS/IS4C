<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IT CORE.

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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\TransRecord;
use \CoreLocal;
use COREPOS\pos\parser\Parser;

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 13Jan2013 Eric Lee Added MTL for Ontario Meal Tax Rebate

*/

class Totals extends Parser {

    function check($str){
        if ($str == "FNTL" || $str == "TETL" ||
            $str == "FTTL" || $str == "TL" ||
            $str == "MTL" || $str == "WICTL" ||
            substr($str,0,2) == "FN")
            return True;
        return False;
    }

    function parse($str)
    {
        $ret = $this->default_json();
        if ($str == "FNTL"){
            $ret['main_frame'] = MiscLib::baseURL().'gui-modules/fsTotalConfirm.php';
        } elseif ($str == "TETL"){
            $ret['main_frame'] = MiscLib::baseURL().'gui-modules/requestInfo.php?class=COREPOS-pos-parser-parse-Totals';
        } elseif ($str == "FTTL") {
            $this->finalttl();
        } elseif ($str == "TL"){
            $this->session->set('End', 0);
            $chk = PrehLib::ttl();
            if ($chk !== True)
                $ret['main_frame'] = $chk;
        } elseif ($str == "MTL") {
            $chk = PrehLib::omtr_ttl();
            if ($chk !== True)
                $ret['main_frame'] = $chk;
        } elseif ($str == "WICTL") {
            $ttl = $this->wicableTotal();
            $ret['output'] = DisplayLib::boxMsg(
                _('WIC Total') . sprintf(': $%.2f', $ttl), 
                '', 
                true,
                DisplayLib::standardClearButton()
            );

            // return early since output has been set
            return $ret;
        }

        if (!$ret['main_frame']){
            $ret['output'] = DisplayLib::lastpage();
            $ret['redraw_footer'] = True;
        }
        return $ret;
    }

    static $requestInfoHeader = 'tax exempt';
    static $requestInfoMsg = 'Enter the tax exempt ID';
    static function requestInfoCallback($info){
        TransRecord::addTaxExempt();
        TransRecord::addcomment("Tax Ex ID# ".$info);
        return True;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>FNTL</td>
                <td>Foodstamp eligible total</td>
            </tr>
            <tr>
                <td>TETL</td>
                <td>Tax exempt total</td>
            </tr>
            <tr>
                <td>FTTL</td>
                <td>Final total</td>
            </tr>
            <tr>
                <td>TL</td>
                <td>Re-calculate total</td>
            </tr>
            <tr>
                <td>MTL</td>
                <td>Ontario (Canada) Meal Tax Rebate
                <br />Remove Provincial tax on food up to \$4 to this point in the transaction.</td>
            </tr>
            </table>";
    }

    /**
      Calculate WIC eligible total
      @return [number] WIC eligible items total
    */
    private function wicableTotal()
    {
        $dbc = Database::tDataConnect();
        $products = $this->session->get('pDatabase') . $dbc->sep() . 'products';

        $query = '
            SELECT SUM(total) AS wicableTotal
            FROM localtemptrans AS t
                INNER JOIN ' . $products . ' AS p ON t.upc=p.upc
            WHERE t.trans_type = \'I\'
                AND p.wicable = 1
        ';

        $result = $dbc->query($query);
        if (!$result || $dbc->numRows($result) == 0) {
            return 0.00;
        }
        $row = $dbc->fetchRow($result);
        
        return $row['wicableTotal'];
    }

    /**
      Add tax and transaction discount records.
      This is called at the end of a transaction.
      There's probably no other place where calling
      this function is appropriate.
    */
    private function finalttl() 
    {
        if ($this->session->get("percentDiscount") > 0) {
            TransRecord::addRecord(array(
                'description' => 'Discount',
                'trans_type' => 'C',
                'trans_status' => 'D',
                'unitPrice' => MiscLib::truncate2(-1 * $this->session->get('transDiscount')),
                'voided' => 5,
            ));
        }

        TransRecord::addRecord(array(
            'upc' => 'Subtotal',
            'description' => 'Subtotal',
            'trans_type' => 'C',
            'trans_status' => 'D',
            'unitPrice' => MiscLib::truncate2($this->session->get('taxTotal') - $this->session->get('fsTaxExempt')),
            'voided' => 11,
        ));

        if ($this->session->get("fsTaxExempt")  != 0) {
            TransRecord::addRecord(array(
                'upc' => 'Tax',
                'description' => 'FS Taxable',
                'trans_type' => 'C',
                'trans_status' => 'D',
                'unitPrice' => MiscLib::truncate2($this->session->get('fsTaxExempt')),
                'voided' => 7,
            ));
        }

        TransRecord::addRecord(array(
            'upc' => 'Total',
            'description' => 'Total',
            'trans_type' => 'C',
            'trans_status' => 'D',
            'unitPrice' => MiscLib::truncate2($this->session->get('amtdue')),
            'voided' => 11,
        ));
    }
}

