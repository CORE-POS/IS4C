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

class CoopDealsSignsPageNew extends FannieRESTfulPage
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
        $dealSet = FormLib::get('dealSet');
        $year = FormLib::get('year');
        $storeID = FormLib::get('store');
        $dir = __DIR__;
        $bogoMsg = "";

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $data = array();
        $sets = array();
        $ret = '';
        $post = $_POST['test'];
        $get = $_GET['test'];
        $names = array(
            "12CdMer" => "12Up <span class=\"alert-success\">Coop Deals</span> All Merch",
            "16CdWell" => "16Up <span class=\"alert-success\"> Coop Deals</span> Wellness",
            "16CdMer" => "16Up <span class=\"alert-success\"> Coop Deals</span> All Merch",
            "12CdDel" => "12Up <span class=\"alert-success\"> Coop Deals</span> Deli",
            "16CdPro" => "16Up <span class=\"alert-success\"> Coop Deals</span> Produce",
            "16TprPro" => "16Up <span class=\"alert-warning\">TPR</span> Produce",
            "12TprDel" => "12Up <span class=\"alert-warning\">TPR</span> Deli",
            "16TprWell" => "16Up <span class=\"alert-warning\">TPR</span> Wellness",
            "12TprMer" => "12Up <span class=\"alert-warning\">TPR</span> All Merch",
            "16TprMer" => "16Up <span class=\"alert-warning\">TPR</span> All Merch",
            "16Bogo" => "16Up <span class=\"alert-info\">BOGO</span> All Merch",
            "12Bogo" => "12Up <span class=\"alert-info\">BOGO</span> All Merch",
        );

        if ($cycle == 'A') {
            $cycleAND = " AND ( b.batchName LIKE '% A %' OR b.batchName LIKE '% TPR %' ) ";
        } else {
            $cycleAND = " AND b.batchName LIKE '% B %' ";
        }
        
        $query = <<<SQL
SELECT 
    l.upc, b.batchName, b.owner,
    v.sections, s.narrow, s.signCount,
    CASE WHEN b.batchName LIKE '%BOGO%' THEN 1 ELSE 0 END AS isBogo
FROM batches b
    INNER JOIN batchList l ON l.batchID=b.batchID
    LEFT JOIN FloorSectionsListView v ON v.upc=l.upc
    LEFT JOIN SignProperties s ON s.upc=l.upc AND s.storeID=v.storeID
WHERE b.batchName LIKE '%Co-op Deals%'
    AND b.batchName LIKE '%$dealSet%'
    AND v.storeID = ?
    $cycleAND
SQL;
        $args = array($storeID);
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $cols = array('upc', 'batchName', 'owner', 'sections', 'narrow', 'isBogo');
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $upcLn = "<input type=\"hidden\" name=\"u[]\" value=\"$upc\" />";

            $batchName = $row['batchName'];
            $owner = $row['owner'];
            $sectionsStr = $row['sections'];
            $narrow = $row['narrow'];
            $isBogo = $row['isBogo'];

            $sectionsArr = explode(",", $sectionsStr);

            $isTPR = (strpos($batchName, ' TPR ') !== false) ? true : false;

            if ($isBogo == 1) {
                $bogoMsg = "<div class=\"alert alert-warning\">Remember to print BOGO deals using 
                    blank paper & Smart Signs layout, as BOGOs are not applicable for the 10%
                    Off deal for owners</div>";
                if (in_array($owner, array('PRODUCE', 'WELLNESS')) || $narrow == 1 || strpos($section, 'Bev') !== false) {
                    $sets['16Bogo'][] = $upcLn;
                } else {
                    $sets['12Bogo'][] = $upcLn;
                }
            }

            if ($owner == 'PRODUCE') {
                if ($isTPR) {
                    $sets['16TprPro'][] = $upcLn;
                } else {
                    $sets['16CdPro'][] = $upcLn;
                }
            }

            if ($owner == 'DELI') {
                if ($isTPR) {
                    $sets['12TprDel'][] = $upcLn;
                } else {
                    $sets['12CdDel'][] = $upcLn;
                }
            }

            if ($owner == 'WELLNESS') {
                if ($isTPR) {
                    $sets['16TprWell'][] = $upcLn;
                } else {
                    $sets['16CdWell'][] = $upcLn;
                }
            }

            if (!in_array($owner, array('WELLNESS', 'PRODUCE', 'DELI'))) {
                foreach ($sectionsArr as $section) {
                    if ($isTPR) {
                        if ($narrow == 1 || strpos($section, 'Bev') !== false) {
                            $sets['16TprMer'][] = $upcLn;
                        } else {
                            $sets['12TprMer'][] = $upcLn;
                        }
                    } else {
                        if ($narrow == 1 || strpos($section, 'Bev') !== false) {
                            $sets['16CdMer'][] = $upcLn;
                        } else {
                            $sets['12CdMer'][] = $upcLn;
                        }
                    }
                }
            }

        }
        echo $dbc->error();

        $forms = array(); 
        foreach ($sets as $set => $row) {
            $forms[$set] = "<form method=\"post\" action=\"../../admin/labels/SignFromSearch.php\" target=\"_blank\">";
        }
        foreach ($sets as $set => $row) {
            foreach ($row as $upcLn) {
                $forms[$set] .= $upcLn;
            }
        }
        foreach ($forms as $set => $form) {
            $forms[$set] .= "<div class=\"form-group\"><input type=\"submit\" class=\"btn btn-default\" onclick=\"$(this).css('text-decoration', 'line-through'); $(this).parent().find('label').css('text-decoration', 'line-through');\">";
            $forms[$set] .= "&nbsp;<label>{$names[$set]}</label></div>";
            if (strpos($set, '12') !== false) {
                $forms[$set] .= "<input type=\"hidden\" name=\"signmod\" value=\"COREPOS\\Fannie\\API\\item\\signage\\Compact12UpL\" />";
            } else {
                $forms[$set] .= "<input type=\"hidden\" name=\"signmod\" value=\"COREPOS\\Fannie\\API\\item\\signage\\Compact16UpP\" />";
            }
            $forms[$set] .= "<input type=\"hidden\" name=\"store\" value=\"$storeID\" />";
            $forms[$set] .= "<input type=\"hidden\" name=\"item_mode\" value=3 />";
            $forms[$set] .= "</form>";
            $ret .= $forms[$set];
        }

        return <<<HTML
{$this->get_view()}
$bogoMsg
<div align="">
    <div class="row">
        <div class="col-lg-4" id="col-1">
            $ret 
        </div>
        <div class="col-lg-4" id="col-2"></div>
        <div class="col-lg-4"></div>
    </div>
</div>
HTML;
    }

    public function get_view()
    {

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $formYear = FormLib::get('year', false);
        $formCycle = FormLib::get('cycle', false);
        $formStore = FormLib::get('storeID', false);

        $set = FormLib::get('dealSet');
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
        $sel = '';
        foreach ($cycles as $cycle) {
            $sel = ($cycle == $formCycle) ? " selected " : "";
            $cycle_opts .= "<option value='$cycle' $sel>$cycle</option>";
        }

        $y = new DateTime();
        $curYear = $y->format('Y');
        $years = array();
        $yearsHTML = '';
        $years[] = $curYear;
        $curMonth = $y->format('m');
        if ($curMonth == 12)
            $years[] = $curYear + 1;
        if ($curMonth == 1)
            $years[] = $curYear - 1;
        foreach ($years as $year) {
            $sel = ($formYear == $year) ? ' selected ' : '';
            $yearsHTML .= "<option value=\"$year\" $sel>$year</option>";
        }

        $storePicker = FormLib::storePicker();
        $store_opts = $storePicker['html'];

        $form = sprintf('
            <form method="get" action="CoopDealsSignsPageNew.php">
                <div class="form-group"><div class="input-group">
                    <div class="input-group-addon">&nbsp;Year: </div>
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
                <div class="form-group">
                    %s
                </div>
                <div class="form-group"><div class="input-group">
                    <button type="submit" class="btn btn-default">Load</button>
                </div></div>
                <div style="border-bottom: 1px solid lightgrey; padding: 5px; margin: 20px"></div>
            </form>
        ', $yearsHTML, $deal_opts, $cycle_opts, $store_opts);
        return <<<HTML
<div class="row">
    <div class="col-lg-4">$form</div>
    <div class="col-lg-4"></div>
    <div class="col-lg-4"></div>
</div>
HTML;
    }

    public function css_content()
    {
        return <<<HTML
.btn-success, .btn-warning {
    width: 150px;
}
.input-group-addon {
    width: 50x;
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
