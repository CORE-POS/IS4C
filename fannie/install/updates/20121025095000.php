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

class update_20121025095000 extends UpdateObj {

    protected $timestamp = '20121025095000';

    protected $description = 'Simplify A/R Balance calculations';


    protected $author = 'Andy Theuninck (WFC)';

    protected $queries = array(
        'op' => array(),
        'trans' => array(
        'DROP VIEW ar_history_sum',
        'DROP TABLE ar_sum_cache',
        'CREATE TABLE ar_history_sum (
            card_no INT,
            charges DECIMAL(10,2),
            payments DECIMAL(10,2),
            balance DECIMAL(10,2),
            PRIMARY KEY (card_no)   
            )'
        ),
        'archive' => array()
    );
}

?>
