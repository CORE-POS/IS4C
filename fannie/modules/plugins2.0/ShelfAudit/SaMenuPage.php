<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

/**
  @class SaMenuPage
*/
class SaMenuPage extends FannieRESTfulPage {
    protected $window_dressing = False;

    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Menu] lists handheld-friendly pages for navigation.';

    function css_content(){
        ob_start();
        ?>
input[type="submit"] {
    width:85%;
    font-size: 2em;
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
<input type="submit" value="Inventory"
    onclick="location='SaHandheldPage.php';return false;" />
<hr />
<input type="submit" value="Price Check"
    onclick="location='SaPriceChangePage.php';return false;" />
<hr />
<input type="submit" value="Ordering Info"
    onclick="location='SaOrderingPage.php';return false;" />
<hr />
<input type="submit" value="Shelf Location"
    onclick="location='../../../item/mapping/index.php';return false;" />
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
