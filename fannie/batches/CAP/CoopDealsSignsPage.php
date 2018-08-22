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

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class CoopDealsSignsPage extends FannieRESTfulPage
{
    protected $title = "Fannie - Coop Deals Signs Page";
    protected $header = "Print Coop Deals Signs";

    public $description = '[] .';
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;


    public function preprocess()
    {
        $this->__routes[] = 'get<dealSet><cycle>';

        return parent::preprocess();
    }

    public function get_dealSet_cycle_view()
    {
        include(__DIR__.'/../../config.php');
        $cycle = FormLib::get('cycle');
        $month = FormLib::get('dealSet');
        $year = FormLib::get('year');
        $storeID = FormLib::get('store');
        $dir = __DIR__;

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $Qcycle = ($cycle == 'A') ?
            "AND (batchName like '%Deals A%' OR batchName like '%Deals TPR%') " :
            "AND batchName like '%Deals B%' ";
        $Qdealset = "AND batchName like '%$month%' ";

        $args = array($year);
        $query = '
            SELECT
                batchID,
                batchName,
                owner,
                batchType,
                startDate,
                endDate
            FROM is4c_op.batches
            WHERE batchType = 1
                '.$Qcycle.'
                AND SUBSTR(startDate,1,4) = ?
                '.$Qdealset.'
        ';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep,$args);
        $url = "http://key" . $this->config->get('URL') . "/admin/labels/SignFromSearch.php?store=$storeID&";
        $batchLists = array(
            '16TPR' => array(
                'MERCH' => $url,
                'PRODUCE' => $url,
                'DELI' => $url,
                'WELLNESS' => $url,
            ),
            '16CD' => array(
                'MERCH' => $url,
                'PRODUCE' => $url,
                'DELI' => $url,
                'WELLNESS' => $url,
            ),
            '12TPR' => array(
                'MERCH' => $url,
                'LAMINATES' => $url,
                'BULK' => $url,
                'FROZEN' => $url,
                'GROCERY' => $url,
                'PRODUCE' => $url,
                'DELI' => $url,
                'WELLNESS' => $url,
            ),
            '12CD' => array(
                'MERCH' => $url,
                'LAMINATES' => $url,
                'BULK' => $url,
                'FROZEN' => $url,
                'GROCERY' => $url,
                'PRODUCE' => $url,
                'DELI' => $url,
                'WELLNESS' => $url,
            ),
        );
        while ($row = $dbc->fetchRow($res)) {
            $batchName = $row['batchName'];
            $owner = $row['owner'];
            $id = $row['batchID'];
            if (in_array($owner, array('WELLNESS','PRODUCE'))) {
                if (strpos($batchName,'TPR')) {
                    if ($owner == 'PRODUCE') {
                        $batchLists['16TPR']['PRODUCE'] .= 'batch[]='.$id.'&';
                    } elseif ($owner == 'WELLNESS') {
                        $batchLists['16TPR']['WELLNESS'] .= 'batch[]='.$id.'&';
                    }
                } else {
                    if ($owner == 'PRODUCE') {
                        $batchLists['16CD']['PRODUCE'] .= 'batch[]='.$id.'&';
                    } elseif ($owner == 'WELLNESS') {
                        $batchLists['16CD']['WELLNESS'] .= 'batch[]='.$id.'&';
                    }
                }
            } else {
                if ($owner == 'PRODUCE') {
                    if (strpos($batchName,'TPR')) {
                        $batchLists['12TPR']['PRODUCE'] .= 'batch[]='.$id.'&';
                    } else {
                        $batchLists['12CD']['PRODUCE'] .= 'batch[]='.$id.'&';
                    }
                } elseif ($owner == 'DELI') {
                    if (strpos($batchName,'TPR')) {
                        $batchLists['12TPR']['DELI'] .= 'batch[]='.$id.'&';
                    } else {
                        $batchLists['12CD']['DELI'] .= 'batch[]='.$id.'&';
                    }
                } elseif ($owner == 'REFRIGERATED' || $owner == 'MEAT' || $owner == 'BULK') {
                    if (strpos($batchName,'TPR')) {
                        $batchLists['12TPR']['LAMINATES'] .= 'batch[]='.$id.'&';
                    } else {
                        $batchLists['12CD']['LAMINATES'] .= 'batch[]='.$id.'&';
                    }
                } elseif ($owner == 'GROCERY') {
                    if (strpos($batchName,'TPR')) {
                        $batchLists['12TPR']['GROCERY'] .= 'batch[]='.$id.'&';
                    } else {
                        $batchLists['12CD']['GROCERY'] .= 'batch[]='.$id.'&';
                    }
                } else {
                    if (strpos($batchName,'TPR')) {
                        $batchLists['12TPR']['MERCH'] .= 'batch[]='.$id.'&';
                    } else {
                        $batchLists['12CD']['MERCH'] .= 'batch[]='.$id.'&';
                    }
                }
            }
        }
        $disabled = ($cycle == 'B') ? 'collapse' : '';

        return <<<HTML
{$this->get_view()}
<form method="get" class="form-inline">
    </br>
    <h4>Print Signs for $month $cycle $year </h4>
    <div class="form-group">
        <a class="btn btn-success" onclick="
            window.open('{$batchLists["12CD"]["MERCH"]}');
            window.open('{$batchLists["12CD"]["DELI"]}');
            window.open('{$batchLists["12CD"]["LAMINATES"]}');
            window.open('{$batchLists["12CD"]["GROCERY"]}');
            return false;
            "
            id="a1" target="_blank">12UP <b>{$cycle}</b></a>
    </div>
    <div class="form-group">
        <a class="btn btn-success" onclick="
            window.open('{$batchLists["16CD"]["WELLNESS"]}&signmod=COREPOS\\\Fannie\\\API\\\item\\\signage\\\Signage16UpP');
            window.open('{$batchLists["16CD"]["PRODUCE"]}&signmod=COREPOS\\\Fannie\\\API\\\item\\\signage\\\Signage16UpP');
            return false;
            "
            id="a2" target="_blank">16UP <b>{$cycle}</b></a>
    </div>
    <div class="form-group">
        <a class="btn btn-warning $disabled" onclick="
            window.open('{$batchLists["12TPR"]["MERCH"]}');
            window.open('{$batchLists["12TPR"]["DELI"]}');
            window.open('{$batchLists["12TPR"]["LAMINATES"]}');
            window.open('{$batchLists["12TPR"]["GROCERY"]}');
            return false;
            "
            id="a3" target="_blank">12UP <b>TPR</b></a>
    </div>
    <div class="form-group">
        <a class="btn btn-warning $disabled" onclick="
            window.open('{$batchLists["16TPR"]["WELLNESS"]}&signmod=COREPOS\\\Fannie\\\API\\\item\\\signage\\\Signage16UpP');
            window.open('{$batchLists["16TPR"]["PRODUCE"]}&signmod=COREPOS\\\Fannie\\\API\\\item\\\signage\\\Signage16UpP');
            return false;
            "
            id="a4" target="_blank">16UP <b>TPR</b></a>
    </div>
</form>
<br/>
HTML;
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

        $cycles = array('A','B');
        $cycle_opts = '';
        foreach ($cycles as $cycle) {
            $sel = ($cycle == FormLib::get('cycle')) ? " selected " : "";
            $cycle_opts .= "<option value='$cycle' $sel>$cycle</option>";
        }

        $years = '';
        $curYear = 2019;
        $curMonth = date('m');
        for ($i=2017; $i<$curYear; $i++) {
            $sel = ($i == FormLib::get('year')) ? " selected " : "";
            $years .= "<option value='$i' $sel>$i</option>";
        }

        $stores = new StoresModel($this->connection);
        $stores->hasOwnItems(1);
        $store_opts = "";
        $store_opts .= '<select class="form-control" name="store">
                <option value="0">Any Store</option>';
        foreach ($stores->find() as $s) {
            $store_selected = (FormLib::get('store') == $s->storeID()) ? ' SELECTED ' : '';
            $store_opts .= sprintf('<option value="%d" %s>%s</option>',
                $s->storeID(), $store_selected, $s->description());
        }
        $store_opts .= '</select>';

        $form = sprintf('
            <form method="get" class="form-inline">
                <div class="form-group"><div class="input-group">
                    <div class="input-group-addon">Year</div>
                    <select name="year" class="form-control">%s</select>
                </div></div>
                <div class="form-group"><div class="input-group">
                    <div class="input-group-addon">Month</div>
                    <select name="dealSet" class="form-control">%s</select>
                </div></div>
                <div class="form-group"><div class="input-group">
                    <div class="input-group-addon">Cycle: </div>
                    <select name="cycle" class="form-control">
                        %s
                    </select>
                </div></div>
                <div class="form-group"><div class="input-group">
                    %s
                </div></div>
                <div class="form-group"><div class="input-group">
                    <button type="submit" class="btn btn-default">Load</button>
                </div></div>
            </form>
        ', $years, $deal_opts, $cycle_opts, $store_opts);
        return <<<HTML
{$form}
HTML;
    }

    public function javascript_content()
    {
        return <<<HTML
$('#a1').click(function(){
    $(this).addClass('disabled');
});
$('#a2').click(function(){
    $(this).addClass('disabled');
});
$('#a3').click(function(){
    $(this).addClass('disabled');
});
$('#a4').click(function(){
    $(this).addClass('disabled');
});
HTML;
    }

    public function css_content()
    {
        return <<<HTML
.btn-success, .btn-warning {
    width: 150px;
}
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
<label>Find and separate Co+op Deals sales signage by</label>:
<ul>
    <li>Department (Grocery Super Dept. is kept separate)</li>
    <li>Signs that need to be laminated.</li>
    <li>Signs that do not require lamination.</li>
</ul>
HTML;
    }

}
FannieDispatch::conditionalExec();
