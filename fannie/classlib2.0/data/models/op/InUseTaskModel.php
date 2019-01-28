<?php

/*******************************************************************************

    Copyright 2018 Whole Foods Co-op

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
  @class InUseTaskModel
*/
class InUseTaskModel extends BasicModel
{
    protected $name = "inUseTask";
    protected $preferredDB = 'op';

    protected $columns = array(
    'superID' => array('type'=>'int', 'primary_key'=>true),
    'time' => array('type'=>'int'),
    );

    public function doc()
    {
        return '
Usage:
Define automatic inUse expiration on a per-super department level.
The InUseTask will mark an item as no longer in use when [time] seconds
has passed since the item last sold.
            ';
    }
}

