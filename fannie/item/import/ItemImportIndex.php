<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
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

class ItemImportIndex extends FanniePage {
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Product Information";

    public $description = '[Item Import Menu] lists options for importing item related data.';

    function body_content(){
        ob_start();
        ?>
        <ul>
        <li><a href="DepartmentImportPage.php">Departments</a></li>
        <li><a href="SubdeptImportPage.php">Subdepartments</a></li>
        <li><a href="ProductImportPage.php">Products</a></li>
        <li><a href="UploadAnyFile.php">Upload a file</a></li>
        </ul>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
