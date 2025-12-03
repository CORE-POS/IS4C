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
        $this->addRoute('post<quickset>');
        $this->addRoute('post<addsession>');
        $this->addRoute('post<newSubSection>');
        $this->addRoute('post<section>');
        $this->addRoute('post<removesection>');
        $this->addRoute('post<newSection>');
        $this->addRoute('get<upc>');

        return parent::preprocess();
    }

    public function post_quickset_handler()
    {
        $section = FormLib::get("sect", false);
        $subsection = FormLib::get("subs", false);
        $upc = FormLib::get("upc", false);
        $storeID = FormLib::get("storeID", false);
        $mode = FormLib::get("mode", false);

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        if ($mode == 'reset') {
            // 1. delete all FSPM rows for item (for storeID)
            $ids = array();
            $getA = array($upc, $storeID);
            $getP = $dbc->prepare("SELECT
                floorSectionProductMapID
                FROM FloorSectionProductMap m
                    INNER JOIN FloorSections s ON s.floorSectionID=m.floorSectionID
                WHERE upc = ? 
                    AND s.storeID = ?");
            $getR = $dbc->execute($getP, $getA);
            while ($row = $dbc->fetchRow($getR)) {
                $ids[] = $row['floorSectionProductMapID'];
            }

            $delP = $dbc->prepare("DELETE from FloorSectionProductMap
                WHERE floorSectionProductMapID = ?");
            foreach ($ids as $id) {
                $delA = array($id);
                $delR = $dbc->execute($delP, $delA);
            }
        }

        // 2. insert into FSPM
        $insertA = array($upc, $section);
        $insertP = $dbc->prepare("INSERT INTO
            FloorSectionProductMap (upc, floorSectionID)
                VALUES (?, ?)");
        $insertR = $dbc->execute($insertP, $insertA);
        // 3. insert into FSS
        $fssA = array($upc, $section, $subsection);
        $fssP = $dbc->prepare("INSERT INTO FloorSubSections (upc, floorSectionID, subSection) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE subSection=VALUES(subSection)");
        $fssR = $dbc->execute($fssP, $fssA);

        return false;
    }

    public function post_addsession_handler()
    {
        $section = FormLib::get("sect", false);
        $subsection = FormLib::get("subs", false);
        $unsub = FormLib::get("unsub", false);
        if ($unsub === 'true') {
            unset($_SESSION['EditLocationDefault']);
        } else { 
            $_SESSION['EditLocationDefault'] = "$section,$subsection";
        }

        return false;
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
        $storeID = FormLib::get('storeID');
        $floorSectionID = FormLib::get('floorSectionID');
        $newSection = FormLib::get('newSection');
        $isnew = FormLib::get('isnew');
        $json = array();
        $json['error'] = 0;

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $args = array($upc, $storeID);
        $prep = $dbc->prepare("SELECT * FROM FloorSectionProductMap AS m
            LEFT JOIN FloorSections AS f ON m.floorSectionID=f.floorSectionID WHERE upc = ? AND storeID = ?");
        $res = $dbc->execute($prep, $args);
        $row = $dbc->fetchRow($res);
        $exists = ($row['upc'] > 0) ? true : false;

        if ($isnew == 'true') {
            $prep = $dbc->prepare("INSERT INTO FloorSectionProductMap (floorSectionID, upc) 
                VALUES (?, ?) ");
            $res = $dbc->execute($prep, array($newSection, $upc));
        }
        if ($isnew == 'false') {
            $prep = $dbc->prepare("UPDATE FloorSectionProductMap SET floorSectionID = ? 
                WHERE upc = ? AND floorSectionID = ?;");
            $res = $dbc->execute($prep, array($newSection, $upc, $floorSectionID));
        }

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

    private function floorSectionSelect($options, $floorSectionID, $new='false')
    {
        $select = "<select class=\"form-control edit-floorSection\" style=\"width: 175px; display: inline-block;\" data-isnew=\"$new\">
            <option value=\"\">NEW FLOOR SECTION</option>";
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
        $curSub = (isset($row['subSection'])) ? $curSub = $row['subSection'] : '';

        $options = '<option value=\"0\">&nbsp;</option>';
        $letters = range('a', 'l');
        foreach ($letters as $letter) {
            $sel = ($curSub == $letter) ? ' selected' : '';
            $options .= "<option value=\"$letter\" $sel>$letter</option>";
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

        if (isset($_SESSION['EditLocationDefault'])) {
            echo "<input type=\"hidden\" name=\"EditLocationDefault\" value=\"{$_SESSION['EditLocationDefault']}\" />";
        }

        $sessionDefault = FormLib::get('EditLocationDefault', false);

        $storeID = FormLib::get('storeID');
        $storePicker = FormLib::storePicker('storeID');

        $upc = FormLib::get('upc', false);
        $upc = BarcodeLib::padUpc($upc);

        $sections = array();
        $prep = $dbc->prepare("SELECT * FROM FloorSections WHERE storeID = ? ORDER BY name");
        $res = $dbc->execute($prep, array($storeID));
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

        $args = array($upc, $storeID);
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
        $rProdInfo = $dbc->execute($pProdInfo, array($upc));
        $wProdInfo = $dbc->fetchRow($rProdInfo);
        $brand = is_array($wProdInfo) ? $wProdInfo['brand'] : '';
        $description = is_array($wProdInfo) ? $wProdInfo['description'] : '';
        $department = is_array($wProdInfo) ? $wProdInfo['dept_name'] : '';
        $superName = is_array($wProdInfo) ? $wProdInfo['super_name'] : '';
        $td .= sprintf("
                <tr><td>%s</td></tr>
                <tr><td>%s</td></tr>
                <tr><td>%s</td></tr>",
            $upc,
            $brand, 
            $description
        );
        while ($row = $dbc->fetchRow($res)) {
            //$upc = $row['upc'];
            $floorSectionID = $row['floorSectionID'];
            list($subSection, $subSectionOpts) = $this->subSectionSelect($upc, $subSections[$floorSectionID], $floorSectionID, $dbc);
            $i++;
            $stripe = ($i % 2 == 0) ? "#FFFFCC" : "transparent";
            $alphabet = range('A', 'Z');
            $td .= sprintf("
                    <tr style=\"background-color: %s;\"><td><div>Floor Section<strong> %s</strong></div>%s</td></tr>
                    <tr style=\"background-color: %s;\"><td><div>Sub-Section<strong> %s</strong></div>%s</td></tr>
                    <td class=\"row-data\" id=\"row-data\" style=\"display: none\" 
                    data-upc=\"%s\" data-floorSectionID=\"%s\" data-subSection=\"%s\" data-storeID=\"%s\">
                    </td></tr>", 
                $stripe,
                $alphabet[$i-1],
                $this->floorSectionSelect($sections, $floorSectionID)
                    ." <span class=\"btn btn-default fas fa-trash btn-remove-section\" 
                        style=\"float:right; margin: 5px;\" data-floorSectionID=\"$floorSectionID\"></span>",
                $stripe,
                $alphabet[$i-1],
                "<select class=\"form-control edit-subsection\" data-floorSection=\"$floorSectionID\" style=\"width: 75px;\">$subSectionOpts</select>",
                $upc, $floorSectionID, $subSection, $storeID
            );
        }
        $td .= sprintf("
                <tr style=\"background-color: %s;\"><td><div>
                    <b>+ ADD NEW +</b> Floor Section</div>%s</td></tr>
                <tr style=\"background-color: %s;\"></tr>
                <td class=\"row-data\" id=\"row-data\" style=\"display: none\" 
                data-upc=\"%s\" data-floorSectionID=\"%s\" data-subSection=\"%s\" data-storeID=\"%s\">
                </td></tr>", 
            'orange',
            $this->floorSectionSelect($sections, 0, 'true'),
            'orange',
            $upc, 0, 0, $storeID
        );
        if (isset($_SESSION['EditLocationDefault'])) {
            $info = explode(",", $_SESSION['EditLocationDefault']);
            $dsection = $sections[$info[0]];

            $td .= "<tr style=\"background-color: plum; height: 30px;\"><td>
                <div style=\"height: 24px;\"></div>
                <div><span class='btn btn-default btn-sm' onclick='setQuickLocation(\"{$info[0]}\", \"{$info[1]}\", \"reset\"); return false;'>Set to <b> $dsection - {$info[1]} </b></span></div>
                <div style=\"height: 24px;\"></div>
                <div><span class='btn btn-default btn-sm' onclick='setQuickLocation(\"{$info[0]}\", \"{$info[1]}\", \"add\"); return false;'>Add <b> $dsection - {$info[1]}</b></span></div>
                <div style=\"height: 24px;\"></div>
                <div><span class='btn btn-default btn-sm' style='background: tomato; color: white; text-shadow: 1px 1px crimson;' onclick='setDefaultLocation(1, 1, \"true\")'>Unset Remember Location</span></div>
                </td></tr></td></tr>";
            $td .= "<tr style=\"background-color: plum; height: 30px;\" id=\"set-default-location-session\"><td><div>
                        <b>Press</b> to remember current location</div> </td></tr></td></tr>";

        } else {
            $td .= "<tr style=\"background-color: plum; height: 30px;\" id=\"set-default-location-session\"><td><div>
                        <b>Press</b> to remember current location</div> </td></tr></td></tr>";
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
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <div class="form-group">
            <form name="upc-form" method="get">
                <div class="row">
                    <div class="col-lg-1"></div>
                    <div class="col-xs-6">
                        <label>Edit a single item</label>
                        <div class="form-group">
                            <input name="upc" id="upc" value="$upc" class="input-small small form-control" autofocus pattern="\d*">
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="form-group">
                            {$storePicker['html']}
                        </div>
                        <div class="form-group">
                            <button class="btn btn-default btn-xs xs form-control" id="thesubmitbtn">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4"></div>
</div>
<div class="container">
    <div class="row">
        <div class="col-lg-4"></div>
        <div class="col-lg-4">
            <form class="form-inline" id="table-update">
            <div class="table-responsive"><table class="table table-bordered table-sm small" id="handheldtable"><thead>$th</thead><tbody>$td</tbody></table></div>
            </form>
            <div align="center" style="margin-bottom: 10px;"><i>Related Pages</i></div>
            <div style="padding: 26px; border: 1px solid lightgrey; ">
                <div class="form-group" align="center">
                    <a class="btn btn-default menu-btn" href="../../item/ProdLocationEditor.php?list=">Edit List</a>
                </div>
                <div class="form-group" align="center">
                    <a class="btn btn-default menu-btn" href="../../item/ProdLocationEditor.php?remove=">Remove List</a>
                </div>
                <div class="form-group" align="center">
                    <a class="btn btn-default menu-btn" href="../../item/FloorSections/EditLocations.php">Edit Sub-Locations</a>
                </div>
                <div style="border-bottom: 1px solid lightgrey; margin: 15px;"></div>
                <div class="form-group" align="center">
                    <a class="btn btn-default menu-btn" href="../../modules/plugins2.0/ShelfAudit/SaMenuPage.php">Mobile Menu</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4"></div>
    </div>
</div>
HTML;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $onChange = "document.forms['upcs'].submit();";
        $storePicker = FormLib::storePicker('storeID', true, $onChange);
        $storeID = FormLib::get('storeID');
        $floorSectionID = FormLib::get('floorSectionID');

        $upcsStr = FormLib::get('upcs', false);
        $upcs = explode("\r\n",$upcsStr);
        $tmp = array();
        foreach ($upcs as $upc) {
            $upc = BarcodeLib::padUPC($upc);
            $tmp[] = $upc;
        }
        $upcs = $tmp;

        $sections = array();
        $prep = $dbc->prepare("SELECT * FROM FloorSections WHERE storeID = ? ORDER BY name");
        $res = $dbc->execute($prep, array($storeID));
        $fsOptions = "<option value=\"null\" >Select an Aisle</option>";
        $sections = array();
        while ($row = $dbc->fetchRow($res)) {
            $sections[$row['floorSectionID']] = $row['name'];
            $sel = ($row['floorSectionID'] == $floorSectionID) ? ' selected ' : '';
            //echo $row['floorSectionID'] . " = " . $floorSectionID .  "<br/>";
            $fsOptions .= "<option value=\"{$row['floorSectionID']}\" $sel>{$row['name']}</option>";
        }

        $prep = $dbc->prepare("select floorSectionID, subSection from FloorSubSections group by floorSectionID, subSection;");
        $res = $dbc->execute($prep);
        $subSections = array();
        while ($row = $dbc->fetchRow($res)) {
            $subSections[$row['floorSectionID']][] = $row['subSection'];
        }

        list($inStr, $args) = $dbc->safeInClause($upcs);
        $args[] = $storeID;
        $args[] = $floorSectionID;

        $prep = $dbc->prepare("
            SELECT * 
            FROM FloorSectionProductMap AS map 
                RIGHT JOIN FloorSections as fs ON map.floorSectionID=fs.floorSectionID
            WHERE upc IN ($inStr)
                AND fs.storeID = ?
                AND map.floorSectionID = ?
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
        $floorSectionsForm = '';
        while ($row = $dbc->fetchRow($res)) {
            $brand = '';
            $description = '';
            $department = '';
            $superName = '';
            $upc = $row['upc'];
            if ($upc != 0) {
                $rProdInfo = $dbc->execute($pProdInfo, array($upc));
                $wProdInfo = $dbc->fetchRow($rProdInfo);
                if (isset($wProdInfo['brand']))
                    $brand = $wProdInfo['brand'];
                if (isset($wProdInfo['description']))
                    $description = $wProdInfo['description'];
                if (isset($wProdInfo['dept_name']))
                    $department = $wProdInfo['dept_name'];
                if (isset($wProdInfo['super_name']))
                    $superName = $wProdInfo['super_name'];

                $floorSectionID = (isset($row['floorSectionID'])) ? $row['floorSectionID'] : 0;
                if (!isset($subSections[$floorSectionID]))
                    $subSections[$floorSectionID] = 'unknown';

                list($subSection, $subSectionOpts) = $this->subSectionSelect($upc, $subSections[$floorSectionID], $floorSectionID, $dbc);
                $floorSectionsForm .= "\n".$floorSectionID;

                $td .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>
                        <td class=\"row-data\" style=\"display: none\" 
                        data-upc=\"%s\" data-floorSectionID=\"%s\" data-subSection=\"%s\" data-storeID=\"%s\">
                        </td></tr>", 
                    $upc,
                    $brand, 
                    $description,
                    $this->floorSectionSelect($sections, $floorSectionID)
                        ." <span class=\"form-control btn btn-default fas fa-trash btn-remove-section\"></span>",
                    "<select class=\"form-control edit-subsection\" style=\"width: 75px;\">$subSectionOpts</select>"
                        ." <span class=\"form-control btn btn-default btn-add-subsection\">+</span>",
                    $department,
                    $superName,
                    $upc, $floorSectionID, $subSection, $storeID
                );
            }
        }
        echo $dbc->error();

        $options = '<option value=\"0\">Select a Sub-Section</option>';
        $letters = range('a', 'l');
        foreach ($letters as $letter) {
            $options .= "<option value=\"$letter\" >$letter</option>";
        }

        $fsNav = file_get_contents('FloorSectionsNav.html', 'r');

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
                <textarea name="upcs" id="upcs" rows=5 class="form-control">$upcsStr</textarea>
            </div>
            <div class="form-group">
                {$storePicker['html']}
            </div>
            <div class="form-group">
                <select name="floorSectionID" class="form-control alert-warning" onchange="$onChange">
                    $fsOptions
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="form-control btn btn-default">
            </div>
        </form>
        <input type="hidden" id="storeID" value="$storeID" />
    </div>
    <div class="col-lg-4">
        <form name="editAllSubs" action="EditLocations.php" method="post">
            <label>Update All Sub Sections</label>
            <div class="form-group">
                <select name="editAllSubs" id="editAllSubs" class="form-control">$options</select>
                <textarea class="hidden" name="upcs">$upcsStr</textarea>
                <textarea class="hidden" name="floorSections">$floorSectionsForm</textarea>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        $fsNav
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
let userAgent = navigator.userAgent;
if (!userAgent.includes("TC52X")) {
    $('head').append('<meta name = "viewport" content = "width=device-width, minimum-scale=1.0, maximum-scale = 1.0, user-scalable = no">');
}

$('#upc').focus(function(){
    $(this).select();
});

$('#fannie-outer-margin').css('margin-left', '0')
    .css('margin-right', '0');
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
    var storeID = $('#storeID').val();
    var isnew = $(this).attr('data-isnew');
    if (upc == null) {
        upc = $('#upc').val();
    }
    if (floorSectionID == null) {
        floorSectionID = prevFloorSection;
    }
    $.ajax({
        type: 'post',
        data: 'newSection='+newSection+'&upc='+upc+'&floorSectionID='+floorSectionID+'&storeID='+storeID+'&isnew='+isnew,
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
    var storeID = $('#storeID').val();
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
        data: 'newSubSection='+newSubSection+'&upc='+upc+'&floorSectionID='+floorSectionID+'&subSection='+subSection+'&storeID='+storeID,
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

$('#upc').focusout(function(){
    let newval = $(this).val().replace(/\s/g, "");
    $(this).val(newval);
});

$('#editAllSubs').change(function(){
    var value = $(this).find(':selected').text();
    var c = confirm('Set All Sub-Sections to "'+value+'"?');
    if (c == true) {
        $('.edit-subsection').each(function(){
            if ($(this).is(":visible")) {
                $(this).val(value).trigger('change');
            }
        });
    }
});

const setDefaultLocation = function(section, subsection, unsub=false) {
    console.log('yesss, honey');
    $.ajax({
        type: 'post',
        data: 'addsession=true&sect='+section+'&subs='+subsection+'&unsub='+unsub,
        url: 'EditLocations.php',
        success: function(response) {
            console.log('success, apparently');
            if (unsub='true')
                window.location.reload();
        },
        error: function(response) {
            console.log('Something went wrong: ' + response);
        },
    });
}

const setQuickLocation = function(sect, subs, mode) {
    let storeID = $('select[name="storeID"] option:selected').val();
    let upc = $('#upc').val();
    $.ajax({
        type: 'post',
        data: 'quickset=true&upc='+upc+'&storeID='+storeID+'&sect='+sect+'&subs='+subs+'&mode='+mode,
        url: 'EditLocations.php',
        success: function(response) {
            window.location.reload();
        },
        error: function(response) {
            console.log('Something went wrong: ' + response);
        },
    });
}

$('#set-default-location-session').on('click', function() {
    let storeID = $('select[name="storeID"] option:selected').val();
    let upc = $('#upc').val();
    let count = $('.edit-floorSection').length;
    if (count > 2) {
        alert('In order to set default location, item must have only one location set'); 

        return false;
    }
    let sect = $('.edit-floorSection:first option:selected'); 
    let sectno = sect.val();
    let subs = $('.edit-subsection:first option:selected'); 
    let subsno = subs.val();
    let info = sectno + ',' + subsno;
    let text = sect.text() + " - " + subs.text();
    setDefaultLocation(sectno, subsno);
});

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
.menu-btn {
    width: 200px;
}
HTML;
    }

    public function helpContent()
    {
        return 'No help content available.';
    }

}

FannieDispatch::conditionalExec();
