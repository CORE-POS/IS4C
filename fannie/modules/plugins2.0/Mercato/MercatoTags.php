<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class MercatoTags extends FannieRESTfulPage
{
    protected $header = 'Pickup Tags';
    protected $title = 'Pickup Tags';

    protected function post_handler()
    {

        $pdf=new FPDF('P','mm','Letter'); //start new instance of PDF
        $pdf->Open(); //open new PDF Document
        $posX = 0;
        $posY = 0;
        $date = date("m/d/Y");
        $multi = FormLib::get('count');
        for ($i=0; $i<$multi; $i++) {
            if ($i % 4 == 0){ 
                $pdf->AddPage();
                $pdf->SetDrawColor(0,0,0);
                $pdf->Line(108,0,108,279);
                $pdf->Line(0,135,215,135);
            }

            $posX = $i % 2 == 0 ? 5 : 115;
            $posY = ($i/2) % 2 == 0 ? 30 : 165;
            $pdf->SetXY($posX,$posY);
            $pdf->SetFont('Arial','','16');

            $pdf->SetX($posX);
            $pdf->SetFont('Arial','B','24');
            $pdf->Cell(100,10,FormLib::get('name'),0,1,'C');
            $pdf->SetFont('Arial','','16');
            $pdf->SetX($posX);
            $pDate = FormLib::get('pDate');
            $pdf->Cell(100,10,date('D, M j', strtotime($pDate)),0,1,'C');

            $pdf->SetXY($posX, $posY + 80);
            $pdf->Cell(100,10,($i+1) . '/' . $multi,0,1,'C');
        }
        $pdf->Output('PickupOrder.pdf', 'I');

        return false;
    }

    protected function get_view()
    {
        $today = date('Y-m-d');
        return <<<HTML
<form method="post" action="MercatoTags.php" id="mtagform"
    onsubmit="setTimeout(function() { document.getElementById('mtagform').reset(); }, 500);">
<p>
    <div class="input-group">
        <span class="input-group-addon">Name</span>
        <input type="text" name="name" class="form-control" required>
    </div>
</p>
<p>
    <div class="input-group">
        <span class="input-group-addon">Pickup Date</span>
        <input type="text" name="pdate" class="form-control date-field" value="$today" required>
    </div>
</p>
<p>
    <div class="input-group">
        <span class="input-group-addon"># of tags</span>
        <input type="number" value="1" name="count" class="form-control" required>
    </div>
</p>
<p>
    <button type="submit" class="btn btn-default">Print Tags</button>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

