<?php
/*******************************************************************************

    Copyright 2014 Foods Co-op

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

use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

/*
 *  @class VendorAliasesPageOrdered
 *
 *  Used to create bulk ordering tags, tags will 
 *  be printed in the order they are added to
 *  get_id_print_handler() => $items->array();
 *
 */
class DeliVendorAliasPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Vendor Aliases";
    protected $header = "Price Deli Order Tags for Queue";

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    protected $skus = array(
'0684456' => array('BTEA ASSAM OG FT', '#', '1', 'Frontier', 'UNFI'),     
'0643940' => array('BAMARANTH', '#', '1', 'Frontier', 'UNFI'),     
//'0566604' => array('BEANS,OG2,GARBANZO', '108 OZ', '6', 'NATVAL'),
//'1270271' => array('COCOA,UNSWEETENED', '8 OZ', '6', 'GHIRAR')   

//'0948612' => array('CRANBERRIES,OG2,SWT', '#', '10', 'BULK B'),                    
//'0176602' => array('APRICOTS,OG2,TURKISH', '#', '28', 'BULK B'),                   
//'0570986' => array('SESAME SEED,OG2,WHT,HULL', '#', '25', 'BULK K'),               
//'0480590' => array('POLENTA,OG1', 'LB', '25', 'GIUSTO'),                           
//'0496950' => array('FLOUR,OG1,GOLD-N-WHITE', '#', '50', 'NTLWAY'),                 
//'0464651' => array('FLOUR,OG1,DURUM SEMOLINA', '#', '25', 'HLAND'),                
//'0444182' => array('SUGAR,OG2,POWDERED', '#', '50', 'FLCYSL'),                     
//'0657767' => array('COCONUT,OG1,SHRED,MED', '#', '25', 'BULK B'),                  
//'0464255' => array('SEA SALT,X-FINE,PAC OCEA', '#', '25', 'BULK C'),               
//'0467555' => array('SUGAR,LIGHT BROWN', '#', '25', 'BULK M'),                      
//'0176081' => array('TAMARI SOY SCE,OG2,GF', '5 GAL', '1', 'SAN-J'),                
//'0464917' => array('OATS,OG2,ROLLED,REG', '#', '25', 'BULK D'),                    
//'0484931' => array('WHEATBERRIES,OG1,HRD/RED', '#', '25', 'BULK D'),               
//'0191924' => array('RICE,OG2,BASMATI,BROWN', '#', '25', 'LUNDBG'),                 
//'0485409' => array('PNUT,JUMBO,RSTD/SLTD,USA', '#', '30', 'BULK F'),               
//'0498006' => array('ALMONDS,SLICED,NAT,THICK', '#', '25', 'BULK F'),               
//'0466037' => array('PECAN PCS,MED,FANCY,USA', '#', '30', 'BULK F'),                
//'0736389' => array('BAKING POWDER', '#', '5', 'FCOOP'),                            
//'0613984' => array('SUNFLWR KERNL,OG1,PSTRZD', '#', '25', 'BULK K'),               
//'0311282' => array('BAKING SODA', '#', '1', 'FCOOP'),                              
//'0653667' => array('PUMPKIN SEED,OG2', '#', '27', 'BULK K'),                       
//'0451971' => array('KAMUT,OG1,BERRIES', '#', '25', 'BULK D'),                      
//'0199034' => array('FLOUR,KAF,SIR GALAHAD AP', '#', '50', 'KINGAR'),               
//'0308791' => array('SOYBEANS,OG1', '#', '25', 'BULK H'),                           
//'0710905' => array('PEAS,OG1,SPLIT,GREEN', '#', '25', 'BULK H'),                   
//'0625905' => array('LENTILS,OG1,GREEN', '#', '25', 'BULK H'),                      
//'0854000' => array('LENTILS,OG2,RED,SPLIT', '#', '25', 'BULK H'),                  
//'0548404' => array('QUINOA,OG1,WHITE,ROYAL', '#', '25', 'BULK D'),                 
//'0920942' => array('SUGAR,OG2', 'LB', '25', 'BULK M'),                             
//'0402941' => array('SESAME OIL,TOASTED', '#', '35', 'SPCTRM'),                     
//'0134189' => array('MAYONNAISE,CANOLA', '1 GAL', '4', 'SPCTRM'),                   
//'0857482' => array('VINEGAR,BALSAMIC', '1.32 GAL', '2', 'SPCTRM'),                 
//'0326462' => array('VINEGAR,OG2,APL CDR,UNFL', '1 GAL', '4', 'SPCTRM'),            
//'0301382' => array('GREEN PEAS,OG2', '80 OZ', '4', 'SNOPAC'),                      
//'0982025' => array('SUGAR,OG2', 'LB', '50', 'BULK M'),                             
//'0746909' => array('WALNUTS,MEDIUM,PIECES', '#', '30', 'BULK F'),                  
//'0271023' => array('CASHEWS,PCS,RAW', '#', '25', 'BULK F'),                        
//'0314948' => array('RAISINS,OG2,FLAME,SELECT', '#', '30', 'BULK B'),               
//'0439927' => array('TOFU,OG2,BULK,FRM,PASTRZ', '4 LB', '6', 'SOYBOY'),             
//'0463919' => array('CORNMEAL,OG1,YELLOW', '#', '10', 'BULK C'),                    
//'0467514' => array('MOLASSES,BLACKSTRAP,NOSO', '#', '59', 'BULK M'),               
//'0505990' => array('SWISS,GRADE A', '7 #', '1', 'SWSVAL'),                         
//'0619221' => array('GINGER,CRYST,LO SUG,NOSO', '#', '11', 'BULK B'),               
//'0459255' => array('MAPLE SYRUP,GRADE B', 'GAL', '1', 'ANDSGR'),                   
//'0821918' => array('ENG MUFFIN,OG2,WHEAT', '12 OZ', '8', 'RUDIOG'),                
//'1522747' => array('ENG MUFFIN,OG2,WHITE', '12 OZ', '8', 'RUDIOG'),                
//'1584325' => array('CHERRIES,OG2,SWEET,DARK', '8 OZ', '12', 'EBFARM'),             
//'0593525' => array('WLD YLLOWFIN TUNA,UNSLTD', '66.5 OZ', '6', 'NATSEA'),          
//'0814228' => array('SPINACH,OG2,CUT,FRZN', '3 #', '6', 'WODSTK'),                  
//'0582650' => array('BLUEBERRIES,WILD,OG2,FRZ', '5 #', '4', 'WODSTK'),              
//'0582718' => array('MANGOS,OG2,FRZ', '5 #', '4', 'WODSTK'),                        
//'0778092' => array('MUSTARD,OG2,STONEGROUND', '128 OZ', '4', 'WODSTK'),            
//'0946061' => array('VEGENAISE,ORIG DAIRY FRE', '1 GAL', '4', 'FOLLOW'),            
//'0676502' => array('PREMIM SPRD,OG2,APRICOT', '10 OZ', '6', 'CROFTR'),             
//'0948703' => array('PASTA,ELBOW MACARONI', '1 LB', '12', 'DAVINC'),                
//'0595256' => array('CHIPS,MILK CHOC MAXI', '11.5 OZ', '12', 'GUITTD'),             
//'0644252' => array('BAGELS,PLAIN', '11.5 OZ', '12', 'BAGELS'),                     
//'0489658' => array('SYRUP,BROWN RICE,PREM', '#', '55', 'LUNDBG'),                  
//'0183376' => array('PEPPERS,CHIPOTLE', '7.5 OZ', '24', 'SANMAR'),                  
//'0475749' => array('COCONUT FLAVOR', '2 FZ', '1', 'FCOOP'),                        
//'0972869' => array('LEMON FLAVOR,A/F,OG2', '2 OZ', '1', 'FCOOP'),                  
//'0501759' => array('YEAST,ACTIVE,DRY', '2 #', '1', 'REDSTN'),                      
//'0465476' => array('RICE,WILD,XTRA FANCY,LON', '#', '25', 'BULK D'),               
//'1680271' => array('EGGROLL,VEGGIE BULK 60CT', '3 OZ', '60', 'LOTUSR'),            
//'1785591' => array('BEETS,OG2,RDY TO ET,WHL', '17.6 OZ', '12', 'GEFEN'),           
//'0396481' => array('TOMATO PASTE,OG2', '112 FZ', '6', 'MUIR'),                     
//'1957315' => array('BAKING BAR,SEMI SWT CHOC', '4 OZ', '12', 'GHIRAR')
    );

    public $description = '[Vendor Aliases] manages items that are sold under one or more UPCs that
        differ from the vendor catalog UPC.';

    public function preprocess()
    {
        $this->__routes[] = 'get<id><print>';
        $this->__routes[] = 'post<id><print>';

        return parent::preprocess();
    }

    public function get_id_print_handler()
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $mtLength = $store == 1 ? 3 : 7;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10,10,10);
        $pdf->SetFont('Arial', '', 7);
        $dbc = $this->connection;

        $skus = FormLib::get('sku');
        $descs = FormLib::get('description');
        $vendors = FormLib::get('vendor');
        $brands = FormLib::get('brand');
        $sizes = FormLib::get('size');
        $qtys = FormLib::get('qty');

        $posX = 5;
        $posY = 20;

        $inputlist = array();
        foreach ($skus as $k => $v) {
            $inputlist[$v][0] = $descs[$k];
            $inputlist[$v][1] = $sizes[$k];
            $inputlist[$v][2] = $qtys[$k];
            $inputlist[$v][3] = $brands[$k];
            $inputlist[$v][4] = $vendors[$k];
        }

        $list = (count($skus) > 1) ? $inputlist: $this->skus;

        foreach ($list as $sku => $row) {
            //$test = array_search($sku, $skus);
            //if ($test !== false) {
            //    $row[0] = $descs[$test];
            //}

            if ($row[1] == '#') 
                $row[1] = 'LB';
            $pdf->SetXY($posX+3, $posY);
            $pdf->Cell(0, 5, substr($row[0], 0, 25));
            $pdf->Ln(3);
            $pdf->SetX($posX+3);

            $pdf->Cell(0, 5, $sku.'  '.$row[3], 0, 1);
            $img = Image_Barcode2::draw($sku, 'code128', 'png', false, 20, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);
            $pdf->Image($file, $posX, $posY+7);
            unlink($file);

            //$pdf->SetXY($posX+36, $posY+16);
            //$pdf->Cell(0, 5, $row[4]);

            //$pdf->SetXY($posX+33, $posY+3);
            //$pdf->Cell(0, 5, $row[3]);

            $pdf->SetXY($posX+3, $posY+16);
            $pdf->Cell(0, 5, $row[2] . ' / ' . $row[1] . ' - ' . $row[4]);
            $pdf->SetXY($posX+35, $posY+15);
            $border = $mtLength == 7 ? 'TBR' : 'TBL';
            //$pdf->Cell(8, 4, sprintf('%.1f', $mtLength * $row['auto_par']), $border, 0, 'C');
            $posX += 52;
            if ($posX > 170) {
                $posX = 5;
                $posY += 31;
                if ($posY > 250) {
                    $posY = 20;
                    $pdf->AddPage();
                }
            }
        }
        $pdf->Output('skus.pdf', 'I');

        return false;
    }

    protected function post_id_print_handler()
    {
        return $this->get_id_print_handler();
    }

    protected function dont_print_nothing_handler()
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $mtLength = $store == 1 ? 3 : 7;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10,10,10);
        $pdf->SetFont('Arial', '', 7);
        $dbc = $this->connection;
        $upcs = FormLib::get('printUPCs', array());
        $upcs = array_map(function ($i) { return BarcodeLib::padUPC($i); }, $upcs);
        //$args = array($this->id, $store);
        /*
        $prep = $dbc->prepare('
            SELECT p.upc, p.description, v.sku, n.vendorName, p.brand, MAX(p.auto_par) AS auto_par
            FROM products AS p
                INNER JOIN VendorAliases AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                INNER JOIN vendors AS n ON p.default_vendor_id=n.vendorID
            WHERE v.vendorID=? 
                AND p.store_id=?
                AND p.upc IN (' . $inStr . ')
            GROUP BY p.description, v.sku, n.vendorName'); 
        */
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $args[] = $this->id;
        $args[] = $store;

        // new query for deli products we don't sell
        $prep = $dbc->prepare("
            SELECT * FROM 
                vendorItems AS v
                LEFT JOIN vendors AS s ON v.vendorID=s.vendorID
            WHERE v.sku IN ($inStr)
                AND v.vendorID = ?
            GROUP BY v.sku, s.vendorName
        ");

        /* old query for grocery bulk products we sell
        $prep = $dbc->prepare("
            SELECT * FROM 
                vendorItems AS v
                LEFT JOIN vendors AS s ON v.vendorID=s.vendorID
                LEFT JOIN products AS p ON p.default_vendor_id=v.vendorID
            WHERE v.upc IN ($inStr)
                AND v.vendorID = ?
                AND p.store_id = ?
            GROUP BY p.description, v.sku, s.vendorName
        ");
        */
         
        $res = $dbc->execute($prep, $args);
        $posX = 5;
        $posY = 20;

        $items = $this->plus;

        while ($row = $dbc->fetchRow($res)) {
            if (array_key_exists($row['upc'], $items)) {
                $upc = $row['upc'];
                $items[$upc]['sku'] = $row['sku'];
                $items[$upc]['upc'] = $upc;
                $items[$upc]['description'] = $row['description'];
                $items[$upc]['vendorName'] = $row['vendorName'];
                $items[$upc]['brand'] = $row['brand'];
                $items[$upc]['auto_par'] = $row['auto_par'];
            }
        }
        // while ($row = $dbc->fetchRow($res)) {
        foreach ($items as $upc => $row) {
            if (is_numeric($row['sku'])) {
            //$prepB = $dbc->prepare('SELECT units, size FROM vendorItems WHERE sku = ?');
                $prepB = $dbc->prepare('SELECT max(receivedDate), caseSize, unitSize, brand FROM PurchaseOrderItems WHERE sku = ?');
                $resB = $dbc->execute($prepB, $row['sku']);
                $tagSize = array();
                $tagSize = $dbc->fetch_row($resB);
                $pdf->SetXY($posX+3, $posY);
                $pdf->Cell(0, 5, substr($row['description'], 0, 25));
                $pdf->Ln(3);
                $pdf->SetX($posX+3);
                // sku
                $pdf->Cell(0, 5, $row['upc'], 0, 1);
                $img = Image_Barcode2::draw($row['sku'], 'code128', 'png', false, 13, 1, false);
                $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
                imagepng($img, $file);
                $pdf->Image($file, $posX, $posY+7);
                unlink($file);
                $pdf->SetXY($posX+3, $posY+13);
                // upc
                $pdf->Cell(0, 5, $row['vendorName'] . ' - ' . $row['sku']);
                $pdf->SetXY($posX+3, $posY+16);
                $pdf->Cell(0, 5, $tagSize['unitSize'] . ' / ' . $tagSize['caseSize'] . ' - ' . $tagSize['brand']);
                $pdf->SetXY($posX+35, $posY+15);
                $border = $mtLength == 7 ? 'TBR' : 'TBL';
                $pdf->Cell(8, 4, sprintf('%.1f', $mtLength * $row['auto_par']), $border, 0, 'C');
                $posX += 52;
                if ($posX > 170) {
                    $posX = 5;
                    $posY += 31;
                    if ($posY > 250) {
                        $posY = 20;
                        $pdf->AddPage();
                    }
                }
            }
        }
        $pdf->Output('skus.pdf', 'I');

        return false;
    }

    protected function delete_id_handler()
    {
        $sku = FormLib::get('sku');
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $delP = $dbc->prepare('
            DELETE FROM VendorAliases
            WHERE vendorID=?
                AND sku=?
                AND upc=?');
        $delR = $dbc->execute($delP, array($this->id, $sku, $upc));

        return 'VendorAliasesPageOrdered.php?id=' . $this->id;
    }

    protected function post_id_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $upc = FormLib::get('upc', '');
        $sku = FormLib::get('sku', '');
        if (trim($upc) === '' || trim($sku) === '') {
            return true;
        }
        $upc = BarcodeLib::padUPC($upc);
        $isPrimary = FormLib::get('isPrimary', 0);
        $multiplier = FormLib::get('multiplier');

        $alias = new VendorAliasesModel($dbc);
        $alias->vendorID($this->id);
        $alias->upc($upc);
        $alias->sku($sku);
        $alias->isPrimary($isPrimary);
        $alias->multiplier($multiplier);
        $saved = $alias->save();

        if ($isPrimary) {
            $alias->reset();
            $alias->vendorID($this->id);
            $alias->upc($upc);
            $alias->isPrimary(1);
            foreach ($alias->find() as $obj) {
                if ($obj->upc() != $upc) {
                    $obj->isPrimary(0);
                    $obj->save();
                }
            }
        }

        return true;
    }

    protected function post_id_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    protected function get_id_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ret = '<form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <p><div class="form-inline container-fluid">
                <label>UPC</label>
                <input class="form-control input-sm" type="text" name="upc" placeholder="UPC" />
                <label>SKU</label>
                <input type="text" class="form-control input-sm" name="sku" placeholder="SKU" />
                <label>Primary Alias <input type="checkbox" name="isPrimary" value="1" /></label>
                <label>Multiplier</label>
                <input type="number" min="0" max="100" step="0.01" name="multiplier" value="1.0" 
                    class="form-control input-sm" />
                <button type="submit" class="btn btn-submit btn-core">Add/Update</button>
            </div></p>
            </form>';

        $prep = $dbc->prepare("
            SELECT v.upc,
                v.sku,
                v.isPrimary,
                v.multiplier,
                p.description,
                p.size
            FROM VendorAliases AS v
                " . DTrans::joinProducts('v') . "
            WHERE v.vendorID=?
            ORDER BY v.sku,
                v.isPrimary DESC,
                v.upc");
        $ret .= '<table class="table table-bordered hidden">
            <thead>
                <th>Vendor SKU</th>
                <th>Our UPC</th>
                <th>Item</th>
                <th>Unit Size</th>
                <th>Multiplier</th>
                <th>&nbsp;</th>
                <th><span class="glyphicon glyphicon-print" onclick="$(\'.printUPCs\').prop(\'checked\', true);"></span></th>
            </thead><tbody>';
        $res = $dbc->execute($prep, array($this->id));
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<tr %s>
                <td>%s</td>
                <td><a href="../ItemEditorPage.php?searchupc=%s">%s</a></td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td><a class="btn btn-default btn-xs btn-danger" href="?_method=delete&id=%d&sku=%s&upc=%s">%s</a></td>
                <td><input type="checkbox" class="printUPCs" name="printUPCs[]" value="%d" /></td>
                </tr>',
                ($row['isPrimary'] ? 'class="info"' : ''),
                $row['sku'],
                $row['upc'], $row['upc'],
                $row['description'],
                $row['size'],
                $row['multiplier'],
                $this->id, $row['sku'], $row['upc'], FannieUI::deleteIcon(),
                $row['upc']
            );
        }
        $ret .= '</tbody></table>';
        $ret .= '<form id="tagForm" method="post">
            <input type="hidden" name="print" value="1" />
            <input type="hidden" name="id" value="' . $this->id . '" />
            <h5>To Print Order Tags</h5>
            <ul>
                Option A
                <ul>
                    <li>Directly edit the code for this page, entering the PLUs you would
                        like to print into $this->plus.</li>
                </ul>
                Option B
                <ul>
                    <li>Enter at least two items below.</li>
                </ul>
                <li>Click Print Scan Tags.</li>
                <li>Tags will print in an order matching that of the array.</li>
            </ul>
            <div class="form-group">
                <button type="button" class="btn btn-default"
                    onclick="$(\'.printUPCs:checked\').each(function (i) {
                        console.log($(this).val());
                        $(\'#tagForm\').append(\'<input type=hidden name=printUPCs[] value=\' + $(this).val() + \' />\');
                    }); $(\'#tagForm\').submit();"
                >Print Scan Tags</button>
            </div>
            <div>
                <a href="#" onclick="addEnterItemsRow();">Add Item</a>
            </div>
            <div id="enterItems">
                <div style="padding-bottom: 4px;">
                    <input type="text" name="sku[]" placeholder="sku"/>
                    <input type="text" name="description[]" placeholder="description"/>
                    <input type="text" name="vendor[]" placeholder="vendor"/>
                    <input type="text" name="brand[]" placeholder="brand"/>
                    <input type="text" name="size[]" placeholder="size"/>
                    <input type="text" name="qty[]" placeholder="case qty."/>
                </div>
            </div>
            </form>';

        return $ret;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$('input[type="checkbox"]').each(function(i,v){
    if (i == 1) {
        $(this).attr('checked', 'checked');
    }
});
var addEnterItemsRow = function() {
    var html = '<div style="padding-bottom: 4px;"> <input type="text" name="sku[]" placeholder="sku"/> <input type="text" name="description[]" placeholder="description"/> <input type="text" name="vendor[]" placeholder="vendor"/> <input type="text" name="brand[]" placeholder="brand"/> <input type="text" name="size[]" placeholder="size"/> <input type="text" name="qty[]" placeholder="case qty."/> </div>';
    $('#enterItems').append(html);
}
JAVASCRIPT;
    }

    public function helpContent()
    {
        return '<p>
            Used to create bulk ordering tags, tags will 
            be printed in the order they are added to
            get_id_print_handler() => $items->array();
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertInternalType('string', $this->get_id_view());
        $phpunit->assertInternalType('string', $this->post_id_view());
        $phpunit->assertInternalType('string', $this->delete_id_handler());
        $phpunit->assertEquals(true, $this->post_id_handler());
    }
}

FannieDispatch::conditionalExec();
