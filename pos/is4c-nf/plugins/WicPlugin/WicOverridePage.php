<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class WicOverridePage extends BasicCorePage 
{
    private $step = 0;
    private $box_color="coloredArea";
    private $errMsg;
    private $upc;

    protected $mask_input = true;

    public function preprocess()
    {
        $this->upc = FormLib::get('upc');
        if (FormLib::get('reginput', false) !== false) {
            $inp = FormLib::get('reginput');
            if (strtoupper($inp) == 'CL') {
                $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                return false;
            }
            $dbc = Database::pDataConnect();
            $empP = $dbc->prepare('
                SELECT emp_no
                FROM employees
                WHERE EmpActive=1
                    AND frontendsecurity >= ?
                    AND (CashierPassword=? OR AdminPassword=?)');
            if ($dbc->getValue($empP, array(30, $inp, $inp)) !== false) {
                $arr = CoreLocal::get('WicOverride');
                if (!is_array($arr)) {
                    $arr = array();
                }
                $arr[] = ltrim($this->upc, '0');
                CoreLocal::set('WicOverride', $arr);
                $this->change_page(
                    MiscLib::baseURL() 
                    . 'gui-modules/pos2.php'
                    . '?reginput=' . urlencode($this->upc)
                    . '&repeat=1');
                return false;
            } else {
                $this->box_color = 'errorColoredArea';
            }
        }

        return true;
    }

    public function body_content()
    {
        $this->input_header();
        echo DisplayLib::printheaderb();
        echo '<div class="baseHeight">';
        echo '<div class=" ' .$this->box_color . ' centeredDisplay">';
        echo '<span class="larger">';
        echo $this->upc . ' - ' . $this->getItem() . '<br />';
        echo 'Item is not eligible';
        echo '</span><br />';
        echo '<p>';
        echo 'Type manager password to override.<br />';
        echo '[clear] to go back';
        echo '</p>';
        echo '</div>';
        echo '</div>';
        Database::getsubtotals();
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
        $this->add_onload_command("\$('<input type=\"hidden\" name=\"upc\">').val(" . $this->upc . ").appendTo('#formlocal');\n");
    }

    private function getItem()
    {
        $dbc = Database::pDataConnect();
        $upc = substr('0000000000000' . $this->upc, -13);
        $itemP = $dbc->prepare('SELECT description FROM products WHERE upc=?');
        return $dbc->getValue($itemP, array($upc));
    }

}

AutoLoader::dispatch();

