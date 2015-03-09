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

class update_20130118214808 extends UpdateObj {

    protected $timestamp = '20130118214808';

    protected $description = 'Drop the special backups of tables
<BR />products, productBackup and prodExtra created by update 20130118214805
<BR />to prepare for changing the data type of upc in each.
<BR />These changes were tested under MySQL 5.1';

    protected $author = 'Eric Lee (WEFC_Toronto)';

    protected $queries = array(
        'op' => array(
            'DROP TABLE IF EXISTS productBackup_upc',
            'DROP TABLE IF EXISTS prodExtra_upc',
            'DROP TABLE IF EXISTS products_upc'
        ),
        'trans' => array(),
        'archive' => array()
    );
}

?>
