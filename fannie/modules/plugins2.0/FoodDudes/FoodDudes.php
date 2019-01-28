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

namespace COREPOS\Fannie\Plugin\FoodDudes;
use COREPOS\Fannie\API\FanniePlugin;

/**
*/
class FoodDudes extends FanniePlugin 
{
    public $plugin_settings = array(
    'FdRegNo' => array('default'=>'30','label'=>'Register #',
        'description'=>'Register number when importing transactions'),
    'FdEmpNo' => array('default'=>'1001','label'=>'Employee #',
        'description'=>'Employee number when importing transactions'),
    'FdUser' => array('default'=>'','label'=>'User name',
        'description'=>'User name to log into Food Dudes'),
    'FdPasswd' => array('default'=>'','label'=>'Password',
        'description'=>'Password to log into Food Dudes'),
    );

    public $plugin_description = 'Plugin for Food Dudes integration';
}

