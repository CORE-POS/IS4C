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
            $pDate = FormLib::get('pdate');
            $pdf->Cell(100,10,date('D, M j', strtotime($pDate)),0,1,'C');
            $pdf->SetX($posX);
            $pdf->Cell(100,10,FormLib::get('ptime'),0,1,'C');
            $pdf->SetX($posX);
            $pdf->Cell(100,10,'Order #' . FormLib::get('order'),0,1,'C');

            $pdf->SetXY($posX, $posY + 70);
            $pdf->SetFont('Arial','B','20');
            $label = ($i+1) . ' of ' . $multi . ($multi > 1 ? ' bags' : ' bag');
            $pdf->Cell(100,10,$label,0,1,'C');
        }
        $fileName = FormLib::get('name') . '_' . $date;
        $pdf->Output($fileName.'.pdf', 'I');

        return false;
    }

    protected function post_id_handler()
    {
        $note = FormLib::get('note');
        if ($this->id != md5(trim($hash))) {
            $prep = $this->connection->prepare("INSERT INTO MercatoNotes (name, modified, note)
                VALUES (?, ?, ?)");
            $this->connection->execute($prep, array(FormLib::get('name'), date('Y-m-d H:i:s'), $note));
        }

        return false;
    }

    protected function get_id_handler()
    {
        $orders = $this->getOrders();
        echo json_encode($orders);

        return false;
    }

    private function getOrders()
    {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('tomorrow'));
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $prep = $this->connection->prepare("SELECT orderID, name, pDate
            FROM MercatoOrders
            WHERE pdate BETWEEN ? AND ?
                AND storeID=?
            ORDER BY pDate");
        $res = $this->connection->execute($prep, array($today, $tomorrow . ' 23:59:59', $store));
        $noteQ = $this->connection->addSelectLimit("SELECT note FROM MercatoNotes WHERE name=? ORDER BY modified DESC", 1);
        $noteP = $this->connection->prepare($noteQ);
        $orders = '<ul>';
        $dataSet = array();
        $i = 0;
        while ($row = $this->connection->fetchRow($res)) {
            $stamp = strtotime($row['pDate']);
            $json = array(
                'name' => $row['name'],
                'orderID' => $row['orderID'],
                'date' => date('Y-m-d', $stamp),
                'time' => date('g:ia', $stamp),
            );
            $note = $this->connection->getValue($noteP, array($row['name']));
            $json['note'] = $note ? $note : '';
            $json['hash'] = md5(trim($note));
            $dataSet[] = $json;
            $orders .= sprintf('<li><a href="" onclick="setFields(%s); return false;">%s %s</a></li>',
                $i, date('D g:ia', $stamp), $row['name']);
            $i++;
        }
        $orders .= '</ul>';

        return array(
            'html' => $orders,
            'json' => $dataSet,
        );
    }

    protected function get_view()
    {
        $today = date('Y-m-d');
        $orders = $this->getOrders();
        $jsonData = json_encode($orders['json']);
        $this->addOnloadCommand('setTimeout(refreshData, 15 * 60 * 1000);');

        return <<<HTML
<div class="row">
<div class="col-sm-6">
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
        <span class="input-group-addon">Pickup Time</span>
        <input type="text" name="ptime" class="form-control">
    </div>
</p>
<p>
    <div class="input-group">
        <span class="input-group-addon">Order #</span>
        <input type="number" value="" name="order" class="form-control">
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
</div>
<div class="col-sm-6">
    <div id="ordersColumn">
        {$orders['html']}
    </div>

    <div style="margin: 20px; text-decoration: none !important;">
        <textarea id="customerNotes" rows="10" class="form-control" placeholder="Save notes about the customer" onchange="saveNote();"></textarea>
        <input type="hidden" id="noteHash" />
        <input type="hidden" id="noteName" />
    </div>
</div>
</div>
<script>
var dataSet = {$jsonData};
function setFields(i) {
    obj = dataSet[i];
    $('input[name=name]').val(obj.name);
    $('input[name=pdate]').val(obj.date);
    $('input[name=ptime]').val(obj.time);
    $('input[name=order]').val(obj.orderID);
    $('#customerNotes').val(obj.note);
    $('#noteHash').val(obj.hash);
    $('#noteName').val(obj.name);
}
function refreshData() {
    $.ajax({
        method: 'get',
        data: 'id=refresh',
        dataType: 'json'
    }).done(function (resp) {
        $('div#ordersColumn').html(resp.html);
        dataSet = resp.json;
        setTimeout(refreshData, 15 * 60 * 1000);
    });
};
function saveNote() {
    var dstr = 'id=' + $('#noteHash').val() + '&note=' + $('#customerNotes').val();
    dstr += '&name=' + $('#noteName').val();
    $.ajax({
        method: 'post',
        data: dstr
    }).done(function (resp) {
    });
}
</script>
HTML;
    }
}

FannieDispatch::conditionalExec();

