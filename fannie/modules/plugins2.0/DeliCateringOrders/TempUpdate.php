<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class TempUpdate {} // compat

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {

    include(dirname(__FILE__).'/../../../config.php');
    if (!class_exists('FannieAPI')) {
        include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    }
    if (!class_exists('DeliCateringPage.php')) {
        include_once($FANNIE_ROOT.'item/DeliCateringOrdersPage.php');
    }
    
    $ret = '';
    $ret .= '<div class="alert alert-danger" align="center">AJAX request complete</div>';
    
    $ret .= '
        <div class="alert alert-info">
            <form method="get">
				<input type="text" name="test' . $i . '">
				<input type="submit">
			</form>
            This form was added to the page through ajax. 
        </div>
    ';
    
    $ret .= '
        <div class="alert alert-warning" align="right">
            <br> $i = ' . $_POST['i'] . '<br>
            <br> $plu = ' . $_POST['plu'] . '<br>
        </div>
    ';

    echo json_encode($ret);

}