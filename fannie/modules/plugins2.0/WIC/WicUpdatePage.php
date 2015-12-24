<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class WicUpdatePage extends FannieRESTfulPage
{
    protected $title = "Fannie :: WIC Items";
    protected $header = "Fannie :: WIC Items";

    public function post_id_handler()
    {
        preg_match_all('/\d+/', $this->id, $upcs);
        $upcs = $upcs[0];
        $args = array_map(function($i){ return BarcodeLib::padUPC(trim($i)); }, $upcs);
        $in_str = str_repeat('?,', count($args));
        $in_str = substr($in_str, 0, strlen($in_str)-1);

        $prep = $this->connection->prepare('
            UPDATE products
            SET wicable=1
            WHERE upc IN (' . $in_str . ')');
        $res = $this->connection->execute($prep, $args);

        var_dump($res);

        return false;
    }

    public function get_view()
    {
        return '<form method="post">
            <div class="form-group">
                <label>UPCs</label>
                <textarea name="id" rows="20" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Submit</button>
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

