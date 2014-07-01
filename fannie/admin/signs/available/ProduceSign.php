<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require_once(dirname(__FILE__).'/../../../config.php');
require_once($FANNIE_ROOT.'admin/signs/available/SignClass.php');

class ProduceSign extends SignClass {

    function start_form(){
        global $FANNIE_URL;
        ?>
        <script type="text/javascript">
        $(document).ready(function(){
            $('#searchDescIn').autocomplete({
                source:'<?php echo $FANNIE_URL; ?>src/ajax/prodByDesc.php?super=6',
                close: function(){
                    if ($('#searchDescIn').val() != '')
                        $('#searchForm').submit();
                }
            });
        });
        </script>
        <form id="searchForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
        <input type="hidden" name="signtype" value="ProduceSign" />
        <input type="hidden" name="action" value="edit" />
        <b>Search</b> <input type="text" name="search_desc" id="searchDescIn" />
        <p />
        <input type="submit" value="Continue" />
        </form>
        <p />
        <a href="<?php echo $FANNIE_URL; ?>admin/import/byLC.php">Import Info</a>
        <?php
    }

    function edit_form(){
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $upc = str_pad($_REQUEST['search_desc'],13,'0',STR_PAD_LEFT);
        $q = $dbc->prepare_statement("SELECT CASE WHEN u.brand IS NULL THEN '' ELSE u.brand END as origin,
            CASE WHEN u.description IS NULL THEN p.description ELSE u.description END as goodDesc,
            CASE WHEN p.discounttype IN (1,2) THEN special_price ELSE normal_price END as price,
            CASE WHEN p.discounttype in (1,2) THEN 'SALE' ELSE 'REG' END as onSale,
            p.scale,p.local
            FROM products AS p LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE p.upc=?");
        $r = $dbc->exec_statement($q,array($upc));
        $w = $dbc->fetch_row($r);
        ?>
        <script type="text/javascript">
        $(document).ready(function(){
            getPreview();
            $('#formPane :input').keyup(getPreview);
        });
        function getPreview(){
            var dstr = $('#editForm').serialize();
            dstr = dstr.replace("action=pdf","action=preview");
            $.ajax({
                data: dstr,
                cache: false,
                url: '<?php echo $FANNIE_URL; ?>admin/signs/available/ProduceSign.php',
                success: function(data){
                    $('#previewPane').html(data);
                }       
            });
        }
        </script>
        <div id="previewPane" style="width:30%; border:solid 1px black; float: right;"></div>
        <div id="formPane" style="width:60%; float: left;">
        <form id="editForm" action="<?php echo $FANNIE_URL; ?>/admin/signs/available/ProduceSign.php" method="get">
        <input type="hidden" name="signtype" value="ProduceSign" />
        <input type="hidden" id="action" name="action" value="preview" />
        <input type="hidden" name="scale" value="<?php echo $w['scale']; ?>" />
        <table>
        <tr>
            <th>Item</th>
            <td><input type="text" name="desc" id="desc" value="<?php echo $w['goodDesc']; ?>" /></td>
        </tr>
        <tr>
            <th>Origin</th>
            <td><input type="text" name="origin" id="origin" value="<?php echo $w['origin']; ?>" /></td>
        </tr>
        <tr>
            <th>Price</th>
            <td><input type="text" name="price" id="price" value="<?php echo $w['price']; ?>" /></td>
        </tr>
        <tr>
            <th>On Sale</th>
            <td><?php echo $w['onSale']=='SALE' ? 'Yes' : 'No' ?></td>
        </tr>
        <tr>
            <th>Local</th>
            <td><?php echo $w['local']=='1' ? 'Yes' : 'No' ?></td>
        </tr>
        </table>
        <p />
        <input type="submit" value="Print" onclick="$('#action').val('pdf');return true;" />
        </form>
        </div>
        <div style="clear:both;"></div>
        <?php
    }

    function preview(){
        $desc = isset($_REQUEST['desc']) ? $_REQUEST['desc'] : '&nbsp;';
        $price = isset($_REQUEST['price']) ? $_REQUEST['price'] : '&nbsp;';
        if (isset($_REQUEST['scale']) && $_REQUEST['scale']==1)
            $price .= " / lb";
        $price = '$'.$price;
        $origin = isset($_REQUEST['origin']) ? $_REQUEST['origin'] : '&nbsp;';

        printf('<div id="pvDesc" style="text-align:center;">%s</div>
            <div id="pvPrice" style="text-align:center;font-size:200%%;">%s</div>
            <div id="pvOrigin" style="text-align:left;margin-left:5px;">%s</div>',
            $desc,$price,$origin);
    }

    function sign_pdf(){
        global $FANNIE_ROOT;
        $desc = isset($_REQUEST['desc']) ? $_REQUEST['desc'] : '&nbsp;';
        $price = isset($_REQUEST['price']) ? $_REQUEST['price'] : '&nbsp;';
        if (isset($_REQUEST['scale']) && $_REQUEST['scale']==1)
            $price .= " / lb";
        $price = '$'.$price;
        $origin = isset($_REQUEST['origin']) ? $_REQUEST['origin'] : '&nbsp;';

        require($FANNIE_ROOT.'src/fpdf/fpdf.php');
        define('FPDF_FONTPATH',$FANNIE_ROOT.'src/fpdf/font/');
        $pdf = new FPDF('L','in','Letter'); 
        $pdf->SetMargins(0.59,0.25,0.59);
        $pdf->SetAutoPageBreak(False,0.25);
        $pdf->AddPage();

        $pdf->AddFont('ScalaSans-Bold','B','ScalaSans-Bold.php');
        $pdf->AddFont('ScalaSans','','ScalaSans.php');
        $pdf->SetFont('ScalaSans','',22);

        $pdf->SetXY(0.25,5.87);
        $pdf->Cell(5.26,0.54,$desc,1,0,'C');
        $pdf->SetFont('ScalaSans-Bold','B',70);
        $pdf->SetXY(0.25,6.41);
        $pdf->Cell(5.26,1.3,$price,1,0,'C');
        $pdf->SetXY(0.25,7.71);
        $pdf->SetFont('ScalaSans','',18);
        $pdf->Cell(5.26,0.54,$origin,1,0,'L');
        $pdf->Close();
        $pdf->Output("sign.pdf","I");
    }
}

if (basename($_SERVER['PHP_SELF']) == 'ProduceSign.php')
    $ps = new ProduceSign();
