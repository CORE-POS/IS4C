<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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

namespace COREPOS\pos\lib\models\trans;
use COREPOS\pos\lib\models\BasicModel;

/*
if (!class_exists('\\COREPOS\\pos\lib\\models\\trans\\LocalTransModel')) {
    include_once(dirname(__FILE__).'/LocalTransModel.php');
}
*/

/**
  @class LocalTransTodayViewModel
*/
class LocalTransTodayViewModel extends \COREPOS\pos\lib\models\trans\LocalTransModel
{

    protected $name = "localtranstoday";

    /* disabled because it's a view */
    public function create(){ return false; }
    public function delete(){ return false; }
    public function save(){ return false; }
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){ return 0; }

    public function doc()
    {
        return '';
    }

}

