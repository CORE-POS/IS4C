<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
  @class SaMenuPage
*/
class SaMenuPage extends FannieRESTfulPage {

    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Menu] lists handheld-friendly pages for navigation.';
    public $themed = true;
    protected $title = 'ShelfAudit Menu';
    protected $header = '';

    function css_content(){
        ob_start();
        ?>
/*
input[type="submit"] {
    width:85%;
    font-size: 2em;
}
*/
a[type="submit"] {
    width: 65vw;
    font-size: 1em;
}
        <?php
        return ob_get_clean();
    }

    function get_view(){
        ob_start();
        ?>
<!doctype html>
<html>
<head>
    <title>Handheld Menu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<p>
<a class="btn btn-default btn-lg" type="submit"
    href="SaHandheldPage.php" />Inventory</a>
<hr />
<!--
<input type="submit" value="Price Check"
    onclick="location='SaPriceChangePage.php';return false;" />
    -->
<a class="btn btn-default btn-lg" type="submit"
    href="SaItemList.php" />Quick List</a>
<hr />
<a class="btn btn-default btn-lg" type="submit"
    href="../../../item/handheld/ItemStatusPage.php" />Price Check</a>
<hr />
<a class="btn btn-default btn-lg" type="submit"
    href="../../../purchasing/EditOnePurchaseOrder.php" />Create Order</a>
<hr />
<a class="btn btn-default btn-lg" type="submit"
    href="http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php" />Batch Check</a>
<hr />
<a class="btn btn-default btn-lg" type="submit"
    href="http://192.168.1.2/scancoord/item/AuditScanner.php" />Audie</a>
<hr />
</p>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

