<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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
  @class MemStatusAdminLogin
  adminlogin callback for approving member status toggle
*/
class MemStatusAdminLogin 
{

    public static $adminLoginMsg = 'Login to toggle member status';
    
    public static $adminLoginLevel = 30;

    public static function adminLoginCallback($success)
    {
        global $CORE_LOCAL;
        if ($success) {
            $CORE_LOCAL->set('strRemembered', '');
            $CORE_LOCAL->set("isMember", 1);
            $CORE_LOCAL->set("memType", 1);
            $CORE_LOCAL->set("boxMsg", "Member Status Toggled!");

            return MiscLib::baseURL().'gui-modules/boxMsg2.php';
        } else {
            return false;
        }
    }

}

