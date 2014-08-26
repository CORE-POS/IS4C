<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ShelfTagIndex extends FanniePage {

    protected $title = 'Fannie - Shelf Tags';
    protected $header = 'Shelf Tags';
    protected $must_authenticate = True;
    protected $auth_classes = array('barcodes');
    public $description = '[Shelf Tag Menu] lists shelf tag related pages.';

    private $layouts = array();

    function preprocess(){
        if (!function_exists('scan_layouts')) {
            require('scan_layouts.php');
        }
        $this->layouts = scan_layouts();
        return True;
    }

    function javascript_content(){
        ob_start();
        ?>
function goToPage(the_id){
    var offset = document.getElementById('offset').value;
    var str = "0";
    if (!isNaN(parseInt(offset)))
        str = parseInt(offset);

    var url = 'genLabels.php?id='+the_id;
    url += '&offset='+offset;

    var sel = document.getElementById('layoutselector');
    var pdf = sel.options[sel.selectedIndex].text;
    url += '&layout='+pdf;

    /*window.top.location = url;*/
    /* 5May13 Eric Lee As popup instead of replacing the select window. */
    tagwindow=window.open (url, "Shelftags", "location=0,status=1,scrollbars=1,width=800,height=1100");
    tagwindow.moveTo(750,10);
}
        <?php
        return ob_get_clean();
    }

    function body_content(){
        global $FANNIE_URL, $FANNIE_OP_DB, $FANNIE_DEFAULT_PDF;
        ob_start();
        ?>
        <div style="float:right;">
        <a href="CreateTagsByDept.php">Create Tags By Department</a>
        <br />
        <a href="CreateTagsByManu.php">Create Tags By Brand</a>
        </div>
        <div>
        Regular shelf tags
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="BatchShelfTags.php">Batch shelf tags</a>
        <p />
        <table cellspacing=0 cellpadding=4 border=1>
        <tr><td>
        Offset: <input type=text size=2 id=offset value=0 />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <select id=layoutselector>
        <?php
        foreach($this->layouts as $l){
            if ($l == $FANNIE_DEFAULT_PDF)
                echo "<option selected>".$l."</option>";
            else
                echo "<option>".$l."</option>";
        }
        ?>
        </select>
        </td></tr>
        </table>
        <p />
        <table cellspacing=0 cellpadding=4 border=1>
        <?php
        $ret = ob_get_clean();

        $dbc = FannieDB::get($FANNIE_OP_DB);
        /* Was:
        $query = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
            GROUP BY superID,super_name
            ORDER BY superID");
        */
        // 5May13 Change SELECT so #-of-labels can be displayed. */
        $query = $dbc->prepare_statement("SELECT superID,super_name, count(distinct t.upc) ct
            FROM MasterSuperDepts AS s
            LEFT JOIN shelftags AS t ON s.superID = t.id
            GROUP BY superID,super_name
            ORDER BY superID");
        $result = $dbc->exec_statement($query);
        $rows = array();
        while($row = $dbc->fetch_row($result))
            $rows[] = $row;
        if (count($rows)==0){
            $rows[] = array(0,'All Tags');
        }
        foreach($rows as $row){
            $ret .= sprintf("<tr>
            <td>%s barcodes/shelftags</td>
            <td style='text-align:right;'>%d</td>
            <td><a href=\"\" onclick=\"goToPage('%d');return false;\">Print</a></td>
            <td><a href=\"DeleteShelfTags.php?id=%d\">Clear</a></td>
            <td><a href=\"EditShelfTags.php?id=%d\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\"
                alt=\"Edit\" border=0 /></td>
            </tr>",
            $row[1],$row[2],$row[0],$row[0],$row[0]);
        }
        $ret .= "</table>";
        $ret .= '</div>
        <div style="clear:right;"></div>';
        
        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
