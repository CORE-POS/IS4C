<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class NewStoreFloorsPage extends FannieRESTfulPage
{
    protected $header = 'Sales Floor Map';
    protected $title = 'Sales Floor Map';
    //protected $window_dressing = false;

    public $description = '[Sales Floor Map] has labeled map(s) of the store(s)';

    public function preprocess()
    {
        $this->addRoute('get<upload>');

        return parent::preprocess();
    }

    public function get_id_view()
    {
        return $this->get_view();
    }

    public function get_view()
    {
        $storeID = FormLib::get('id');
        $section = FormLib::get('section');
        $subSection = FormLib::get('subSection');
        $storeName = ($storeID == 1) ? 'Hillside' : 'Denfeld';

        return <<<HTML
<input type="hidden" id="storeID" value="$storeID" />
<input type="hidden" id="section" value="$section" />
<input type="hidden" id="subSection" value="$subSection" />
<div class="row">
    <div class="col-lg-10">
        <div style="width: 1200px; height: 100%">
            <div id="window-container">
                <div id="window-top-frame">
                    <h4>$storeName</h4>
                </div>
                <div style="background: white; width: 1250px; height:1000px; overflow: auto; border: 1px solid ;" class="dragless" id="canvas-container">
                    <canvas class="canvas" id="myCanvas" width="1200" height="900" style="border:0px solid transparent; background: linear-gradient(45deg, lightgrey, white);"></canvas>
                    <canvas class="canvas" id="myCanvas-overlay" width="1200" height="900" style=""></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-2">
        <div style="height: 45px;"></div>
        <div class="form-group">
            <label for="show-subsections">Show Subsections &nbsp; </label>
            <input class="" type="checkbox" id="show-subsections" checked />
        </div>
        <div class="form-group">
            <label for="show-aisle-names">Show Aisle Names&nbsp; </label>
            <input class="" type="checkbox" id="show-aisle-names" checked />
        </div>
    <div class="form-group">
    </div>
    </div>
</div>

HTML;
    }

    public function css_content()
    {
        return <<<HTML
#canvas-container {
    position: relative;
}
.canvas {
    position: absolute; top: 0; left: 0;
}
HTML;
    }

    public function javascript_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $storeID = FormLib::get('id');

        $data = array();
        $dataStr = '';
        $prep = $dbc->prepare("SELECT * FROM CanvasFloorSections WHERE storeID = ?");
        $res = $dbc->execute($prep, array($storeID));
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['id'];
            $x = $row['x'];
            $y = $row['y'];
            $w = $row['w'];
            $h = $row['h'];
            $type = $row['type'];
            $name = $row['name'];
            $optA = $row['optA'];
            $optB = $row['optB'];
            $adjX = $row['adjX'];
            $adjY = $row['adjY'];
            $adjZ = $row['adjZ'];
            $orientation = $row['orientation'];

            $data[] = array($x, $y, $w, $h, $type, $name, $optA, $optB, array($adjX, $adjY, $adjZ), $orientation);
            $dataStr .= " [ $x, $y, $w, $h, $type, '$name', $optA, $optB, [$adjX, $adjY, $adjZ], $orientation ], ";
        }
        $data = json_encode($data);

        $subdata = array();
        $subdataStr = '';
        $prep = $dbc->prepare("SELECT * FROM CanvasFloorSubSections WHERE storeID = ?");
        $res = $dbc->execute($prep, array($storeID));
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['id'];
            $x = $row['x'];
            $y = $row['y'];
            $w = $row['w'];
            $h = $row['h'];
            $name = $row['name'];
            $symbol = $row['symbol'];

            $subdata[] = array($x, $y, $w, $h, $name, $symbol  );
            $subdataStr .= " [ $x, $y, $w, $h, '$name', '$symbol' ], ";
        }
        $subdata = json_encode($subdata);

        return <<<JAVASCRIPT
$(document).mousedown(function(e){
    x = e.offsetX;
    y = e.offsetY;
    //console.log(x+', '+y);
});

var alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
var c = document.getElementById("myCanvas");
var ctx = c.getContext("2d");
ctx.font = "14px Helvetica";

var d = document.getElementById("myCanvas-overlay");
var ctx2 = d.getContext("2d");
ctx2.font = "14px Helvetica";

var drawPointer = function(x, y)
{
    y += 6;
    x -= 7;
    ctx.lineWidth = 5;
    ctx.strokeStyle = 'black';
    ctx.beginPath();
    ctx.moveTo(x, y+25);
    ctx.lineTo(x+25,y);
    ctx.moveTo(x+5, y);
    ctx.lineTo(x+25, y);
    ctx.moveTo(x+25, y);
    ctx.lineTo(x+25, y+20);
    ctx.stroke();
    ctx.lineWidth = 1;
}


$(document).ready(function(){
//    $('#window-container').css('width', '100%')
//        .css('height', '100%');
//    $('#window-top-frame').css('width', '100%');
//    $('#canvas-container').css('width', '100%')
//        .css('height', '100%');
});

var mWidth = $('#myCanvas').attr("width");
var mHeight = $('#myCanvas').attr("height");

/*
INSERT INTO CanvasFloorSections (x, y, w, h, type, name, optA, optB, adjX, adjY, adjZ, orientation, storeID) VALUES (
INSERT INTO CanvasFloorSubSections (x, y, w, h, name, symbol, storeID) VALUES (
*/

var data = [$dataStr];
var subsections = [$subdataStr];

var section = $('#section').val();
var subSection = $('#subSection').val().toUpperCase();
var currentItem = [section, subSection];

var size = 2;

var dt = 5;
var interval = 0;

var drawMap = function() {

    $.each(data, function(i,arr) {

        let type = arr[4]; // type: 1 == blue (aisle), type: 2 == plum (additional objects)

        if (type == 1) {
            ctx.fillStyle = 'lightblue';
        }
        if (type == 2) {
            ctx.fillStyle = 'plum';
        }
        ctx.strokeStyle = 'black';

        // Print Rectangle 
        if (arr[6] == '45') {
            ctx.rotate(-0.785398);
        }
        if (arr[6] == '315') {
            ctx.rotate(-5.4978);
        }
        ctx.beginPath();
        ctx.rect(arr[0], arr[1], arr[2], arr[3]);
        ctx.stroke();
        ctx.fill();
        if (arr[6] == '45') {
            ctx.rotate(+0.785398);
        }
        if (arr[6] == '315') {
            ctx.rotate(+5.4978);
        }

        ctx.fillStyle = 'black';
        ctx.font = "12px Helvetica";

        ctx.font = "20px";
        ctx.fillStyle = 'black';

        // print sub-sections
        let numSections = arr[6];
        let sectAlign = (arr[4] == true) ? 'y' : 'x';
        let sectionSpacing = (arr[4] == true) ? arr[3] / numSections : arr[2] / numSections;
        sectionSpacing -= 5;
        //console.log(sectionSpacing);

        let x = arr[0];
        let y = arr[1] - sectionSpacing /2 ;
        let xmod = 0;
        if (arr[7] == 'w') {
            x -= 10;
            xmod = -8;
        } else if (arr[7] == 'e') {
            x += 7 + arr[2];
            xmod = 3;
        }

        //let alpha = [];
        //for (let i = 0; i <= numSections; i++) {
        //    alpha.push(alphabet[i]);
        //}
        //alpha = alpha.reverse();


    });

    let showSubs = $('#show-subsections').is(':checked');
    if (showSubs) {
        // manual subsection locations
        for (let i = 0; i < subsections.length; i++) {
            if (subsections[i].hasOwnProperty(0) && subsections[i].hasOwnProperty(1)) {
                ctx.fillStyle = 'green';
                x = subsections[i][0];
                y = subsections[i][1];
                ctx.fillText(subsections[i][5], x, y); 
                ctx.fillStyle = 'lightblue';
            }
        }
    }

    let showAisleNames = $('#show-aisle-names').is(':checked');
    if (showAisleNames) {
        // name each aisle
        $.each(data, function(i,arr) {
            // if index exists
            let mod = 0;
            let modx = 0;
            let mody = 0;
            if (arr.hasOwnProperty(8)) {
                mod = arr[8][0];
                modx = arr[8][1];
                mody = arr[8][2];
            }
            ctx.fillStyle = 'black';
            ctx.font = "12px Helvetica";
            ctx.save();
            ctx.translate(arr[0], arr[1]+mod);
            //ctx.rotate(-90 * Math.PI / 360);
            if (arr.hasOwnProperty(8)) {
                if (arr[9] == 1) {
                } else {
                    ctx.rotate(-Math.PI/2);
                }
            } else {
                ctx.rotate(-Math.PI/2);
            }
            ctx.fillText(arr[5], modx, mody);
            ctx.restore();
            ctx.font = "20px";
            ctx.fillStyle = 'lightblue';
        });

    }

    // highlight "current item" location 
    if (currentItem.length > 0) {
        if (currentItem[1].length > 0) {
            // item has sub-section set
            let x = 0;
            let y = 0;
            $.each(subsections, function(a, b) {
                if (subsections[a][4] == currentItem[0]) {
                    if (subsections[a][5] == currentItem[1]) {
                        x = subsections[a][0];
                        y = subsections[a][1];
                    }
                }
            });

            //setInterval('flashRect('+x+', '+y+', 5, 5)', 500);
            //drawPointer(x-24, y);

            setInterval('rectIter('+x+', '+y+', 5, 5)', 300);

        } else {
            // item does not have sub-section set
            let x = 0;
            let y = 0;
            let w = 0;
            let h = 0;
            $.each(data, function(a, b) {
                //console.log('hi: '+data[a][5]);
                if (data[a][5] == currentItem[0]) {
                    //console.log(data[a][5]);
                    //console.log('anything');
                    x = data[a][0];
                    y = data[a][1];
                    w = data[a][2];
                    h = data[a][3];
                }
            });

            //setInterval('flashRect('+x+', '+y+', '+w+', '+h+')', 500);
            w -= 8;
            h -= 8;
            y += 8;
            x += 2;
            setInterval('rectIter('+x+', '+y+', '+w+', '+h+')', 300);
        }
        
        ctx.fillStyle = 'lightblue';
        ctx.strokeStyle = 'lightblue';
    }

};
drawMap();

var flashRect = function(x, y, w, h)
{
        let colors = ['green', 'rgb(0,185,185)'];
        ctx.lineWidth = 5;
        ctx.strokeStyle = colors[dt];
        if (w == 5 && h == 5) {
            ctx.rect(x-5, y-13, 18, 17); 
        } else {
            ctx.rect(x, y, w, h); 
        }
        ctx.stroke();
        dt++;
        if (dt == colors.length)
            dt = 0;
        ctx.lineWidth = 1;

        ctx.fillStyle = 'lightblue';
        ctx.strokeStyle = 'lightblue';
}

$('#show-subsections, #show-aisle-names').on('click', function() {
    ctx.clearRect(0, 0, 1600, 1900);
    drawMap();
});

/*
    New Stuff (using ctx2)
*/

ctx2.fillStyle = 'white';
ctx2.strokeStyle = 'black';

var drawRect = function(x, y, w, h) {
    ctx2.beginPath();
    ctx2.rect(x, y, w, h);
    ctx2.stroke();
}

var clr = function(x, y, w, h) {
    ctx2.clearRect(x-5, y-5, w+10, h+10);
}

var rectIter = function(x, y, w, h)
{
    clr(0, 0, 1200, 1200)
    x += 2;
    y -= 7;

    x = x - dt;
    y = y - dt;
    w = w + dt*2;
    h = h + dt*2;

    ctx2.fillStyle = '';
    ctx2.strokeStyle = 'purple';

    drawRect(x, y, w, h);

    ctx2.fillStyle = '';
    ctx2.strokeStyle = 'black';

    if (interval == 0) {
        dt++;
    } else {
        dt--;
    }
    if (dt == 8 || dt == 5) {
        if (interval == 0) {
            interval = 1;
        } else {
            interval = 0;
        }
    }
}



JAVASCRIPT;
    }
}

FannieDispatch::conditionalExec();

