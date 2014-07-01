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

class update_20120713113300 extends UpdateObj {

    protected $timestamp = '20120713113300';

    protected $description = 'Change dtransactions
columns representing currency to a proper type';

    protected $author = 'Andy Theuninck (WFC)';

    protected $queries = array(
        'op' => array(),
        'trans' => array(
        'ALTER TABLE dtransactions CHANGE COLUMN cost cost DECIMAL(10,2)',
        'ALTER TABLE dtransactions CHANGE COLUMN unitPrice unitPrice DECIMAL(10,2)',
        'ALTER TABLE dtransactions CHANGE COLUMN total total DECIMAL(10,2)',
        'ALTER TABLE dtransactions CHANGE COLUMN regPrice regPrice DECIMAL(10,2)',
        'ALTER TABLE dtransactions CHANGE COLUMN discount discount DECIMAL(10,2)',
        'ALTER TABLE dtransactions CHANGE COLUMN memDiscount memDiscount DECIMAL(10,2)',
        'ALTER TABLE dtransactions CHANGE COLUMN VolSpecial VolSpecial DECIMAL(10,2)',
        'ALTER TABLE transarchive CHANGE COLUMN cost cost DECIMAL(10,2)',
        'ALTER TABLE transarchive CHANGE COLUMN unitPrice unitPrice DECIMAL(10,2)',
        'ALTER TABLE transarchive CHANGE COLUMN total total DECIMAL(10,2)',
        'ALTER TABLE transarchive CHANGE COLUMN regPrice regPrice DECIMAL(10,2)',
        'ALTER TABLE transarchive CHANGE COLUMN discount discount DECIMAL(10,2)',
        'ALTER TABLE transarchive CHANGE COLUMN memDiscount memDiscount DECIMAL(10,2)',
        'ALTER TABLE transarchive CHANGE COLUMN VolSpecial VolSpecial DECIMAL(10,2)',
        'ALTER TABLE suspended CHANGE COLUMN cost cost DECIMAL(10,2)',
        'ALTER TABLE suspended CHANGE COLUMN unitPrice unitPrice DECIMAL(10,2)',
        'ALTER TABLE suspended CHANGE COLUMN total total DECIMAL(10,2)',
        'ALTER TABLE suspended CHANGE COLUMN regPrice regPrice DECIMAL(10,2)',
        'ALTER TABLE suspended CHANGE COLUMN discount discount DECIMAL(10,2)',
        'ALTER TABLE suspended CHANGE COLUMN memDiscount memDiscount DECIMAL(10,2)',
        'ALTER TABLE suspended CHANGE COLUMN VolSpecial VolSpecial DECIMAL(10,2)'
        ),
        'archive' => array()
    );
}

?>
