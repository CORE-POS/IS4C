<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CoopDealsReviewPage extends FanniePage 
{

    protected $title = "Fannie - CAP sales";
    protected $header = "Review Data";

    public $description = '[Co+op Deals Review] lists the currently load Co+op Deals data
    and can create sales batches from that data.';
    
    private $mode = 'form';

    public function preprocess()
    {
        if (FormLib::get_form_value('start') !== '') {
            $this->mode = 'results';
        }

        return true;
    }

    public function body_content()
    {
        if ($this->mode == 'form') {
            return $this->form_content();
        } elseif ($this->mode == 'results') {
            return $this->results_content();
        }
    }

    public function results_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $start = date('Y-m-d', strtotime(FormLib::get_form_value('start',date('Y-m-d'))));
        $end = date('Y-m-d', strtotime(FormLib::get_form_value('end',date('Y-m-d'))));
        $b_start = date('Y-m-d', strtotime(FormLib::get_form_value('bstart',date('Y-m-d'))));
        $b_end = date('Y-m-d', strtotime(FormLib::get_form_value('bend',date('Y-m-d'))));
        $naming = FormLib::get_form_value('naming','');
        $upcs = FormLib::get_form_value('upc',array());
        $prices = FormLib::get_form_value('price',array());
        $names = FormLib::get_form_value('batch',array());
        $batchIDs = array();

        if( FormLib::get_form_value('group_by_superdepts','') == 'on' ){
            $superdept_grouping = "CASE WHEN s.super_name IS NULL THEN 'sale' ELSE s.super_name END";
        } else {
            $superdept_grouping = "";
        }
        $saleItemsP = $dbc->prepare_statement("
            SELECT t.upc,
                t.price,"
                . $dbc->concat(
                    ($superdept_grouping ? $superdept_grouping : "''"),
                    ($superdept_grouping ? "' '" : "''"),
                    "'Co-op Deals '",
                    "t.abtpr",
                    ''
                ) . " AS batch
            FROM tempCapPrices as t
                INNER JOIN products AS p on t.upc = p.upc
                LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
            ORDER BY s.super_name, t.upc
        ");
        $saleItemsR = $dbc->exec_statement($saleItemsP);
        define("UPC_COL",0);
        define("PRICE_COL",1);
        define("BATCHNAME_COL",2);

        $batchP = $dbc->prepare_statement('
            INSERT INTO batches (
                batchName,
                batchType,
                discountType,
                priority,
                startDate,
                endDate
            )
            VALUES (?, ?, ?, 0, ?, ?)
        ');

        $list = new BatchListModel($dbc);
        $list->active(0);
        $list->pricemethod(0);
        $list->quantity(0);

        while ($row = $dbc->fetch_row($saleItemsR)) {
            if (!isset($batchIDs[$row[BATCHNAME_COL]])) {
                $args = array($row[BATCHNAME_COL] . ' ' . $naming, 1, 1);
                if (substr($row[BATCHNAME_COL],-2) == " A"){
                    $args[] = $start;
                    $args[] = $end;
                } else if (substr($row[BATCHNAME_COL],-2) == " B") {
                    $args[] = $b_start;
                    $args[] = $b_end;
                } else {
                    $args[] = $start;
                    $args[] = $b_end;
                }
    
                $dbc->exec_statement($batchP,$args);
                $bID = $dbc->insert_id();
                $batchIDs[$row[BATCHNAME_COL]] = $bID;
            }
            $id = $batchIDs[$row[BATCHNAME_COL]];

            $list->upc($row[UPC_COL]);
            $list->batchID($id);
            $list->salePrice(sprintf("%.2f",$row[PRICE_COL]));
            $list->save();
        }

        $ret = "New sales batches have been created!<p />";
        $ret .= "<a href=\"../newbatch/\">View batches</a>";    

        return $ret;
    }

    public function form_content()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $query = $dbc->prepare_statement("
            SELECT
                t.upc,
                p.description,
                t.price,
                CASE WHEN s.super_name IS NULL THEN 'sale' ELSE s.super_name END as batch,
                t.abtpr as subbatch
            FROM
                tempCapPrices as t
                INNER JOIN products AS p on t.upc = p.upc
                LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
            ORDER BY s.super_name,t.upc
        ");
        $result = $dbc->exec_statement($query);

        $ret = "<form action=CoopDealsReviewPage.php method=post>
        <table cellpadding=4 cellspacing=0 border=1>
        <tr><th>UPC</th><th>Desc</th><th>Sale Price</th><th>Batch</th></tr>\n";
        while ($row = $dbc->fetch_row($result)) {
            $ret .= sprintf('<tr>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%.2f</td>
                        <td><span class="superNameSpan">%s </span>Co-op Deals %s</td>
                        </tr>' . "\n",
                        $row['upc'],
                        $row['description'],
                        $row['price'],
                        $row['batch'],
                        $row['subbatch']);
        }
        $ret .= <<<html
        </table><p />
        <table cellpadding=4 cellspacing=0><tr>
        <td><b>A Start</b></td><td><input type=text name=start id=start /></td>
        </tr><tr>
        <td><b>A End</b></td><td><input type=text name=end id=end /></td>
        </tr><tr>
        <td><b>B Start</b></td><td><input type=text name=bstart id=bstart /></td>
        </tr><tr>
        <td><b>B End</b></td><td><input type=text name=bend id=bend /></td>
        </tr><tr>
        <td><b>Month</b></td><td><input type=text name=naming /></td>
        </tr></table>
        <label>
            <input type="checkbox" name="group_by_superdepts" checked="true" 
                onchange="$('.superNameSpan').toggle(); " />
            Group sale batches by Superdepartment
         </label><br />
        <input type=submit value="Create Batch(es)" />
        </form>
html;

        $this->add_onload_command("\$('#start').datepicker();\n");
        $this->add_onload_command("\$('#end').datepicker();\n");
        $this->add_onload_command("\$('#bstart').datepicker();\n");
        $this->add_onload_command("\$('#bend').datepicker();\n");

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

