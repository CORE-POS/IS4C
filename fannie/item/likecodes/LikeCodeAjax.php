<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class LikeCodeAjax extends COREPOS\Fannie\API\FannieReadOnlyPage
{
    public $discoverable = false;

    protected function get_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $prep = $dbc->prepare("SELECT u.upc,p.description FROM
                upcLike AS u 
                    " . DTrans::joinProducts('u', 'p', 'INNER') . "
                WHERE u.likeCode=?
                ORDER BY p.description");
        $res = $dbc->execute($prep,array($this->id));
        $ret = "";
        while ($row = $dbc->fetch_row($res)) {
            $ret .= "<a style=\"font-size:90%;\" href=\"../ItemEditorPage.php?searchupc=$row[0]\">";
            $ret .= $row[0]."</a> ".substr($row[1],0,25)."<br />";
        }
        echo $ret;

        return false;
    }

    public function unitTest($phpunit)
    {
        ob_start();
        $this->id = 1;
        $phpunit->assertEquals(false, $this->get_id_handler());
        ob_end_clean();
    }
}

FannieDispatch::conditionalExec();

