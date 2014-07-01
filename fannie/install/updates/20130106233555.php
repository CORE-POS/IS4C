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

class update_20130106233555 extends UpdateObj {

    protected $timestamp = '20130106233555';

    protected $description = 'Populate the table memContactPrefs, a list of available member contact preferences.
Also see 20130106233554 to create the table.';

    protected $author = 'Eric Lee (WEFC_Toronto)';

    protected $queries = array(
        'op' => array(
            'TRUNCATE TABLE memContactPrefs',
            "INSERT INTO memContactPrefs (pref_id, pref_description) VALUES (0, 'No contact of any kind')",
            "INSERT INTO memContactPrefs (pref_id, pref_description) VALUES (1, 'Postal mail only')",
            "INSERT INTO memContactPrefs (pref_id, pref_description) VALUES (2, 'Email only')",
            "INSERT INTO memContactPrefs (pref_id, pref_description) VALUES (3, 'Either postal mail or email')"
        ),
        'trans' => array(),
        'archive' => array()
    );
}

?>
