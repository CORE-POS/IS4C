<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Show total sales by hour for today from dlog.
 * Offer dropdown of superdepartments and, on-select, display the same report for
 *  that superdept only.
 * This page extends FanniePage because it is simpler than most reports
 *  and would be encumbered by the FannieReportPage structure.
*/

include(dirname(__FILE__) . '/../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class PriceDiscrepancies extends \COREPOS\Fannie\API\FannieReportTool 
{
    public $description = '[Prices Discrepancies] shows items with differing retail price';
    public $report_set = 'Price Reports';

    protected $selected = -1;
    protected $store = 0;
    protected $name = "";
    protected $supers = array();

    public function preprocess()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $this->form = new COREPOS\common\mvc\FormValueContainer();
        try {
            $this->selected = $this->form->super;
        } catch (Exception $ex) { }


        /* Populate an array of superdepartments from which to
         *  select for filtering this report in the next run
         *  and if a superdepartment was chosen for this run
         *  get its name.
        */
        $superP = $dbc->prepare("SELECT superID,super_name FROM MasterSuperDepts ORDER BY super_name");
        $superR = $dbc->execute($superP);
        $this->supers = array();
        $this->supers[-1] = "All Departments";
        while ($row = $dbc->fetchRow($superR)) {
            $this->supers[$row[0]] = $row[1];
            if ($this->selected == $row[0]) {
                $this->name = $row[1];
            }
        }

        $this->title = "Price Discrepancies";
        $this->header = '';
        return True;

    // preprocess()
    }

    public function body_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $args = array();
        $query = '
            SELECT upc,
                MAX(brand) AS brand,
                MAX(description) AS descr,
                MIN(normal_price) AS lowPrice,
                MAX(normal_price) AS highPrice,
                MAX(p.last_sold) AS last_sold
            FROM products AS p
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID ';
        if ($this->selected > -1) {
            $query .= ' WHERE m.superID=? ';
            $args[] = $this->selected;
        }
        $query .= '
            GROUP BY upc
            HAVING MIN(normal_price) <> MAX(normal_price)
            ORDER BY MAX(m.superID), brand, descr';

        $opts = array();
        foreach ($this->supers as $id => $name) {
            $opts .= sprintf('<option %s value="%d">%s</option>',
                    ($this->selected == $id ? 'selected' : ''),
                    $id, $name);
        }
        $ret = '<form id="pdForm" method="get">
            <p><div class="text-center form-inline">
            <strong>Category</strong>
            <select name="super" class="form-control" onchange="$(\'#pdForm\').submit();">
            ' . $opts . '
            </select>
            </div></p>
            </form>';

        $ret .= '<table class="table table-bordered table-striped table-float">
            <thead style="background:#fff;"><tr>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Low Price</th>
                <th>High Price</th>
                <th>Last Sold</th>
            </tr></thead><tbody>';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<tr>
                <td><a href="%sitem/ItemEditorPage.php?searchupc=%s">%s</a></td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%s</td>
                </tr>',
                $this->config->get('URL'), $row['upc'], $row['upc'],
                $row['brand'],
                $row['descr'],
                $row['lowPrice'],
                $row['highPrice'],
                $row['last_sold']
            );
        }
        $ret .= '</tbody></table>';

        $this->addScript($this->config->get('URL') . 'src/javascript/jquery.floatThead.min.js');
        $this->addOnloadCommand("\$('.table-float').floatThead();\n");

        return $ret;
    // body_content()
    }

}

FannieDispatch::conditionalExec();

