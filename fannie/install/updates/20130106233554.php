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

class update_20130106233554 extends UpdateObj {

    protected $timestamp = '20130106233554';

    protected $description = 'Create, or re-create, table for a list of available member contact preferences.
Describes values in memContact.
Also see 20130106233555 to populate the table.';

    protected $author = 'Eric Lee (WEFC_Toronto)';

    protected $queries = array(
        'op' => array(
            'DROP TABLE IF EXISTS memContactPrefs',
            'CREATE TABLE memContactPrefs (
                pref_id int,
                pref_description varchar(50),
                PRIMARY KEY (pref_id))'
        ),
        'trans' => array(),
        'archive' => array()
    );
}

?>
