<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FPDF')) {
    include_once(__DIR__ . '/../../../src/fpdf/fpdf.php');
}

class OverShortDepositSlips extends FanniePage 
{

    protected $header = 'Print Deposit Slips';
    protected $title = 'Print Deposit Slips';

    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Deposit Slips] generates PDF of bank-required deposit info.';
    public $themed = true;

    function preprocess(){
        if (FormLib::get_form_value('startDate') !== ''){
            $this->outputPDF();
            return False;
        }
        return True;
    }

    function outputPDF(){
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $start = FormLib::get_form_value('startDate');
        $end = FormLib::get_form_value('endDate');
        $store = FormLib::get('store');

        $font_size = 12;

        $pdf = new FPDF("P","mm","A4"); 
        $pdf->SetFont('Arial','',$font_size);
        $pdf->SetMargins(5,5,5);
        $pdf->SetAutoPageBreak(True,5);
        $pdf->AddPage();

        $dateClause = $start;
        if ($start != $end)
            $dateClause = $start." ".$end;

        /**
          Print check amounts in vertical columns
          Overly complicated. Bank requirements suck.
        */
        $query = "select checks from dailyChecks where
            date BETWEEN ? AND ?
                AND storeID=?
            order by 
              case when id >= 68 then id+1
              when id = 43 then 68
              else id end";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($start, $end, $store));
        $acc = array();
        $counts = array();
        $ckSum = 0;
        $width = 30;
        $num = 1;
        $breakon = 1;
        while($row = $dbc->fetch_row($result)){
            $real = $num;
            if (($num-1) % 6 == 0 && $num > 0 && $num != $breakon){
                $pdf->AddPage();
                $breakon = $num;
            }
            if ($num > 6){
                $real = ($num%6);
                if ($real == 0) $real = 6;
            }
            $vals = explode(",",$row[0]);

            $extra = array();

            $vcount = 0;
            foreach($vals as $v){
                if (is_numeric($v)) $vcount++;
            }

            // accumulate up to 56 checks
            // that's max column size
            // put any leftovers in $extra
            if ($vcount + count($acc) <= 56){
                foreach($vals as $v){
                    if (is_numeric($v)) array_push($acc,$v);
                }
            } else {
                foreach($vals as $v){
                    if (is_numeric($v)) array_push($extra,$v);
                }
            }

            // if there are $extra values, then
            // accumulator $acc is full so print
            // a column
            if (count($extra) > 0) {
                $str1 = "WFC #$num\n";
                $sum = 0;
                $str = "";
                for($j=0;$j<56;$j++){
                    if ($j < count($acc)){
                        $str .= sprintf("%.2f",$acc[$j]);
                        $sum += $acc[$j];
                    }
                    $str .= "\n";
                }
                $str2 = "TTL: ".sprintf("%.2f",$sum);
                $str3 = "CT: ".count($acc);
                array_push($counts,$sum);
                $ckSum += $sum;
                
                $j = 1;
                if ($real == 1) $j = 0;
                $k = 6;
                if ($real == 6) $k = 2;
                $pdf->SetXY(10 + ($width-0)*$j + ($width+7)*($real-1-$j),10);
                $pdf->MultiCell($width+($j==0?-1:$k),5,$str1,'R','L');
                $pdf->SetX(10+ ($width-0)*$j+ ($width+7)*($real-1-$j));
                $pdf->SetFontSize($font_size-1);
                $pdf->MultiCell($width+($j==0?-1:$k),4.35,$str,'R','L');
                $pdf->SetX(10 + ($width-0)*$j+ ($width+7)*($real-1-$j));
                $pdf->SetFont('Arial','B',$font_size-1);
                $pdf->MultiCell($width+($j==0?-1:$k),5,$str2,'R','L');
                $pdf->SetFont('Arial','',$font_size-1);
                $pdf->SetX(10 + ($width-0)*$j+ ($width+7)*($real-1-$j));
                $pdf->MultiCell($width+($j==0?-1:$k),5,$str3,'R','L');
                
                $acc = array();
                $num++;

                // put $extra values into the accumulator
                foreach($extra as $e) array_push($acc,$e);
            }
        }

        $str1 = "WFC #$num\n";
        $real = $num;
        if ($num % 7 == 0 && $num > 0) $pdf->AddPage();
        if ($num > 6){
            $real = ($num%6);
            if ($real == 0) $real = 6;
        }

        if (count($acc) > 0){
            $sum = 0;
            $str = "";
            for($j=0;$j<56;$j++){
                if ($j < count($acc)){
                    $str .= sprintf("%.2f",$acc[$j]);
                    $sum += $acc[$j];
                }
                $str .= "\n";
            }
            $str2 = "TTL: ".sprintf("%.2f",$sum);
            $str3 = "CT: ".count($acc);
            array_push($counts,$sum);
            $ckSum += $sum;
        
            $j = 1;
            if ($real == 1) $j = 0;
            $k = 6;
            if ($real == 6) $k = 2;
            $pdf->SetXY(10+($width-0)*$j + ($width+7)*($real-1-$j),10);
            $pdf->MultiCell($width+($j==0?-1:$k),5,$str1,'R','L');
            $pdf->SetX(10+($width-0)*$j+ ($width+7)*($real-1-$j));
            $pdf->SetFontSize($font_size-1);
            $pdf->MultiCell($width+($j==0?-1:$k),4.35,$str,'R','L');
            $pdf->SetX(10+($width-0)*$j+ ($width+7)*($real-1-$j));
            $pdf->SetFont('Arial','B',$font_size);
            $pdf->MultiCell($width+($j==0?-1:$k),5,$str2,'R','L');
            $pdf->SetFont('Arial','',$font_size);
            $pdf->SetX(10+($width-0)*$j+ ($width+7)*($real-1-$j));
            $pdf->MultiCell($width+($j==0?-1:$k),5,$str3,'R','L');
        }

        // seven columns can fit on a full page
        // four columns fit on the page containing the
        // deposit summary
        if ($num % 7 > 4) {
            $pdf->AddPage();
        }

        /* shift last box over a bit */
        $width += 3;

        $pdf->SetXY(($width+2)*4 + 5,10);
        $pdf->SetFillColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFontSize(12);
        $str = "Whole Foods Community\nCO-OP Deposit Slip\nChecking\n";
        $str .= trim(file_get_contents("acct"),"\r\n")."\n\n";
        $str .= "Date\t".date("m/d/y")."\n";
        $pdf->MultiCell(55,8,$str,1,'C');
        
        $pdf->SetTextColor(0,0,0);
        $str = "";
        for($i=0;$i<5 || $i < count($counts); $i++){
            $str .= "Check # ".($i+1).":";
            if ($i < count($counts))
                $str .= "\t\t$counts[$i]";
            $str .= "\n";
        }
        $pdf->SetX(($width+2)*4 + 5);
        //$pdf->MultiCell(55,8,$str,'LR','L');

        $dbstack = array('buyAmount'=>array(),
                 'depositAmount'=>array());
        $dbQ = "SELECT rowName,denomination,amt FROM dailyDeposit WHERE
            dateStr = ? AND storeID=? AND countType='Regular' AND rowName IN ('buyAmount','depositAmount')";
        $dbP = $dbc->prepare($dbQ);
        $dbR = $dbc->execute($dbP,array($dateClause,$store));
        while($dbW = $dbc->fetch_row($dbR)){
            $dbstack[$dbW[0]][$dbW[1]] = $dbW[2];
        }

        $coin = 0;
        if (isset($dbstack['depositAmount']['0.01'])) $coin += $dbstack['depositAmount']['0.01'];
        if (isset($dbstack['depositAmount']['0.05'])) $coin += $dbstack['depositAmount']['0.05'];
        if (isset($dbstack['depositAmount']['0.10'])) $coin += $dbstack['depositAmount']['0.10'];
        if (isset($dbstack['depositAmount']['0.25'])) $coin += $dbstack['depositAmount']['0.25'];
        $junk = 0;
        if (isset($dbstack['depositAmount']['Junk'])) $junk += $dbstack['depositAmount']['Junk'];
        $cash = 0;
        if (isset($dbstack['depositAmount']['1.00'])) $cash += $dbstack['depositAmount']['1.00'];
        if (isset($dbstack['depositAmount']['5.00'])) $cash += $dbstack['depositAmount']['5.00'];
        if (isset($dbstack['depositAmount']['10.00'])) $cash += $dbstack['depositAmount']['10.00'];
        if (isset($dbstack['depositAmount']['20.00'])) $cash += $dbstack['depositAmount']['20.00'];
        if (isset($dbstack['depositAmount']['50.00'])) $cash += $dbstack['depositAmount']['50.00'];
        if (isset($dbstack['depositAmount']['100.00'])) $cash += $dbstack['depositAmount']['100.00'];

        $cashP = $dbc->prepare("SELECT LEFT(rowName, 12) AS rowName, SUM(amt) AS amt FROM dailyDeposit WHERE dateStr=? AND storeID=? AND countFormat=2 AND countType='Regular' AND rowName LIKE 'drop%' GROUP BY LEFT(rowName, 12)");
        $cashR = $dbc->execute($cashP, array($dateClause, $store));
        $cashTTL = 0;
        while ($cashW = $dbc->fetchRow($cashR)) {
            $label = str_replace('drop', '', $cashW['rowName']);
            if (is_numeric($label)) {
                $label = date('m/d', strtotime($label));
            }
            $pdf->SetX(($width+2)*4 + 5);
            $pdf->Cell(15, 8, $label, 'L', 0, 'L');
            $pdf->Cell(40,8,"\t$".sprintf('%.2f',$cashW['amt']),'TBR',1);
            $cashTTL += $cashW['amt'];
        }
        $pdf->SetX(($width+2)*4 + 5);
        $pdf->Cell(15,8,'Total','L',0,'L');
        $pdf->Cell(40,8,"\t$".sprintf('%.2f',$cashTTL),'TBR',1);

        /*
        $pdf->SetX(($width+2)*4 + 5);
        $pdf->Cell(15,8,'Checks','L',0,'L');
        $pdf->Cell(40,8,"\t$".sprintf('%.2f',$ckSum),'TBR',1);
        //$pdf->Cell(40,7,"",'TBR',1);
        $pdf->SetX(($width+2)*4 + 5);
        $pdf->Cell(15,8,'Coin','L',0,'L');
        $pdf->Cell(40,8,"\t$".sprintf('%.2f',$coin),'TBR',1);
        $pdf->SetX(($width+2)*4 + 5);
        $pdf->Cell(15,8,'Cash','L',0,'L');
        $pdf->Cell(40,8,"\t$".sprintf('%.2f',$cash),'TBR',1);
        $pdf->SetX(($width+2)*4 + 5);
        $pdf->Cell(15,8,'Other','L',0,'L');
        $pdf->Cell(40,8,"\t$".sprintf('%.2f',$junk),'TBR',1);
        $pdf->SetX(($width+2)*4 + 5);
        $pdf->Cell(15,8,'Total','L',0,'L');
        $pdf->Cell(40,8,"\t$".sprintf('%.2f',$junk+$cash+$coin+$ckSum),'TBR',1);
         */

        $pdf->SetTextColor(255,255,255);
        $pdf->SetX(($width+2)*4+5);
        $pdf->MultiCell(55,8,"Change Request",0,'C',1);

        $pdf->SetTextColor(0,0,0);
        $denoms = array('0.01','0.05','0.10','0.25','1.00','5.00','10.00', '20.00');
        $total = array_sum($dbstack['buyAmount']);
        if (!empty($dbstack['buyAmount'])){
            if ($dbstack['buyAmount']['0.01'] > 50)
                $total = $dbstack['buyAmount']['0.01'];
            foreach($denoms as $d){
                $pdf->SetX(($width+2)*4+5);
                $pdf->Cell(10,8,'$','L',0,'L');
                $pdf->Cell(10,8,"$d",0,0,'R');
                $pdf->Cell(35,8,$dbstack['buyAmount'][$d],'RB',1,'C');
            }
        }
        $pdf->SetX(($width+2)*4+5);
        $pdf->Cell(20,8,"Total:",'LB',0,'R');
        $pdf->Cell(35,8,sprintf('%.2f',$total),'RB',1,'C');

        $pdf->Output('deposit.pdf','I');
    }

    function body_content(){
        global $FANNIE_URL, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $this->addScript('js/count.js');
        ob_start();
        ?>
        <form action=OverShortDepositSlips.php method=get>
        <div class="row">
        <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">Date Range</div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" class="form-control date-field" id="startDate"
                        name="startDate" required />
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" class="form-control date-field" id="endDate"
                        name="endDate" required />
                </div>
            </div>
        </div>
        </div>
        <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">Recent Counts</div>
            <div class="panel-body">
                <div class="form-group">
                    <select class="form-control" onchange="existingDates(this.value);">
                    <option value=''>Select one...</option>
                    <?php
                    $res = $dbc->query('SELECT dateStr FROM dailyDeposit GROUP BY dateStr ORDER BY dateStr DESC');
                    $count = 0;
                    while($row = $dbc->fetch_row($res)) {
                        if ($count++ > 50) {
                            break;
                        }
                        echo '<option>'.$row['dateStr'].'</option>';
                    }
                    ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">Store</div>
            <div class="panel-body">
                <div class="form-group">
                <?php
                $stores = FormLib::storePicker('store', false);
                echo $stores['html'];
                ?>
                </div>
            </div>
        </div>
        </div>
        </div> <!-- end row -->
        <p>
            <button type=submit class="btn btn-default">Generate slips</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

