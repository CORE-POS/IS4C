<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    * 10Oct12 Eric Lee New.
*/

include('../../config.php');

$page_title = "Fannie : WEFC Toronto utilities";
$header = "WEFC Toronto utilities";
include($FANNIE_ROOT.'src/header.html');

echo "<ul>";
?>
      <li><a href="<?php echo $path; ?>item/itemMaint_WEFC_Toronto.php">WEFC Item Editor</a></li>
      <li><a href="<?php echo $path; ?>item/import/uploadAnyFile.php">Upload Any File</a></li>
            <li><a href="<?php echo $path; ?>item/departments/loadWEFCTorontoDepts.php">Load Departments</a></li>
            <li><a href="<?php echo $path; ?>item/import/loadWEFCTorontoProducts.php">Load Products</a></li>
            <li><a href="<?php echo $path; ?>reports/Store-Specific/WEFC_Toronto/ProductsExport/">Product Export</a></li>

<?php
echo "</ul>";
                                     
include($FANNIE_ROOT.'src/footer.html');

