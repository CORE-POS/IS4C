<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;
    
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
        $set = FormLib::get('deal-set');
        $batchIDs = array();

        if( FormLib::get_form_value('group_by_superdepts','') == 'on' ){
            $superdept_grouping = "CASE WHEN s.super_name IS NULL THEN 'sale' ELSE s.super_name END";
        } else {
            $superdept_grouping = "";
        }

        list($upcIn, $args) = $dbc->safeInClause($upcs);
        $args[] = $set;

        $saleItemsP = $dbc->prepare("
            SELECT t.upc,
                t.price,
                t.multiplier,"
                . $dbc->concat(
                    ($superdept_grouping ? $superdept_grouping : "''"),
                    ($superdept_grouping ? "' '" : "''"),
                    "'Co-op Deals '",
                    "t.abtpr",
                    ''
                ) . " AS batch
            FROM CoopDealsItems as t
                " . DTrans::joinProducts('t', 'p', 'INNER') . "
                LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
            WHERE t.upc IN ({$upcIn})
                AND t.dealSet=?
            ORDER BY s.super_name, t.upc
        ");
        $saleItemsR = $dbc->execute($saleItemsP, $args);

        $batchP = $dbc->prepare('
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
            if (!isset($batchIDs[$row['batch']])) {
                $args = array($row['batch'] . ' ' . $naming, 1, 1);
                if (substr($row['batch'],-2) == " A"){
                    $args[] = $start;
                    $args[] = $end;
                } else if (substr($row['batch'],-2) == " B") {
                    $args[] = $b_start;
                    $args[] = $b_end;
                } else {
                    $args[] = $start;
                    $args[] = $b_end;
                }
    
                $dbc->execute($batchP,$args);
                $bID = $dbc->insertID();
                $batchIDs[$row['batch']] = $bID;

                if ($this->config->get('STORE_MODE') === 'HQ') {
                    StoreBatchMapModel::initBatch($bID);
                }
            }
            $id = $batchIDs[$row['batch']];

            $list->upc($row['upc']);
            $list->batchID($id);
            $list->salePrice(sprintf("%.2f",$row['price']));
            $list->signMultiplier($row['multiplier']);
            $list->save();
        }

        $ret = "<p>New sales batches have been created!</p>";
        $ret .= "<p><a href=\"../newbatch/\">View batches</a></p>";

        return $ret;
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $set = FormLib::get('deal-set');
        $optsR = $dbc->query('
            SELECT dealSet
            FROM CoopDealsItems
            GROUP BY dealSet
            ORDER BY MAX(coopDealsItemID) DESC');
        $opts = '';
        while ($optsW = $dbc->fetchRow($optsR)) {
            if ($set === '') {
                $set = $optsW['dealSet'];
            }
            $opts .= sprintf('<option %s>%s</option>',
                ($set == $optsW['dealSet'] ? 'selected' : ''),
                $optsW['dealSet']
            );
        }

        $query = $dbc->prepare("
            SELECT
                t.upc,
                p.brand,
                p.description,
                t.price,
                MAX(CASE WHEN s.super_name IS NULL THEN 'sale' ELSE s.super_name END) as batch,
                t.abtpr as subbatch
            FROM CoopDealsItems as t
                LEFT JOIN products AS p ON t.upc=p.upc
                LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
            WHERE t.dealSet=?
                AND p.inUse=1
            GROUP BY t.upc,
                p.brand,
                p.description,
                t.price,
                t.abtpr
            ORDER BY s.super_name,t.upc
        ");
        $result = $dbc->execute($query, array($set));

        $ret = "<form action=CoopDealsReviewPage.php method=post>
        <div class=\"form-group\">
            <label>Month</label>
            <select name=\"deal-set\" class=\"form-control\" 
                onchange=\"location='?deal-set='+this.value;\">
            " . $opts . "
            </select>
        </div>
        <table class=\"table table-bordered table-striped tablesorter tablesorter-core small\">
        <thead>
        <tr><th>UPC</th><th>Brand</th><th>Desc</th><th>Sale Price</th>
        <th>New Batch Name</th></tr>\n
        </thead><tbody>";
        while ($row = $dbc->fetch_row($result)) {
            $ret .= sprintf('<input type="hidden" name="upc[]" value="%s" />', $row['upc']);
            $ret .= sprintf('<tr>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%.2f</td>
                        <td><span class="superNameSpan">%s </span>Co-op Deals %s</td>
                        </tr>' . "\n",
                        $row['upc'],
                        $row['brand'],
                        $row['description'],
                        $row['price'],
                        $row['batch'],
                        $row['subbatch']
                        );
        }
        $ret .= <<<html
        </tbody>
        </table><p />
        <div class="row form-horizontal form-group">
            <label class="col-sm-2 control-label">A Start</label>
            <div class="col-sm-4">
                <input type="text" name="start" id="start" class="form-control date-field" />
            </div>
            <label class="col-sm-2 control-label">B Start</label>
            <div class="col-sm-4">
                <input type="text" name="bstart" id="bstart" class="form-control date-field" />
            </div>
        </div>
        <div class="row form-horizontal form-group">
            <label class="col-sm-2 control-label">A End</label>
            <div class="col-sm-4">
                <input type="text" name="end" id="end" class="form-control date-field" />
            </div>
            <label class="col-sm-2 control-label">B End</label>
            <div class="col-sm-4">
                <input type="text" name="bend" id="bend" class="form-control date-field" />
            </div>
        </div>
        <div class="row form-horizontal form-group">
            <label class="col-sm-2 control-label">Month</label>
            <div class="col-sm-4">
                <input type="text" name="naming" class="form-control" value="{{set}}" />
            </div>
            <label class="col-sm-6">
                <input type="checkbox" name="group_by_superdepts" checked="true" 
                    onchange="$('.superNameSpan').toggle(); " />
                Group sale batches by Superdepartment
            </label>
        </div>
        <p>    
            <button type=submit class="btn btn-default">Create Batch(es)</button>
            <a href="CoopDealsMergePage.php" class="pull-right btn btn-default">Merge New Items into Existing Batch(es)</a>
        </p>
        </form>
html;
        $ret = str_replace('{{set}}', $set, $ret);

        return $ret;
    }
    
    public function helpContent()
    {
        return '<p>This tool creates A, B, and TPR batches. The TPR batches will
            start on <em>A Start</em> and end on <em>B End</em>. The Month field
            is used in batch names. For example, if Month is <em>January</em>, 
            batches will have names like <em>Co+op Deals January A</em>.</p>
            <p><em>Group sale batches by superdepartment</em> means create 
            separate sales batches for each appicable superdepartment rather
            than having a single A batch, single B batch, and single TPR
            batch.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

