<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class MemberNotesModel
*/
class MemberNotesModel extends BasicModel 
{

    protected $name = "memberNotes";

    protected $preferred_db = 'op';

    protected $columns = array(
    'memberNoteID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'cardno' => array('type'=>'INT', 'index'=>true),
    'note' => array('type'=>'TEXT'),
    'stamp' => array('type'=>'DATETIME'),
    'username' => array('type'=>'VARCHAR(50)')
    );

    public function doc()
    {
        return '
Depends on:
* custdata (table)

Use:
This table just holds generic blobs of text
associated with a given member. Used to make
a note about a membership and keep a record of
it.
        ';
    }
}

