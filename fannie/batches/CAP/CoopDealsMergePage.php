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

class CoopDealsMergePage extends FannieRESTfulPage 
{
    protected $title = "Fannie - CAP sales";
    protected $header = "Review Data";

    public $description = '[Co+op Deals Merge] lists items in the Co+op Deals data
    that are not part of a current or future sales batch.';
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;
    
    private $mode = 'form';

    public function preprocess()
    {
        $this->__routes[] = 'get<added>';
        $this->__routes[] = 'post<upc><price><batchID><mult>';

        return parent::preprocess();
    }

    public function post_upc_price_batchID_mult_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $added = 0;
        $batchList = new BatchListModel($dbc);
        for ($i=0; $i<count($this->batchID); $i++) {
            if ($this->batchID[$i] == '') {
                continue;
            }
            if (!isset($this->upc[$i]) || !isset($this->price[$i])) {
                continue;
            }
            $batchList->salePrice($this->price[$i]);
            $batchList->batchID($this->batchID[$i]);
            $batchList->upc(BarcodeLib::padUPC($this->upc[$i]));
            $batchList->signMultiplier($this->mult[$i]);
            if ($batchList->save()) {
                $added++;
            }
        }

        header('Location: ?added=' . $added);

        return false;
    }

    public function get_added_view()
    {
        return '<div class="alert alert-info">Added ' . $this->added . ' items to batches</div>'
            . $this->get_view();
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $set = FormLib::get('deal-set');
        $optsR = $dbc->query('
            SELECT dealSet
            FROM CoopDealsItems
            GROUP BY dealSet
            ORDER BY MAX(coopDealsItemID) DESC');
        $deal_opts = '';
        while ($optsW = $dbc->fetchRow($optsR)) {
            if ($set === '') {
                $set = $optsW['dealSet'];
            }
            $deal_opts .= sprintf('<option %s>%s</option>',
                ($set == $optsW['dealSet'] ? 'selected' : ''),
                $optsW['dealSet']
            );
        }

        $query = $dbc->prepare("
            SELECT
                t.upc,
                p.description,
                p.brand,
                t.price,
                CASE WHEN s.super_name IS NULL THEN 'sale' ELSE s.super_name END as batch,
                t.abtpr as subbatch,
                multiplier
            FROM
                CoopDealsItems as t
                " . DTrans::joinProducts('t', 'p', 'INNER') . "
                LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
            WHERE p.inUse=1
                AND t.price < p.normal_price
                AND t.dealSet=?
            ORDER BY s.super_name,t.upc
        ");
        $result = $dbc->execute($query, array($set));
        $upcomingP = $dbc->prepare('
            SELECT batchName
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
            WHERE l.upc=?
                AND b.endDate >= ' . $dbc->curdate() 
        );

        $allR = $dbc->query('
            SELECT batchID,
                batchName
            FROM batches
            WHERE endDate >= ' . $dbc->curdate()
        );
        $opts = array();
        while ($allW = $dbc->fetchRow($allR)) {
            $opts[$allW['batchID']] = $allW['batchName'];
        }

        $ret = "<form action=CoopDealsMergePage.php method=post>
        <div class=\"form-group\">
            <label>Month</label>
            <select name=\"deal-set\" class=\"form-control\" 
                onchange=\"location='?deal-set='+this.value;\">
            " . $deal_opts . "
            </select>
        </div>
        <table class=\"table table-bordered table-striped tablesorter tablesorter-core small\">
        <thead>
        <tr><th>UPC</th><th>Brand</th><th>Desc</th><th>Sale Price</th>
        <th>Add to Batch</th></tr>\n
        </thead><tbody>";
        $upcs = array('a'=>'', 'b'=>'', 'tpr'=>'');
        while ($row = $dbc->fetch_row($result)) {
            $upcoming = $dbc->getValue($upcomingP, array($row['upc']));
            if ($upcoming) {
                continue;
            }
            $name = $row['batch'] . ' Co-op Deals ' . $row['subbatch'];
            $ret .= sprintf('<tr>
                        <td><input type="hidden" name="upc[]" value="%s"/>%s
                            <input type="hidden" name="mult[]" value="%d" />
                        </td>
                        <td>%s</td>
                        <td>%s</td>
                        <td><input type="hidden" name="price[]" value="%.2f"/>%.2f</td>
                        <td><select class="form-control input-sm" name="batchID[]">
                            <option value="">Select batch...</option>',
                        $row['upc'], \COREPOS\Fannie\API\lib\FannieUI::itemEditorLink($row['upc']),
                        $row['multiplier'],
                        $row['brand'],
                        $row['description'] . ' ' . $row['subbatch'],
                        $row['price'],$row['price']
            );
            foreach ($opts as $id => $batch) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            strstr($batch, $name) ? 'selected' : '',
                            $id, $batch);
            }
            $ret .= '</select></td></tr>';
            if ($row['subbatch'] == 'TPR') {
                $upcs['tpr'] .= $row['upc'] . "\n";
            } elseif ($row['subbatch'] == 'A') {
                $upcs['a'] .= $row['upc'] . "\n";
            } elseif ($row['subbatch'] == 'B') {
                $upcs['b'] .= $row['upc'] . "\n";
            } elseif ($row['subbatch'] == 'AB') {
                $upcs['a'] .= $row['upc'] . "\n";
                $upcs['b'] .= $row['upc'] . "\n";
            }
        }
        $ret .= <<<html
        </tbody>
        </table>
        <textarea rows="5" cols="15">A
{$upcs['a']}</textarea>
        <textarea rows="5" cols="15">B
{$upcs['b']}</textarea>
        <textarea rows="5" cols="15">TPR
{$upcs['tpr']}</textarea>
        <p>    
            <button type=submit class="btn btn-default">Merge Items into Batch(es)</button>
            <a href="CoopDealsReviewPage.php" class="pull-right btn btn-default">Create New Batch(es)</a>
        </p>
        </form>
html;

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
        $this->added = 'foo';
        $phpunit->assertNotEquals(0, strlen($this->get_added_view()));
    }
}

FannieDispatch::conditionalExec();

