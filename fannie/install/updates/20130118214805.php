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

class update_20130118214805 extends UpdateObj {

    protected $timestamp = '20130118214805';

    protected $description = 'Create and populate special backups of tables
<br />products, productBackup and prodExtra
<br />prior to changing the data type of upc in each.
<br />If this succeeds run update 20130118214806 to make the data type changes.
<br />If that succeeds but you need to restore the leading zeros on upc, run update 20130118214807.
<br />When all is well run update 20130118214808 to drop these special backups.
<br />These changes were tested under MySQL 5.1';

    protected $author = 'Eric Lee (WEFC_Toronto)';

    protected $queries = array(
        'op' => array(
            'CREATE TABLE productBackup_upc LIKE productBackup',
            'INSERT INTO productBackup_upc (SELECT * from productBackup)',
            'CREATE TABLE prodExtra_upc LIKE prodExtra',
            'INSERT INTO prodExtra_upc (SELECT * from prodExtra)',
            'CREATE TABLE products_upc LIKE products',
            'INSERT INTO products_upc (SELECT * from products)'
        ),
        'trans' => array(),
        'archive' => array()
    );
}

?>
