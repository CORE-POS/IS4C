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
  @class ReasoncodesModel
*/
class ReasoncodesModel extends BasicModel 
{

    protected $name = "reasoncodes";

    protected $preferred_db = 'op';

    protected $columns = array(
    'textStr' => array('type'=>'VARCHAR(100)'),
    'mask' => array('type'=>'INT','primary_key'=>True,'default'=>0)
    );

    public function doc()
    {
        return '
Depends on:
* suspensions

Use:
Reason code work in conjunction with suspended
memberships. The mask here is a bitmask. This
lets you tag the suspensions.reasonCode with
multiple reasons in one field. Probably not the
        ';
    }
}

