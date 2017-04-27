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

namespace COREPOS\pos\lib\adminlogin;
use COREPOS\pos\lib\MiscLib;

/**
  @class DDDAdminLogin
  adminlogin callback for marking current
  items as shrink (DDD in WFC parlance [dropped, dented, damaged])
*/
class DDDAdminLogin implements AdminLoginInterface
{
    public static function messageAndLevel()
    {
        return array(_('Mark these items as shrink/unsellable?'), 10);
    }

    public static function adminLoginCallback($success)
    {
        if ($success) {
            return MiscLib::baseURL().'gui-modules/ddd.php';
        }

        return false;
    }

}

