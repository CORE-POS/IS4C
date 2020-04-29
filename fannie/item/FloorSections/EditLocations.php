<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Community Co-op

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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
class EditLocations extends FannieRESTfulPage
{
    protected $header = 'Sub-section Editor';
    protected $title = 'Sub-section Editor';

    public $themed = true;
    public $description = '[Sub-section Editor] Edit product physical 
        location sub-sections.';
    protected $enable_linea = true;

    public function preprocess()
    {
        $this->addRoute('post<newSubSection>');
        $this->addRoute('post<section>');
        $this->addRoute('post<removesection>');
        $this->addRoute('post<newSection>');
        $this->addRoute('get<upc>');

        return parent::preprocess();
    }

    public function post_newSubSection_handler()
    {
        $json = array();

        $upc = FormLib::get('upc');
        $floorSectionID = FormLib::get('floorSectionID');
        $newSubSection = FormLib::get('newSubSection');
        $subSection = FormLib::get('subSection');
        $json['error'] = 0;

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $prep = $dbc->prepare("SELECT * FROM FloorSubSections WHERE floorSectionID = ? AND upc = ?");
        $res = $dbc->execute($prep, array($floorSectionID, $upc));
        $rows = $dbc->numRows($res);
        if (!ctype_alpha($newSubSection))  {
            if ($newSubSection == '\"0\"') {
                $prep = $dbc->prepare("DELETE FROM FloorSubSections WHERE floorSectionID = ? AND upc = ?");
                $res = $dbc->execute($prep, array($floorSectionID, $upc));
            }
        } elseif ($rows > 0) {
            $prep = $dbc->prepare("UPDATE FloorSubSections SET subSection = ? 
                WHERE floorSectionID = ? AND subSection = ? AND upc = ?");
            $res = $dbc->execute($prep, array($newSubSection, $floorSectionID, $subSection, $upc));
        } else {
            $prep = $dbc->prepare("INSERT INTO FloorSubSections (floorSectionID, subSection, upc) 
                VALUES (?, ?, ?)");
            $res = $dbc->execute($prep, array($floorSectionID, $newSubSection, $upc));
        }

        if ($er = $dbc->error())
                $json['error'] = $er;
        echo json_encode($json);

        return false; 
    }

    public function post_newSection_handler()
    {
        $upc = FormLib::get('upc');
        $floorSectionID = FormLib::get('floorSectionID');
        $newSection = FormLib::get('newSection');
        $json = array();
        $json['error'] = 0;

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $prep = $dbc->prepare("UPDATE FloorSectionProductMap SET floorSectionID = ? 
            WHERE upc = ? AND floorSectionID = ?;");
        $res = $dbc->execute($prep, array($newSection, $upc, $floorSectionID));
        if ($er = $dbc->error())
                $json['error'] = $er;
        echo json_encode($json);

        return false; 
    }

    public function post_removesection_handler()
    {
        $upc = FormLib::get('upc');
        $floorSectionID = FormLib::get('floorSectionID');
        $json = array();

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $prep = $dbc->prepare("DELETE FROM FloorSectionProductMap WHERE upc = ? AND floorSectionID = ?;");
        $res = $dbc->execute($prep, array($upc, $floorSectionID));

        $prep = $dbc->prepare("SELECT * FROM FloorSectionProductMap WHERE upc = ? AND floorSectionID = ?;");
        $res = $dbc->execute($prep, array($upc, $floorSectionID));
        if ($dbc->numRows($res) !== 0) {
            $json['error'] = 1;
        } elseif($er = $dbc->error()) {
            $json['error'] = $er;
        } else {
            $json['error'] = 0;
        }
        echo json_encode($json);

        return false; 
    }

    public function floorSectionSelect($options, $floorSectionID)
    {
        $select = "<select class=\"form-control edit-floorSection\">";
        foreach ($options as $id => $name) {
            $sel = ($floorSectionID == $id) ? "selected" : "";
            $select .= "<option value=\"$id\" $sel>$name</option>";
        }
        $select .= "</select>";

        return $select;
    }

    public function subSectionSelect($upc, $subSections, $floorSectionID, $dbc)
    {
        $prep = $dbc->prepare("SELECT subSection FROM FloorSubSections 
            WHERE upc = ? AND floorSectionID = ?");
        $res = $dbc->execute($prep, array($upc, $floorSectionID));
        $row = $dbc->fetchRow($res);
        $curSub = $row['subSection'];

        $options = '<option value=\"0\">&nbsp;</option>';
        foreach ($subSections as $k => $sub) {
            $sel = ($curSub == $sub) ? ' selected' : '';
            $options .= "<option value=\"$sub\" $sel>$sub</option>";
        }

        return array($curSub, $options);
    }

    public function get_upc_view()
    {
        /*
            Handheld scanner view
        */
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $STORE_ID = 2;

        $upc = FormLib::get('upc', false);
        $upc = BarcodeLib::padUpc($upc);

        $sections = array();
        $prep = $dbc->prepare("SELECT * FROM FloorSections WHERE storeID = 2 ORDER BY name");
        $res = $dbc->execute($prep);
        $sections = array();
        while ($row = $dbc->fetchRow($res)) {
            $sections[$row['floorSectionID']] = $row['name'];
        }

        $prep = $dbc->prepare("select floorSectionID, subSection from FloorSubSections group by floorSectionID, subSection;");
        $res = $dbc->execute($prep);
        $subSections = array();
        while ($row = $dbc->fetchRow($res)) {
            $subSections[$row['floorSectionID']][] = $row['subSection'];
        }

        $args = array($upc, $STORE_ID);
        $prep = $dbc->prepare("
            SELECT * 
            FROM FloorSectionProductMap AS map 
                RIGHT JOIN FloorSections as fs ON map.floorSectionID=fs.floorSectionID
            WHERE upc = ?
                AND fs.storeID = ?
        ");
        $res = $dbc->execute($prep, $args);

        // get product info
        $pProdInfo = $dbc->prepare("
            SELECT p.brand, p.description, p.department, m.super_name, d.dept_name
            FROM products AS p 
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE p.upc = ?
            GROUP BY p.upc
        ");

        $td = '';
        $th = '';
        $i = 0;
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $rProdInfo = $dbc->execute($pProdInfo, array($upc));
            $wProdInfo = $dbc->fetchRow($rProdInfo);
            $brand = $wProdInfo['brand'];
            $description = $wProdInfo['description'];
            $department = $wProdInfo['dept_name'];
            $superName = $wProdInfo['super_name'];
            $floorSectionID = $row['floorSectionID'];
            list($subSection, $subSectionOpts) = $this->subSectionSelect($upc, $subSections[$floorSectionID], $floorSectionID, $dbc);

            if ($i == 0) {
                $td .= sprintf("
                        <tr><td>%s</td></tr>
                        <tr><td>%s</td></tr>
                        <tr><td>%s</td></tr>",
                    $upc,
                    $brand, 
                    $description
                );
            }
            $i++;

            $stripe = ($i % 2 == 0) ? "#FFFFCC" : "transparent";

            $td .= sprintf("
                    <tr style=\"background-color: %s;\"><td><div><strong>Floor Section (%s)</strong></div>%s</td></tr>
                    <tr style=\"background-color: %s;\"><td><div><strong>Sub-Section (%s)</strong></div>%s</td></tr>
                    <td class=\"row-data\" id=\"row-data\" style=\"display: none\" 
                    data-upc=\"%s\" data-floorSectionID=\"%s\" data-subSection=\"%s\" data-storeID=\"%s\">
                    </td></tr>", 
                $stripe,
                $i,
                $this->floorSectionSelect($sections, $floorSectionID)
                    ." <span class=\"btn btn-default glyphicon glyphicon-trash btn-remove-section\" 
                        style=\"float:right; margin: 5px;\" data-floorSectionID=\"$floorSectionID\"></span>",
                $stripe,
                $i,
                "<select class=\"form-control edit-subsection\" data-floorSection=\"$floorSectionID\">$subSectionOpts</select>",
                $upc, $floorSectionID, $subSection, $STORE_ID
            );
        }
        echo $dbc->error();
        $this->addOnloadCommand('$(\'#upc\').focus();');
        $this->addOnloadCommand("enableLinea('#upc');\n");

        return <<<HTML
<div style="position: fixed; top: 0; right: 0; display:none; " class="alert alert-success" id="ajax-success">Saved</div>
<div style="position: fixed; top: 0; right: 0; display:none; " class="alert alert-success" id="ajax-danger">Error</div>
<div class="modal fade" tabindex="-1" role="dialog" id="mymodal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Product List</h4>
            </div>
            <div class="modal-body" id="modal-body">
                <p>Data</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <form name="upc-form" method="get">
                <div class="form-group">
                    <label for="upc">UPC</label>
                    <input name="upc" id="upc" value="$upc" class="form-control" autofocus pattern="\d*">
                </div>
                <div class="form-group" align="right">
                    <button class="btn btn-default">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
    </div>
</div>
<form class="form-inline" id="table-update">
<div class="table-responsive"><table class="table table-bordered table-sm small" id="handheldtable"><thead>$th</thead><tbody>$td</tbody></table></div>
</form>
<div class="form-group">
    <a class="btn btn-default" href="../../modules/plugins2.0/ShelfAudit/SaMenuPage.php">Menu</a>
</div>
HTML;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $STORE_ID = 2;

        $upcs = FormLib::get('upcs', false);
        $upcs = explode("\r\n",$upcs);

        $sections = array();
        $prep = $dbc->prepare("SELECT * FROM FloorSections WHERE storeID = 2 ORDER BY name");
        $res = $dbc->execute($prep);
        $sections = array();
        while ($row = $dbc->fetchRow($res)) {
            $sections[$row['floorSectionID']] = $row['name'];
        }

        $prep = $dbc->prepare("select floorSectionID, subSection from FloorSubSections group by floorSectionID, subSection;");
        $res = $dbc->execute($prep);
        $subSections = array();
        while ($row = $dbc->fetchRow($res)) {
            $subSections[$row['floorSectionID']][] = $row['subSection'];
        }

        list($inStr, $args) = $dbc->safeInClause($upcs);
        $args[] = 2;

        $prep = $dbc->prepare("
            SELECT * 
            FROM FloorSectionProductMap AS map 
                RIGHT JOIN FloorSections as fs ON map.floorSectionID=fs.floorSectionID
            WHERE upc IN ($inStr)
                AND fs.storeID = ?
                /* excluding floor sections from wellness, bulk, bread & deli */
                AND map.floorSectionID NOT IN (36,37,38,39,40,49,50,51,52,53,69)
        ");
        $res = $dbc->execute($prep, $args);

        // get product info
        $pProdInfo = $dbc->prepare("
            SELECT p.brand, p.description, p.department, m.super_name, d.dept_name
            FROM products AS p 
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE p.upc = ?
            GROUP BY p.upc
        ");

        $td = '';
        $th = '<th>UPC</th><th>Brand</th><th>Description</th><th>Floor Section ID</th><th>Sub-Section ID</th><th>Department.</th><th>Super Dept.</th>';
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $rProdInfo = $dbc->execute($pProdInfo, array($upc));
            $wProdInfo = $dbc->fetchRow($rProdInfo);
            $brand = $wProdInfo['brand'];
            $description = $wProdInfo['description'];
            $department = $wProdInfo['dept_name'];
            $superName = $wProdInfo['super_name'];
            $floorSectionID = $row['floorSectionID'];
            list($subSection, $subSectionOpts) = $this->subSectionSelect($upc, $subSections[$floorSectionID], $floorSectionID, $dbc);

            $td .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>
                    <td class=\"row-data\" style=\"display: none\" 
                    data-upc=\"%s\" data-floorSectionID=\"%s\" data-subSection=\"%s\" data-storeID=\"%s\">
                    </td></tr>", 
                $upc,
                $brand, 
                $description,
                $this->floorSectionSelect($sections, $floorSectionID)
                    ." <span class=\"form-control btn btn-default glyphicon glyphicon-trash btn-remove-section\"></span>",
                "<select class=\"form-control edit-subsection\" style=\"width: 50px;\">$subSectionOpts</select>"
                    ." <span class=\"form-control btn btn-default btn-add-subsection\">+</span>",
                $department,
                $superName,
                $upc, $floorSectionID, $subSection, $STORE_ID
            );
        }
        echo $dbc->error();

        return <<<HTML
<div class="alert alert-success ajax-resp-alert" id="ajax-success">Saved</div>
<div class="alert alert-success ajax-resp-alert" id="ajax-danger">Error</div>
<div class="modal fade" tabindex="-1" role="dialog" id="mymodal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Product List</h4>
            </div>
            <div class="modal-body" id="modal-body">
                <p>Data</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <form name="upcs">
            <div class="form-group">
                <label for="upcs">Paste a list of UPCs</label>
                <textarea name="upcs" id="upcs" rows=5 class="form-control"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" class="form-control">
            </div>
        </form>
    </div>
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <ul>
            <li><a href="SubLocationViewer.php">Sub-Location Mapper</a></li>
        </ul>
    </div>
</div>
<form class="form-inline" id="table-update">
<div class="table-responsive"><table class="table table-bordered table-sm small" id="mytable"><thead>$th</thead><tbody>$td</tbody></table></div>
</form>
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$('.btn-add-subsection').click(function(){
    var p = prompt('Enter section to add');
    if (p != null) {
        p = p.substring(0,1);
        c = confirm('Add sub-section: '+p);
        if (c == true) {
            $(this).parent('td').find('select')
                .append("<option value='"+p+"'>"+p+"</option>");
            $(this).parent('td').find('select option:last').attr('selected', 'selected');
            $(this).parent('td').find('select').trigger('change');
        }
    }
});
$('.btn-remove-section').click(function(){
    c = confirm("Permanently remove Floor Section?")
    if (c == true) {
        var rowData = $(this).parent('td').parent('tr').find('.row-data');
        var upc = rowData.attr('data-upc');
        if (upc == null) {
            upc = $('#upc').val();
        }
        var floorSectionID = rowData.attr('data-floorSectionID');
        if (floorSectionID == null) {
            floorSectionID = $(this).attr('data-floorSectionID');
        }
        $.ajax({
            type: 'post',
            data: 'removesection=true&upc='+upc+'&floorSectionID='+floorSectionID,
            dataType: 'json',
            url: 'EditLocations.php',
            success: function(resp) {
                if (resp.error == 0) {
                    $(this).closest('tr').hide();
                    $('#ajax-success').show();
                    $('#ajax-success').fadeOut(1500);
                    location.reload();
                } else {
                    $('#ajax-danger').show();
                    $('#ajax-danger').fadeOut(1500);
                    console.log(resp.error);
                }
            },
        });
    }
});
var prevFloorSection = null
$('.edit-floorSection').on('focus', function(){
    prevFloorSection = $(this).children('option:selected').val();
});
$('.edit-floorSection').change(function(){
    var rowData = $(this).parent('td').parent('tr').find('.row-data');
    var upc = rowData.attr('data-upc');
    var floorSectionID = rowData.attr('data-floorSectionID');
    var newSection = $(this).children('option:selected').val();
    if (upc == null) {
        upc = $('#upc').val();
    }
    if (floorSectionID == null) {
        floorSectionID = prevFloorSection;
    }
    $.ajax({
        type: 'post',
        data: 'newSection='+newSection+'&upc='+upc+'&floorSectionID='+floorSectionID,
        dataType: 'json',
        url: 'EditLocations.php',
        success: function(resp) {
            if (resp.error == 0) {
                $('#ajax-success').show();
                $('#ajax-success').fadeOut(1500);
                location.reload();
            } else {
                $('#ajax-danger').show();
                $('#ajax-danger').fadeOut(1500);
                console.log(resp.error);
            }
        },
    });
});
var prevSubSection = null
$('.edit-subsection').on('focus', function(){
    prevSubSection = $(this).children('option:selected').val();
});
$('.edit-subsection').change(function(){
    var rowData = $(this).parent('td').parent('tr').find('.row-data');
    var upc = rowData.attr('data-upc');
    var floorSectionID = rowData.attr('data-floorSectionID');
    var subSection = rowData.attr('data-subSection');
    var newSubSection = $(this).children('option:selected').val();
    if (upc == null) {
        upc = $('#upc').val();
    }
    if (floorSectionID == null) {
        floorSectionID = $(this).attr('data-floorSection');
    }
    if (subSection == null) {
        subSection = prevSubSection;
    }
    $.ajax({
        type: 'post',
        data: 'newSubSection='+newSubSection+'&upc='+upc+'&floorSectionID='+floorSectionID+'&subSection='+subSection,
        dataType: 'json',
        url: 'EditLocations.php',
        success: function(resp) {
            if (resp.error == 0) {
                console.log('Saved');
                $('#ajax-success').show();
                $('#ajax-success').fadeOut(1500);
            } else {
                $('#ajax-danger').show();
                $('#ajax-danger').fadeOut(1500);
                console.log(resp.error);
            }
        },
    });
});
var upc = 0;
var stripeFillColor = '#FFFFCC';
var stripeTable = function(){
    $('#mytable tr').each(function(){
        if (!$(this).parent('thead').is('thead')) {
            temp_upc = $(this).find('.row-data').attr('data-upc');
            if (temp_upc != upc) {
                if (stripeFillColor == '#FFFFCC') {
                    stripeFillColor = 'transparent';
                } else {
                    stripeFillColor = '#FFFFCC';
                }
            } else {
            }
            $(this).css('background', stripeFillColor);
            upc = temp_upc;
        }
    });
}
stripeTable();

function fadeAlerts()
{
    $('#ajax-resp').fadeOut(1500);
}
JAVASCRIPT;
    }

    public function css_content()
    {
        return <<<HTML
.dept {
    cursor: pointer;
}
.ajax-resp-alert {
    position: fixed;
    top: 0;
    right: 0;
    display:none; 
}
HTML;
    }

    public function helpContent()
    {
        return 'No help content available.';
    }

}

FannieDispatch::conditionalExec();
