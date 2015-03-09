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

class update_20130118214806 extends UpdateObj {

    protected $timestamp = '20130118214806';

    protected $description = 'Change the data type of upc in tables
<br />products, productBackup and prodExtra
<br />Before you do this back up these tables using update 20130118214805.
<br />If the data type change succeeds but you need to restore the leading zeros on upc, run update 20130118214807.
<br />When all is well run update 20130118214808 to drop these special backups.
<br />These changes were tested under MySQL 5.1';

    protected $author = 'Eric Lee (WEFC_Toronto)';

    protected $queries = array(
        'op' => array(
            'ALTER TABLE productBackup CHANGE COLUMN  upc upc VARCHAR(13)',
            'ALTER TABLE prodExtra CHANGE COLUMN  upc upc VARCHAR(13)',
            'ALTER TABLE products CHANGE COLUMN  upc upc VARCHAR(13)'
        ),
        'trans' => array(),
        'archive' => array()
    );
}

?>
