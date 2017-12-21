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
        $url = $this->config->get('URL');
        $batchLists = array(
            '16TPR' => "$url/admin/labels/SignFromSearch.php?",
            '16CD' => "$url/admin/labels/SignFromSearch.php?",
            '12TPR' => "$url/admin/labels/SignFromSearch.php?",
            '12CD' => "$url/admin/labels/SignFromSearch.php?",
        );
        while ($row = $dbc->fetchRow($res)) {
            $batchName = $row['batchName'];
            $owner = $row['owner'];
            $id = $row['batchID'];
            if (in_array($owner, array('WELLNESS','PRODUCE'))) {
                if (strpos($batchName,'TPR')) {
                    $batchLists['16TPR'] .= 'batch[]='.$id.'&';
                } else {
                    $batchLists['16CD'] .= 'batch[]='.$id.'&';
                }
            } else {
                if (strpos($batchName,'TPR')) {
                    $batchLists['12TPR'] .= 'batch[]='.$id.'&';
                } else {
                    $batchLists['12CD'] .= 'batch[]='.$id.'&';
                }
            }
        }
        
        return <<<HTML
{$this->get_view()}
<form method="get" class="form-inline">
    </br>
    <label>Print Signs for $month $cycle $year </label></br>
    <div class="form-group">
        <input type="checkbox" id="check1">
        <a class="btn btn-success" href="{$batchLists['12CD']}" id="a1" target="_blank">12UP <b>{$cycle}</b></a>
    </div>
    <div class="form-group">
        <input type="checkbox" id="check2">
        <a class="btn btn-success" href="{$batchLists['16CD']}" id="a2" target="_blank">16UP <b>{$cycle}</b></a>
    </div>
    <div class="form-group">
        <input type="checkbox" id="check3">
        <a class="btn btn-warning" href="{$batchLists['12TPR']}" id="a3" target="_blank">12UP <b>TPR</b></a>
    </div>
    <div class="form-group">
        <input type="checkbox" id="check4">
        <a class="btn btn-warning" href="{$batchLists['16TPR']}" id="a4" target="_blank">16UP <b>TPR</b></a>
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
            $cycle_opts .= sprintf('<option %s>%s</option>',
                ($set == FormLib::get('cycle') ? 'selected' : ''),
                $cycle
            );
        }

        $years = '';
        $curYear = 2019;
        $curMonth = date('m');
        for ($i=2017; $i<$curYear; $i++) {
            $years .= "<option value='$i'>$i</option>";
        }

        $form = sprintf('
            <form method="get" class="form-inline">
                <div class="input-group">
                    <div class="input-group-addon">Year</div>
                    <select name="year" class="form-control">%s</select>
                </div>
                <div class="input-group">
                    <div class="input-group-addon">Month</div>
                    <select name="dealSet" class="form-control">%s</select>
                </div>
                <div class="input-group">
                    <div class="input-group-addon">Cycle: </div>
                    <select name="cycle" class="form-control">
                        <option value="A">A</option>
                        <option value="B">B</option>
                    </select>
                </div>
                <div class="input-group">
                    <button type="submit" class="btn btn-default">Load</button>
                </div>
            </form>
        ',$years,$deal_opts);
        return <<<HTML
{$form}
HTML;
    }

    public function javascript_content()
    {
        return <<<HTML
$('#a1').click(function(){
    $('#check1').prop('checked', true);
});
$('#a2').click(function(){
    $('#check2').prop('checked', true);
});
$('#a3').click(function(){
    $('#check3').prop('checked', true);
});
$('#a4').click(function(){
    $('#check4').prop('checked', true);
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

}
FannieDispatch::conditionalExec();
