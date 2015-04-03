<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

namespace COREPOS\Fannie\API\lib {

/**
  @class FannieUI
  Generator methods for common UI elements' markup
*/
class FannieUI 
{
    public static function editIcon($alt='Edit')
    {
        return '<span class="glyphicon glyphicon-pencil" title="' . $alt . '"></span>';
    }

    public static function saveIcon($alt='Save')
    {
        return '<span class="glyphicon glyphicon-floppy-disk" title="' . $alt . '"></span>';
    }

    public static function deleteIcon($alt='Delete')
    {
        return '<span class="glyphicon glyphicon-trash" title="' . $alt . '"></span>';
    }

    public static function loadingBar($id='')
    {
        return '
        <div class="progress" ' . (!empty($id) ? "id=\"$id\"" : '') . '>
            <div class="progress-bar progress-bar-striped active"  role="progressbar" 
                aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                <span class="sr-only">Loading</span>
            </div>
        </div>';
    }

}

}

namespace {
    class FannieUI extends \COREPOS\Fannie\API\lib\FannieUI {}
}

