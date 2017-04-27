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
use \CoreLocal;

/**
  @class AgeApproveAdminLogin
  adminlogin callback for permitting underage age
  cashiers to sell age-restricted items
*/
class AgeApproveAdminLogin implements AdminLoginInterface
{
    public static function messageAndLevel()
    {
        return array(_('Login to approve sale'), 30);
    }

    public static function adminLoginCallback($success)
    {
        if ($success) {
            CoreLocal::set('refundComment', CoreLocal::get('strEntered'));    
            CoreLocal::set('cashierAgeOverride', 1);
            $inp = urlencode(CoreLocal::get('strEntered'));
            return MiscLib::baseURL() . 'gui-modules/pos2.php?reginput=' . $inp . '&repeat=1';
        }
        CoreLocal::set('cashierAgeOverride', 0);
        return false;
    }
}

