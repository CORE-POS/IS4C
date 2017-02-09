<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class UploadVendorPriceFile extends FanniePage {
    /* html header, including navbar */
    protected $title = "Fannie - Upload Price File";
    protected $header = "Upload Price File";

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    public $description = '[Vendor Price File] loads or reloads catalog information from a spreadsheet.';

    function body_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $p = $dbc->prepare('SELECT vendorID,vendorName FROM vendors ORDER BY vendorName');
        $r = $dbc->execute($p);
        $ret = '<div class="form-group"><label>Use the Default import tool</label>'
            . '<select id="vendor-id" class="form-control">';;
       
        while ($w = $dbc->fetch_row($r)) {
            $ret .= sprintf('<option value="%d">%s</option>',
                $w['vendorID'],$w['vendorName']);
        }
        $ret .= '</select></div>';
        $ret .= '<button type="button" class="btn btn-default btn-danger"
            onclick="location=\'../../item/vendors/DefaultUploadPage.php?vid=\'+$(\'#vendor-id\').val();
            return false;">Replace Catalog Entire via File</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="button" class="btn btn-default btn-info"
            onclick="location=\'../../item/vendors/UpdateUploadPage.php?vid=\'+$(\'#vendor-id\').val();
            return false;">Update Existing Catalog via File</button>';
        $ret .= '<hr />';
        $ret .= '<b>Use a Custom import tool</b>:<br /><ul>';
        $files = scandir(dirname(__FILE__) . '/load-classes');
        foreach($files as $f){
            if($f[0] == '.') continue;
            if (substr($f,-4) != '.php') continue;
            $ret .= sprintf('<li><a href="load-classes/%s">%s</a></li>',
                    $f,substr($f,0,strlen($f)-4));
        }
        $ret .= '</ul>';
        return $ret;
    }

    public function helpContent()
    {
        return '<p>Import a spreadsheet containing vendor items and costs.
            The default tool works fine in many cases, but custom vendor-specific
            importers can be added to tailor the interface to the spreadsheet
            format or perform additional vendor-specific operations.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }

}

FannieDispatch::conditionalExec();

