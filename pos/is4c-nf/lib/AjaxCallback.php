<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class AjaxCallback
*/
class AjaxCallback
{
    protected $encoding = 'json';

    public function getEncoding()
    {
        return $this->encoding;
    }

    protected function ajax()
    {

    }
 
    public static function run()
    {
        $callback_class = get_called_class();
        if (basename($_SERVER['PHP_SELF']) === $callback_class . '.php') {
            ini_set('display_errors', 'off');
            $obj = new $callback_class();
            $output = $obj->ajax();

            switch ($obj->getEncoding()) {
                case 'json':
                    echo JsonLib::array_to_json($output);
                    break;
                case 'plain':
                default:
                    echo $output;
                    break;
            }
        }
    }
}

