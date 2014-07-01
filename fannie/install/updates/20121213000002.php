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

class update_20121213000002 extends UpdateObj {

    protected $timestamp = '20121213000002';

    protected $description = 'This update
is to support a datestamp for changes to membership-
related tables.  It creates triggers that assign
custdata.LastChange.
Also see 20121213000001 which creates the field.';

    protected $author = 'Eric Lee (WEFC_Toronto)';

    protected $queries = array(
        'op' => array(
            'DROP TRIGGER if exists meminfo_update',
            'CREATE TRIGGER meminfo_update AFTER UPDATE ON meminfo
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists meminfo_insert',
            'CREATE TRIGGER meminfo_insert AFTER INSERT ON meminfo
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists meminfo_delete',
            'CREATE TRIGGER meminfo_delete BEFORE DELETE ON meminfo
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = OLD.card_no',

            'DROP TRIGGER if exists memberCards_update',
            'CREATE TRIGGER memberCards_update AFTER UPDATE ON memberCards
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists memberCards_insert',
            'CREATE TRIGGER memberCards_insert AFTER INSERT ON memberCards
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists memberCards_delete',
            'CREATE TRIGGER memberCards_delete BEFORE DELETE ON memberCards
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = OLD.card_no',

            'DROP TRIGGER if exists memContact_update',
            'CREATE TRIGGER memContact_update AFTER UPDATE ON memContact
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists memContact_insert',
            'CREATE TRIGGER memContact_insert AFTER INSERT ON memContact
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists memContact_delete',
            'CREATE TRIGGER memContact_delete BEFORE DELETE ON memContact
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = OLD.card_no',

            'DROP TRIGGER if exists memDates_update',
            'CREATE TRIGGER memDates_update AFTER UPDATE ON memDates
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists memDates_insert',
            'CREATE TRIGGER memDates_insert AFTER INSERT ON memDates
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = NEW.card_no',
            'DROP TRIGGER if exists memDates_delete',
            'CREATE TRIGGER memDates_delete BEFORE DELETE ON memDates
            FOR EACH ROW
            UPDATE custdata AS c SET c.LastChange = now()
            WHERE c.CardNo = OLD.card_no'

        ),
        'trans' => array(),
        'archive' => array()
    );
}

?>
