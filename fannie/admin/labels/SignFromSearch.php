<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class SignFromSearch extends FannieRESTfulPage 
{

	protected $title = 'Fannie - Signage';
	protected $header = 'Signage';

    public function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       return parent::preprocess();
    }

    function post_u_handler()
    {
        if (!is_array($this->u)) {
            $this->u = array($this->u);
        }
        foreach($this->u as $postdata) {
            if (is_numeric($postdata)) {
                $this->upcs[] = BarcodeLib::padUPC($postdata);
            }
        }

        if (empty($this->upcs)) {
            echo 'Error: no valid data';
            return false;
        } else if (FormLib::get('pdf') == 'Print') {
            $mod = FormLib::get('signmod');
            $obj = new $mod($this->upcs);
            $obj->drawPDF();
            return false;
        } else {
            return true;
        }
    }

    function post_u_view()
    {
        $mod = FormLib::get('signmod', false);
        $ret = '';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" id="signform">';
        $mods = FannieAPI::listModules('FannieSignage');
        $ret .= '<b>Layout</b>: <select name="signmod" onchange="$(\'#signform\').submit())">';
        foreach ($mods as $m) {
            $ret .= sprintf('<option %s>%s</option>',
                    ($m == $mod ? 'selected' : ''), $m);
        }
        $ret .= '</select>';

        if ($mod === false && isset($mods[0])) {
            $mod = $mods[0];
        }
        $signage = new $mod($this->upcs);

        foreach ($this->upcs as $u) {
            $ret .= sprintf('<input type="hidden" name="u[]" value="%s" />', $u);
        }
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" name="pdf" value="Print" />';
        $ret .= '</form>';
        $ret .= '<hr />';

        $ret .= $signage->listItems();

        return $ret;
    }

}

FannieDispatch::conditionalExec(false);

?>
