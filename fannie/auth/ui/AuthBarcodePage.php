<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op, Duluth, MN

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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class AuthBarcodePage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $title = 'Fannie : Auth : Generate Barcode';
    protected $header = 'Fannie : Auth : Generate Barcode';

    public $description = "Generate barcode containing provided username and password";

    protected function post_handler()
    {
        // two spaces used as field separator
        // extra "X" at the end because the last character is 
        // automatically dropped as a check digit
        $string = $this->form->name . '  ' . $this->form->pw . 'X';

        Image_Barcode2::draw($string, 'code128', 'png', true, 69, 2, false);

        return false;
    }

    protected function get_view()
    {
        $this->addOnloadCommand("\$('#pw-in').focus();\n");
        return '<form method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" name="name" value="' . $this->current_user . '" />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="pw-in" class="form-control" name="pw" />
            </div>
            <p>
                <button class="btn btn-default btn-core">Generate Barcode</button>
            </p>
            </form>';
    }
}

FannieDispatch::conditionalExec();

