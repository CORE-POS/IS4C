<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class update_20130502000001 extends UpdateObj {

    protected $timestamp = '20130502000001';

    protected $description = 'Add the following columns to
        dlog_15: memType, staff, numflag, charflag. This will
        also drop the view dlog_90_view so it can be recreated
        with the same columns included. Click the necessities tab
        after this update to recreate the view.';

    protected $author = 'Andy Theuninck (WFC)';

    protected $queries = array(
        'op' => array(),
        'trans' => array(
            'ALTER TABLE dlog_15 ADD COLUMN memType TINYINT AFTER ItemQtty',
            'ALTER TABLE dlog_15 ADD COLUMN staff TINYINT AFTER memType',
            'ALTER TABLE dlog_15 ADD COLUMN numflag INT AFTER staff',
            'ALTER TABLE dlog_15 ADD COLUMN charflag VARCHAR(2) AFTER numflag',
            'DROP VIEW dlog_90_view'
        ),
        'archive' => array()
    );
}

?>
