<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

namespace COREPOS\pos\lib\models\op;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class CustomerNotificationsModel
*/
class CustomerNotificationsModel extends BasicModel
{

    protected $name = "CustomerNotifications";
    
    protected $preferred_db = 'op';

    protected $columns = array(
    'customerNotificationID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'cardNo' => array('type'=>'INT'),
    'customerID' => array('type'=>'INT', 'default'=>0),
    'source' => array('type'=>'VARCHAR(50)'),
    'type' => array('type'=>'VARCHAR(50)'),
    'message' => array('type'=>'VARCHAR(255)'),
    'modifierModule' => array('type'=>'VARCHAR(50)'),
    );

    public function doc()
    {
        return '
Use:
Display account specific or customer specific
messages in various ways at the lane.';
    }

}

