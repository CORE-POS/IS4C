<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    * 28Feb13 Andy Theuninck wrapped as class
    * 19Jan13 Eric Lee Add productUser to table list
    * 10Oct12 Eric Lee Add memberCards to table list
*/

include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class SyncIndexPage extends FanniePage {

    protected $title = "Fannie : Sync Lane";
    protected $header = "Sync Lane Operational Tables";

    function body_content(){
        ob_start();
        ?>
        <form action="TableSyncPage.php" method="get">

        <b>Table</b>: <select name="tablename">
        <option value="">Select a table</option>
        <option value="products">Products</option>
        <option value="productUser">Extra Product Info</option>
        <option value="custdata">Members</option>
        <option value="memberCards">Membership Cards</option>
        <option value="employees"><?php echo _('Cashiers'); ?></option>
        <option value="departments">Departments</option>
        <option value="tenders">Tenders</option>
        </select><br /><br />

        <b>Other table</b>: <input type="text" name="othertable" /><br /><br />

        <input type="submit" value="Send Data" />
        </form>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
