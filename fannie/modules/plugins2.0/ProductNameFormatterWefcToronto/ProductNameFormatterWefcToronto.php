<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op
    Copyright 2015 West End Food Co-op, Toronto, Canada

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

if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/classlib2.0/FannieAPI.php');
}

class ProductNameFormatterWefcToronto extends \COREPOS\Fannie\API\FanniePlugin {

    public $plugin_settings = array();

    public $plugin_description = 'Format products.formatted_name as:
<br />products.description <size><unitofmeasure>
<br />e.g. Tomato ketchup 375ml
<br />for use on the receipt, truncating description as needed.';

}

