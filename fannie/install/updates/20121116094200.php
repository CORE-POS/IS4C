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

class update_20121116094200 extends UpdateObj {

    protected $timestamp = '20121116094200';

    protected $description = 'Replace AR_EOM_Summary with a table rather
        than caching the view all the time';

    protected $author = 'Andy Theuninck (WFC)';

    protected $queries = array(
        'op' => array(),
        'trans' => array(
        'DROP VIEW AR_EOM_Summary',
        'CREATE TABLE AR_EOM_Summary (
        cardno int,
        memName varchar(100),
        priorBalance decimal(10,2),
        threeMonthCharges decimal(10,2),
        threeMonthPayments decimal(10,2),
        threeMonthBalance decimal(10,2),    
        twoMonthCharges decimal(10,2),
        twoMonthPayments decimal(10,2),
        twoMonthBalance decimal(10,2),  
        lastMonthCharges decimal(10,2),
        lastMonthPayments decimal(10,2),
        lastMonthBalance decimal(10,2), 
        PRIMARY KEY (cardno)
        )',
        'INSERT INTO AR_EOM_Summary SELECT * FROM AR_EOM_Summary_cache',
        'DROP TABLE AR_EOM_Summary_cache',
        ),
        'archive' => array()
    );
}

?>
