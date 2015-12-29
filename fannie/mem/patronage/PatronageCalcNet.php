<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PatronageCalcNet extends FannieRESTfulPage
{
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Update Net Purchases";
    public $description = '[Patronage Netted] calculates net patronage for work-in-progress annual patronage data.';
    public $themed = true;

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        ob_start();
        $q = $dbc->prepare("UPDATE patronage_workingcopy SET
            net_purch = purchase + discounts + rewards");
        $r = $dbc->execute($q);
        if ($r) {
            echo '<div class="alert alert-success">';
            echo 'Net purchases updated';
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger">';
            echo 'An error occurred!';
            echo '</div>';
        }

        echo '<br /><br />';
        echo '<a href="index.php">Patronage Menu</a>';

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Net purchases is gross purchases less discounts and
            any other rewards. This page recalculates net purchases and should
            be run any time that gross purchases, discounts, or rewards are
            updated.</p>';
    }
}

FannieDispatch::conditionalExec();

