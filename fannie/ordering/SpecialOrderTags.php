<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use Endroid\QrCode\QrCode;
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class SpecialOrderTags extends FannieRESTfulPage
{
    protected $title = "Fannie :: Special Orders";
    protected $header = "Special Orders";
    public $description = '[Special Order Tags] prints scannable, barcoded order tags';
    public $page_set = 'Special Orders';

    public function preprocess()
    {
        $this->__routes[] = 'get<toIDs>';
        $this->__routes[] = 'get<custom>';
        $this->__routes[] = 'post<custom>';

        return parent::preprocess();
    }

    public function get_toIDs_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB').$dbc->sep();

        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH','font/');
        }
        if (!class_exists('FPDF')) {
            include(dirname(__FILE__) . '/../src/fpdf/fpdf.php');
        }

        $pdf=new FPDF('P','mm','Letter'); //start new instance of PDF
        $pdf->Open(); //open new PDF Document

        $count = 0;
        $posX = 0;
        $posY = 0;
        $date = date("m/d/Y");
        $infoP = $dbc->prepare("SELECT ItemQtty,total,regPrice,p.card_no,description,department,
            CASE WHEN p.card_no=0 THEN o.lastName ELSE c.LastName END as name,
            CASE WHEN p.card_no=0 THEN o.firstName ELSE c.FirstName END as fname,
            CASE WHEN o.phone is NULL THEN m.phone ELSE o.phone END as phone,
            discounttype,quantity,o.sendEmails,
            p.mixMatch AS vendorName,
            o.tagNotes
            FROM {$TRANS}PendingSpecialOrder AS p
            LEFT JOIN custdata AS c ON p.card_no=c.CardNo AND personNum=p.voided
            LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
            LEFT JOIN {$TRANS}SpecialOrders AS o ON o.specialOrderID=p.order_id
            WHERE trans_id=? AND p.order_id=?");
        $flagP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET charflag='P'
            WHERE trans_id=? AND order_id=?");
        $idP = $dbc->prepare("SELECT trans_id FROM {$TRANS}PendingSpecialOrder WHERE
            trans_id > 0 AND order_id=? ORDER BY trans_id");
        $signage = new \COREPOS\Fannie\API\item\FannieSignage(array());
        foreach ($this->toIDs as $toid){
            if ($count % 4 == 0){ 
                $pdf->AddPage();
                $pdf->SetDrawColor(0,0,0);
                $pdf->Line(108,0,108,279);
                $pdf->Line(0,135,215,135);
            }

            $posX = $count % 2 == 0 ? 5 : 115;
            $posY = ($count/2) % 2 == 0 ? 10 : 145;
            $pdf->SetXY($posX,$posY);

            $tmp = explode(":",$toid);
            $tid = $tmp[0];
            $oid = $tmp[1];

            $row = $dbc->getRow($infoP, array($tid, $oid));

            $tagNotes = $row['tagNotes'];
            $tagObj = json_decode($tagNotes);
            $note = null;
            foreach ($tagObj as $item => $notes) {
                if ($row['description'] == $item) {
                    $note = $notes;
                }
            }

            // flag item as "printed"
            $res2 = $dbc->execute($flagP, array($tid, $oid));

            $res3 = $dbc->execute($idP, array($oid));
            $o_count = 0;
            $rel_id = 1;
            while ($row3 = $dbc->fetch_row($res3)){
                $o_count++;
                if ($row3['trans_id'] == $tid)
                    $rel_id = $o_count;
            }

            $pdf->SetFont('Arial','','12');
            $pdf->Text($posX+85,$posY,"$rel_id / $o_count");

            $pdf->SetFont('Arial','B','24');
            $pdf->Cell(100,10,$row['name'],0,1,'C');
            $pdf->SetFont('Arial','','12');
            $pdf->SetX($posX);
            $pdf->Cell(100,8,$row['fname'],0,1,'C');
            $pdf->SetX($posX);
            if ($row['card_no'] != 0){
                $pdf->Cell(100,8,"Owner #".$row['card_no'],0,1,'C');
                $pdf->SetX($posX);
            }

            $pdf->SetFont('Arial','','16');
            $pdf->Cell(100,9,$row['description'],0,1,'C');
            $pdf->SetX($posX);
            $pdf->Cell(100,9,"Cases: ".$row['ItemQtty'].' - '.$row['quantity'],0,1,'C');
            $pdf->SetX($posX);
            $pdf->SetFont('Arial','B','16');
            $pdf->Cell(100,9,sprintf("Total: \$%.2f",$row['total']),0,1,'C');
            $pdf->SetFont('Arial','','12');
            $pdf->SetX($posX);
            if ($row['discounttype'] == 1 || $row['discounttype'] == 2){
                $pdf->Cell(100,9,'Sale Price',0,1,'C');
                $pdf->SetX($posX);

            } elseif ($row['regPrice']-$row['total'] > 0){
                $percent = round(100 * (($row['regPrice']-$row['total'])/$row['regPrice']));
                $pdf->Cell(100,9,sprintf("Owner Savings: \$%.2f (%d%%)",
                        $row['regPrice'] - $row['total'],$percent),0,1,'C');
                $pdf->SetX($posX);
            }
            $pdf->Cell(100,6,"Tag Date: ".$date,0,1,'C');
            $pdf->SetX($posX);
            $pdf->Cell(100,6,"Dept #".$row['department'].' - '.$row['vendorName'],0,0,'C');
            $pdf->SetFont('Arial','B','12');
            $pdf->SetX($posX+50);
            $pdf->Cell(50,6,' ',0,1,'L');
            $pdf->SetFont('Arial','','12');
            $pdf->SetX($posX);
            $contactType = 'Ph';
            if ($row['sendEmails'] == 1) {
                $contactType = 'Email';
            } elseif ($row['sendEmails'] > 0) {
                $contactType = 'Text';
            }
            $pdf->Cell(100,6,$contactType.": ".$row['phone'],0,1,'C');
            $pdf->SetXY($posX,$posY+85);
            if ($note == null) {
                $pdf->Cell(160,10,"Notes: _________________________________");  
            } elseif (strlen($note) < 45) {
                // if we need 2 lines
                $pdf->SetXY($posX,$posY+80);
                $pdf->Cell(160,10,"Notes: ".$note);  
            } else {
                // split notes into 2 str
                $wrap = wordwrap($note, 45);
                $lines = explode("\n", $wrap);

                $pdf->SetXY($posX,$posY+80);
                $pdf->Cell(160,10,"Notes: ".$lines[0]);  
                $pdf->SetXY($posX,$posY+85);
                $pdf->Cell(160,10,$lines[1]);  
            }
            $pdf->SetX($posX);
            
            $upc = "454".str_pad($oid,6,'0',STR_PAD_LEFT).str_pad($tid,2,'0',STR_PAD_LEFT);

            $pdf = $signage->drawBarcode($upc, $pdf, $posX+10, $posY+95, array('height'=>14,'fontsize'=>8));

            $pdf->SetFont('Arial','','6');
            //$pdf->Rect($posX+50, $posY+97, 4, 4);
            $pdf->Line($posX+50, $posY+98, $posX+90, $posY+98);
            $pdf->SetXY($posX+49, $posY+91);
            $pdf->Cell(0, 10, 'C / VM / T / E');
            //$pdf->Rect($posX+50, $posY+107, 4, 4);
            $pdf->SetXY($posX+49, $posY+94.5);
            $pdf->SetFont('Arial','','5');
            $pdf->Cell(0, 10, 'Circle, Date, Initial');
            $pdf->SetFont('Arial','','6');
            $pdf->SetXY($posX+49, $posY+100);
            $pdf->Cell(0, 10, 'C / VM / T / E');
            $pdf->Line($posX+50, $posY+107, $posX+90, $posY+107);
            $pdf->SetXY($posX+49, $posY+103.5);
            $pdf->SetFont('Arial','','5');
            $pdf->Cell(0, 10, 'Circle, Date, Initial');

            /*
            $reorder_url = 'http://wholefoods.coop/reorder/' . $oid . '-' . $tid;
            if (class_exists('Endroid\\QrCode\\QrCode')) {
                $qrImg = tempnam(sys_get_temp_dir(), 'qrc') . '.png';
                $qrCode = new QrCode();
                $qrCode->setText($reorder_url)
                    ->setSize(60)
                    ->setPadding(2)
                    ->setErrorCorrection('high')
                    ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
                    ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
                    ->setLabelFontSize(6)
                    ->render($qrImg);

                $pdf->Image($qrImg, $posX+60, $posY+93);
                unlink($qrImg);
            }

            $pdf->SetXY($posX,$posY+115);
            $pdf->Cell(90,10,"Re-Order: $reorder_url", 0, 0, 'C');
            */

            $count++;
        }

        $pdf->Output();

        return false;
    }

    protected function post_custom_handler()
    {
        $row = array(
            'ItemQtty' => FormLib::get('cases'),
            'total' => FormLib::get('price'),
            'regPrice' => FormLib::get('regPrice'),
            'card_no' => '',
            'description' => FormLib::get('item'),
            'department' => FormLib::get('dept'),
            'phone' => '',
            'quantity' => FormLib::get('caseSize'),
            'vendorName' => '',
            'discountType' => 0,
            'orderCount' => 1,
            'partCount' => 1,
            'upc' => FormLib::get('upc')
        );
        list($first,$rest) = explode(' ', FormLib::get('name'), 2);
        if ($rest == '') {
            $row['name'] = $first;
            $row['fname'] = '';
        } else {
            $row['name'] = $rest;
            $row['fname'] = $first;
        }
        $tags = array($row, $row, $row, $row);
        $this->printTags($tags);

        return false;
    }

    private function printTags($tags)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB').$dbc->sep();

        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH','font/');
        }
        if (!class_exists('FPDF')) {
            include(dirname(__FILE__) . '/../src/fpdf/fpdf.php');
        }

        $pdf=new FPDF('P','mm','Letter'); //start new instance of PDF
        $pdf->Open(); //open new PDF Document

        $count = 0;
        $posX = 0;
        $posY = 0;
        $date = date("m/d/Y");
        $prevOID = false;

        $signage = new \COREPOS\Fannie\API\item\FannieSignage(array());
        foreach ($tags as $row) {
            if ($count % 4 == 0){ 
                $pdf->AddPage();
                $pdf->SetDrawColor(0,0,0);
                $pdf->Line(108,0,108,279);
                $pdf->Line(0,135,215,135);
            }

            $posX = $count % 2 == 0 ? 5 : 115;
            $posY = ($count/2) % 2 == 0 ? 10 : 145;
            $pdf->SetXY($posX,$posY);

            $pdf->SetFont('Arial','','12');
            $pdf->Text($posX+85,$posY,"{$row['partCount']} / {$row['orderCount']}");

            $pdf->SetFont('Arial','B','24');
            $pdf->Cell(100,10,$row['name'],0,1,'C');
            $pdf->SetFont('Arial','','12');
            $pdf->SetX($posX);
            $pdf->Cell(100,8,$row['fname'],0,1,'C');
            $pdf->SetX($posX);
            if ($row['card_no'] != 0){
                $pdf->Cell(100,8,"Owner #".$row['card_no'],0,1,'C');
                $pdf->SetX($posX);
            }

            $pdf->SetFont('Arial','','16');
            $pdf->Cell(100,9,$row['description'],0,1,'C');
            $pdf->SetX($posX);
            $pdf->Cell(100,9,"Cases: ".$row['ItemQtty'].' - '.$row['quantity'],0,1,'C');
            $pdf->SetX($posX);
            $pdf->SetFont('Arial','B','16');
            $pdf->Cell(100,9,sprintf("Total: \$%.2f",$row['total']),0,1,'C');
            $pdf->SetFont('Arial','','12');
            $pdf->SetX($posX);
            if ($row['discounttype'] == 1 || $row['discounttype'] == 2){
                $pdf->Cell(100,9,'Sale Price',0,1,'C');
                $pdf->SetX($posX);

            } elseif ($row['regPrice']-$row['total'] > 0){
                $percent = round(100 * (($row['regPrice']-$row['total'])/$row['regPrice']));
                $pdf->Cell(100,9,sprintf("Owner Savings: \$%.2f (%d%%)",
                        $row['regPrice'] - $row['total'],$percent),0,1,'C');
                $pdf->SetX($posX);
            }
            $pdf->Cell(100,6,"Tag Date: ".$date,0,1,'C');
            $pdf->SetX($posX);
            $pdf->Cell(50,6,"Dept #".$row['department'],0,0,'R');
            $pdf->SetFont('Arial','B','12');
            $pdf->SetX($posX+50);
            $pdf->Cell(50,6,$row['vendorName'],0,1,'L');
            $pdf->SetFont('Arial','','12');
            $pdf->SetX($posX);
            $pdf->Cell(100,6,"Ph: ".$row['phone'],0,1,'C');
            $pdf->SetXY($posX,$posY+85);
            $pdf->Cell(160,10,"Notes: _________________________________");  
            $pdf->SetX($posX);
            
            $pdf = $signage->drawBarcode($row['upc'], $pdf, $posX+10, $posY+95, array('height'=>14,'fontsize'=>8));

            /*
            $reorder_url = 'http://wholefoods.coop/reorder/' . $oid . '-' . $tid;
            if (class_exists('Endroid\\QrCode\\QrCode')) {
                $qrImg = tempnam(sys_get_temp_dir(), 'qrc') . '.png';
                $qrCode = new QrCode();
                $qrCode->setText($reorder_url)
                    ->setSize(60)
                    ->setPadding(2)
                    ->setErrorCorrection('high')
                    ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
                    ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
                    ->setLabelFontSize(6)
                    ->render($qrImg);

                $pdf->Image($qrImg, $posX+60, $posY+93);
                unlink($qrImg);
            }

            $pdf->SetXY($posX,$posY+115);
            $pdf->Cell(90,10,"Re-Order: $reorder_url", 0, 0, 'C');
            */

            $count++;
        }

        $pdf->Output();
    }

    public function javascript_content()
    {
        ob_start();
        ?>
        function toggleChecked(status){
            $(".cbox").each( function() {
                $(this).attr("checked",status);
            });
        }
        <?php

        return ob_get_clean();
    }

    private function getQueuedIDs($oids)
    {
        $username = FannieAuth::checkLogin();
        $cachepath = sys_get_temp_dir()."/ordercache/";
        if (file_exists("{$cachepath}{$username}.prints")){
            $prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
            foreach($prints as $oid=>$data){
                if (!in_array($oid,$oids)) {
                    $oids[] = $oid;
                }
            }
        }

        return $oids;
    }

    public function get_view()
    {
        $oids = FormLib::get('oids', array());
        if (!is_array($oids) || count($oids) == 0) {
            return '<div class="alert alert-danger">No order(s) selected</div>';
        } else {
            return $this->formTable($oids);
        }
    }

    protected function get_custom_view()
    {
        return <<<HTML
<form method="post" action="SpecialOrderTags.php">
    <input type="hidden" name="custom" value="1" />
    <div class="form-group">
        <label>Item</label>
        <input type="text" name="item" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Department</label>
        <input type="text" name="dept" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Regular Price</label>
        <input type="text" name="regPrice" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Actual Price</label>
        <input type="text" name="price" class="form-control" required />
    </div>
    <div class="form-group">
        <label># of Cases</label>
        <input type="text" name="cases" class="form-control" value="1" required />
    </div>
    <div class="form-group">
        <label>Case Size</label>
        <input type="text" name="caseSize" class="form-control" required />
    </div>
    <div class="form-group">
        <label>UPC</label>
        <input type="text" name="upc" class="form-control" required />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Print</button>
    </div>
</form>
HTML;
    }

    private function formTable($oids)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB').$dbc->sep();
        ob_start();
        echo '<form method="get">';
        echo '<input type="checkbox" id="sa" onclick="toggleChecked(this.checked);" />';
        echo '<label for="sa"><b>Select All</b></label>';
        echo '<table class="table table-bordered table-striped small">';
        $oids = $this->getQueuedIDs($oids);
        $infoP = $dbc->prepare("SELECT min(datetime) as orderDate,sum(total) as value,
            count(*)-1 as items,
            CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END as name,
            o.tagNotes
            FROM {$TRANS}PendingSpecialOrder AS p
            LEFT JOIN custdata AS c ON c.CardNo=p.card_no AND personNum=p.voided
            LEFT JOIN {$TRANS}SpecialOrders AS o ON o.specialOrderID=p.order_id 
            WHERE p.order_id=?");
        $itemP = $dbc->prepare("SELECT description,department,quantity,ItemQtty,total,trans_id
            FROM {$TRANS}PendingSpecialOrder WHERE order_id=? AND trans_id > 0 AND deleted <> 1");
        foreach ($oids as $oid) {
            $row = $dbc->getRow($infoP, array($oid));
            if ($row) {
                printf('<tr><td colspan="2">Order #%d (%s, %s)</td><td>Amt: $%.2f</td>
                    <td>Items: %d</td><td>&nbsp;</td></tr>',
                    $oid,$row['orderDate'],$row['name'],$row['value'],$row['items']);

                echo "<input type=\"hidden\" name=\"tagNotes\" value=\"{$row['tagNotes']}\" />";
            }

            $res = $dbc->execute($itemP, array($oid));
            while ($row = $dbc->fetch_row($res)){
                if ($row['department']==0){
                    echo '<tr><td>&nbsp;</td>';
                    echo '<td colspan="4">';
                    echo 'No department set for: '.$row['description'];
                    echo '</td></tr>';
                } else {
                    printf('<tr><td>&nbsp;</td><td>%s (%d)</td><td>%d x %d</td>
                    <td>$%.2f</td>
                    <td><input type="checkbox" class="cbox" name="toIDs[]" value="%d:%d" /></td>
                    </tr>',
                    $row['description'],$row['department'],$row['ItemQtty'],$row['quantity'],
                    $row['total'],$row['trans_id'],$oid);
                }
            }
        }
        echo '</table>';
        echo '<p>';
        echo '<button type="submit" class="btn btn-default">Print Tags</button>';
        echo '</p>';
        echo '</form>';

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->javascript_content()));
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertInternalType('array', $this->getQueuedIDs(array()));
        $this->toIDs = array('1:1');
        ob_start();
        $phpunit->assertEquals(false, $this->get_toIDs_handler());
        $pdf = ob_get_clean();
        $phpunit->assertNotEquals(0, strlen($this->formTable(array(1))));
    }
}

FannieDispatch::conditionalExec();

