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
class NewPageO extends FannieRESTfulPage
{
    protected $header = 'Floor Sub-Section Viewer';
    protected $title = 'Floor Sub-Section Viewer';
    public $themed = true;
    public $description = '[Floor Sub-Sections] Find all sub-sections 
        a department has products located in.';

    public function preprocess()
    {
        $this->addRoute('post<section>');

        return parent::preprocess();
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        // for now, only denfeld is being shown
        //$store = Store::getIdByIp();

        $prep = $dbc->prepare("
            SELECT fs.floorSectionID, fss.upc, fss.subSection, fs.name, p.department, p.brand, p.description, d.dept_name
            FROM FloorSections AS fs
            LEFT JOIN FloorSubSections AS fss ON fs.floorSectionID=fss.floorSectionID
            INNER JOIN products AS p ON fss.upc=p.upc
            LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE fs.storeID = 2 
            GROUP BY fs.floorSectionID, fss.subSection, fss.upc
            ORDER BY fs.name, fss.subSection 
        ");
        $res = $dbc->execute($prep);
        $data = array();
        $subdata = array();
        $subExampleItem = array();
        $cols = array('floorSectionID', 'subSection', 'name', 'department', 'brand', 'description', 'dept_name');
        while ($row = $dbc->fetchRow($res)) {
            $dept = $row['department'] . '-' . $row['dept_name'];
            $fsName = $row['name'];
            if (!isset($subdata[$fsName]) && !isset($subdata[$fsName][$row['subSection']]) ) {
                $subdata[$fsName][$row['subSection']][$dept] = 1;
            } else {
                $subdata[$fsName][$row['subSection']][$dept] += 1;
                $subExampleItem[$fsName][$row['subSection']][$dept] = $row['description'];
            }
        }
        $td = '';
        $map = "";
        $order = array(
            'Cool 1' => 1,
             'Wellness 1' => 2, 
             '5' => 0, 
             'Wellness 2' => 2, 
             'Wellness 3' => 2,
             '4' => 0,
             'Grocery 7' => 1,
             'Grocery 6' => 1,
             '3' => 0,
             'Grocery 5' => 1,
             'Grocery 4' => 1,
             '2' => 0,
             'Grocery 3' => 1,
             'Grocery 2' => 1,
             '1' => 0,
             'Grocery 1' => 1,
             'Bev 1' => 1,
            );
        $heightClasses = array(1 => 'heightOne', 2 => 'heightTwo');
        $depts = array();
        foreach ($order as $fsName => $height) {
            $map .= "<div class=\"Aisle\">";
            $map .= "<h4 class=\"section-name\">$fsName</h4>";
            foreach ($subdata[$fsName] as $letter => $subRow) {
                $map .= "<div class=\"section {$heightClasses[$height]}\">";
                $letter = strtoupper($letter);
                $map .= "<div class=\"sub-section-header\">$letter</div>";
                unset($depts);
                foreach ($subRow as $deptName => $items) {
                    $depts[$deptName] = $items;
                }
                asort($depts);
                $depts = array_reverse($depts);
                foreach ($depts as $dept_name => $count) {
                    if ($count > 4) {
                        $map .= "<div class=\"dept\">$dept_name($count)</div>";
                    }
                }
                $map .= "</div>";
            }
            $map .= "</div>";
        }

        return <<<HTML
<div class="modal fade" tabindex="-1" role="dialog" id="mymodal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Product List</h4>
                <p id="modal-title-body"></p>
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

<div id="mypage" style="padding: 25px; cursor: grab;">

    <div id="scroll-search" style="width: 200px; position: fixed; top: 5px; left: 60px; display: none;"><input "type="text" name="searchin" id="searchin-2" class="form-control"></div>
    <div id="mymap">$map</div>
</div>
HTML;
    }

    public function post_section_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $section = FormLib::get('section');
        $subsection = FormLib::get('subsection');
        $dept = FormLib::get('dept');

        $args = array($section, $subsection);
        $prep = $dbc->prepare("
            SELECT fss.upc, fss.subSection, fs.name, p.department, p.brand, p.description, d.dept_name
            FROM FloorSections AS fs
            LEFT JOIN FloorSubSections AS fss ON fs.floorSectionID=fss.floorSectionID
            INNER JOIN products AS p ON fss.upc=p.upc
            LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE fs.storeID = 2 
                AND fs.name = ?
                AND fss.subSection = ?
            GROUP BY p.upc
            ORDER BY p.brand
        ");
        $res = $dbc->execute($prep, $args);
        $ret = "";
        $json = array();
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>",
                $row['upc'],
                $row['brand'],
                $row['description']
            );
        }
        echo $ret;

        return false;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$('#fannie-main-content').css('border', '0px solid transparent');
$('.dept').on('click', function(){
    var sub = $(this).parent().find('.sub-section').text();
    var section = $(this).parent().find('.section').text();
    var dept = $(this).text();
    dept = dept.substring(0,3);
    console.log(section+', '+sub+', '+dept);
    $.ajax({
        type: 'post',
        data: 'section='+section+'&subsection='+sub+'&dept='+dept,
        success:function(resp)
        {
            console.log('success');
            console.log(resp);
            $('#modal-body').html('<table class="table table-bordered table-striped table-condensed"><thead></thead><tbody>'+resp+'</tbody></table>');
            $('#modal-title-body').html("<h4>Section: <strong>"+section+"</strong> | Sub-Section: <strong>"+sub+"</strong></h4>");
        }
    }).done(function(resp) {
        console.log('done'); 
    });
    $('#mymodal').modal('toggle');
});
$("#searchin").keyup(function(e){
    var text = $(this).val().toUpperCase();
    $('td').each(function(){
        if ($(this).closest('table').attr('id') == "mytable") {
            var tdText = $(this).text().toUpperCase();
            if (!tdText.includes(text)) {
                $(this).css('background', 'lightgrey');
            } else {
                $(this).css('background', '#FFFFCC');
            }
        }
    });
    $('#searchin-2').val(text);
});
$("#searchin-2").keyup(function(e){
    var text = $(this).val().toUpperCase();
    $('#searchin').val(text).trigger('keyup');
});
$(window).scroll(function () {
    var scrollTop = $(this).scrollTop();
    if (scrollTop > 300) {
        $('#scroll-search').fadeIn('slow');
    } else {
        $('#scroll-search').fadeOut('slow');
    }
});

JAVASCRIPT;
    }

    public function css_content()
    {
        return <<<HTML
#mymap {
    width: 500vw;
}
.section-name {
    font-weight: bold;
}
.dept {
    cursor: pointer;
    //border: 1px solid green;
}
.Aisle {
    //border: 1px solid orange;
    width: 200px;
    //height: 250px;
    float: left;
    display: inline-block
}
.section {
    //float: left;
    //display: inline-block;
    border: 1px solid teal;
    height: 100px;
    width: 200px;
    font-size: 12px;
    position: relative;
}
.heightOne {
    height: 100px;
}
.heightTwo {
    height: 200px;
}
.sub-section-header {
    position: absolute; 
    top: 0px;
    right: 0px;
    font-weight: bold;
}
HTML;
    }

    public function helpContent()
    {
        return '<p>Enter a department number or name to 
            highlight all isle sub-sections that department\'s items
            can be found in.</p>
        <p>The number in parentheses after each department name is the number
            of items found in that department in the sub-section.</p>
        <p>Click on a department to view a list of items in that department, 
            in the overlying sub-section.</p>';
    }

}

FannieDispatch::conditionalExec();
