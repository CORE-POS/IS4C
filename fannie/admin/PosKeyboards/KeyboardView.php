<?php
include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FpdfWithBarcode')) {
    include(__DIR__ . '/../labels/FpdfWithBarcode.php');
}
if (!class_exists('PosKeyboard')) {
    include_once('PosKeyboard.php');
}

class KeyboardView extends FannieRESTfulPage 
{

    protected $header = 'POS Keyboard Keys';
    protected $title = 'POS Keyboard Keys';
    public $description = '[POS Keyboard] Define POS keyboard keys and print labels for
        mechanical and programmable keyboards.';
    protected $auth_classes = array('admin');
    protected $keySize = 50;
    protected $fontSize = 8;

    public function preprocess()
    {
        $this->__routes[] = "post<position>"; 
        $this->__routes[] = "post<keyboard>"; 
        $this->__routes[] = "post<rgb>"; 
        $this->__routes[] = "get<singleid>"; 

        return parent::preprocess();
    }

    public function post_rgb_handler()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $pos = FormLib::get('pos');
        $label = FormLib::get('label');
        $cmd = FormLib::get('cmd');
        $check_0 = (FormLib::get('check_0', false) != false) ? '1' : '0';
        $check_1 = (FormLib::get('check_1', false) != false) ? '1' : '0';
        $check_2 = (FormLib::get('check_2', false) != false) ? '1' : '0';
        $underline = '00000' . $check_2 . $check_1 . $check_0;
        $underline = bindec($underline);

        $rgb = FormLib::get('rgb');
        $temp = '';
        $temp .= str_pad(hexdec(substr($rgb, 2, 2)), 3, '0', STR_PAD_LEFT) . ",";
        $temp .= str_pad(hexdec(substr($rgb, 3, 2)), 3, '0', STR_PAD_LEFT) . ",";
        $temp .= str_pad(hexdec(substr($rgb, 5, 2)), 3, '0', STR_PAD_LEFT);
        $rgb = $temp;
        $labelRgb = FormLib::get('labelRgb');
        $temp = '';
        $temp .= str_pad(hexdec(substr($labelRgb, 1, 2)), 3, '0', STR_PAD_LEFT) . ",";
        $temp .= str_pad(hexdec(substr($labelRgb, 3, 2)), 3, '0', STR_PAD_LEFT) . ",";
        $temp .= str_pad(hexdec(substr($labelRgb, 5, 2)), 3, '0', STR_PAD_LEFT);
        $labelRgb = $temp;

        $args = array($label, $cmd, $rgb, $labelRgb, $underline, $pos);
        $prep = $dbc->prepare("UPDATE PosKeys 
            SET label = ?,
                cmd = ?,
                rgb = ?,
                labelRgb = ?,
                underline = ?
            WHERE pos = ?");
        $res = $dbc->execute($prep, $args);
        $er = $dbc->error();

        return header("location: KeyboardView.php?$er");
    }

    public function post_keyboard_handler()
    {
        echo $this->drawKeyboard();
        return false;
    }

    public function post_position_handler()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $position = FormLib::get('position');
        $col = FormLib::get('col');
        $value = FormLib::get('value');

        $args = array($value, $position);
        $query = "UPDATE PosKeyboard SET $col = ? WHERE position = ?";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);


        return false;
    }

    public function get_id_handler()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));

        $keyboard = new PosKeyboard($dbc);
        $keyboard->drawPDF($dbc);

        return false;
    }

    public function get_singleid_handler()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $id = FormLib::get('singleid');
        $n = FormLib::get('n');

        $keyboard = new PosKeyboard($dbc);
        $keyboard->drawSingleKey($dbc, $id, $n);

        return false;
    }

    public function drawKeyboard()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $k = new PosKeyboard($dbc);

        return $k->drawKeyboard();
    }

    public function getView()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));

        return <<<HTML
<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-success hidden" id="alert-success">Edit Successful</div>
    </div>
</div>
<div class="row">
    <div class="col-lg-2">
        <form type="get" name="generate-pdf-form">
            <input type="hidden" name="id" value="1" />
                <div class="form-group">
                    <label>Print Labels for Keys</label>
                    <button class="btn btn-default" type="submit">Generate PDF</button>
                </div>
        </form>
    </div>
    <div class="col-lg-4">
        <form type="get" name="generate-single-pdf-form" class="form-inline">
            <input type="hidden" name="singleid" id="singleid" value="" />
            <div class="form-group">
                <label>Print Multiples of Selected Label</label>
                <div>
                    <input type="number" class="form-control" name="n" id="n" value="1" />
                    <button class="btn btn-default" type="submit">Print</button>
                </div>
            </div>
        </form>
    </div>
    <form method="post" name="update-keys-form">
    <div class="col-lg-10">
        <input type="hidden" name="position" id="position" value=null />
    </div>
</div>
<div class="row">
    <input type="hidden" name="pos" id="pos" />
    <div class="col-lg-4">
        <div class="form-group">
            <label for="label">CMD</label>
            <input type="text" name="cmd" id="cmd" class="form-control" />
        </div>
    </div>
    <div class="col-lg-7">
        <div class="form-group">
            <label for="label">Label</label>
            <input type="text" name="label" id="label" class="form-control"/>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <label for="rgb">Background Color</label>
            <input type="color" name="rgb" id="rgb" class="form-control" />
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label for="labelRgb">Foreground Color</label>
            <input type="color" name="labelRgb" id="labelRgb" class="form-control" />
        </div>
    </div>
    <div class="col-lg-3">
        <div class="form-group">
            <label for="submit">&nbsp;</label>
            <button type="submit" id="submit" class="form-control btn btn-default">Update</button>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-8">
        <label>Underline</label> -  
        <label for="check_0">Line 1</label>: 
        <input type="checkbox" name="check_0" id="check_0" class="" />
        <label for="check_1">Line 2: </label>
        <input type="checkbox" name="check_1" id="check_1" class="" />
        <label for="check_2">Line 3: </label>
        <input type="checkbox" name="check_2" id="check_2" class="" />
    </div>
    <div class="col-lg-4">
    </div>
    </form>
</div>
<div id="container">
    <div id="keyboard-container">
        <h4><u>Preview</u></h4>
        {$this->drawKeyboard()}
    </div>
</div>
HTML;
    }

    public function printKeyboard()
    {
    }

    public function javascript_content()
    {
        $lastEdited = FormLib::get('editable', "false");

        return <<<JAVASCRIPT
var last = $lastEdited;
var id = null;
$('.cell').click(function(){
    id = $(this).attr('id');
    id = id.substr(3);
    $('#singleid').val(id);
    var label = $(this).attr('data-string');
    var cmd = $(this).attr('data-command');
    var rgb = $(this).attr('data-rgb');
    var labelRgb = $(this).attr('data-labelRgb');
    var underline = $(this).attr('data-underline');
    $('#label').val(label);
    $('#cmd').val(cmd);
    $('#btn-form').show();
    $('#rgb').val(rgbToHex(rgb));
    $('#labelRgb').val(rgbToHex(labelRgb));
    $('#pos').val(id);
    if ((underline & 1 << 0) != 0) {
        $('#check_0').prop('checked', true);
    } else {
        $('#check_0').prop('checked', false);
    }
    if ((underline & 1 << 1) != 0) {
        $('#check_1').prop('checked', true);
    } else {
        $('#check_1').prop('checked', false);
    }
    if ((underline & 1 << 2) != 0) {
        $('#check_2').prop('checked', true);
    } else {
        $('#check_2').prop('checked', false);
    }

});

var rgbToHex = function(rgb){
    let thirds = [];
    let hex = '#';
    thirds[0] = rgb.substring(0,3);
    thirds[1] = rgb.substring(4,7);
    thirds[2] = rgb.substring(8,11);
    let x = null
    let whole = null;
    let dec = null;
    $.each(thirds, function(k,v) {
        x = parseInt(v, 10) / 16;
        whole = parseInt(x, 10);
        dec = x - whole;
        dec *= 16;
        hex += whole.toString(16);
        hex += dec.toString(16);
    });
    return hex.toUpperCase();
}
var printMultiple = function(x) {
    // id = PosKeys.pos
}
JAVASCRIPT;
    }

    public function help_content()
    {
        return <<<HTML
HTML;
    }

    public function unitTest($phpunit)
    {
    }
}

FannieDispatch::conditionalExec();
