<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

class ItemFlagsModule extends ItemModule 
{

    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $ret = '';
        $ret = '<div id="ItemFlagsFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#ItemFlagsContents').toggle();return false;\">
                Flags
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="ItemFlagsContents" class="panel-body' . $css . '">';
        // class="col-lg-1" works pretty well with META_WIDTH_HALF
        $ret .= '<div id="ItemFlagsTable" class="col-sm-5">';

        $dbc = $this->db();
        $q = "SELECT f.description,
            f.bit_number,
            (1<<(f.bit_number-1)) & p.numflag AS flagIsSet
            FROM products AS p, prodFlags AS f
            WHERE p.upc=?";
        $p = $dbc->prepare_statement($q);
        $r = $dbc->exec_statement($p,array($upc));
        
        if ($dbc->num_rows($r) == 0){
            // item does not exist
            $p = $dbc->prepare_statement('SELECT f.description,f.bit_number,0 AS flagIsSet
                    FROM prodFlags AS f');
            $r = $dbc->exec_statement($p);
        }

        $tableStyle = " style='border-spacing:5px; border-collapse: separate;'";
        $ret .= "<table{$tableStyle}>";
        $i=0;
        while($w = $dbc->fetch_row($r)){
            if ($i==0) $ret .= '<tr>';
            if ($i != 0 && $i % 2 == 0) $ret .= '</tr><tr>';
            $ret .= sprintf('<td><input type="checkbox" name="flags[]" value="%d" %s /></td>
                <td>%s</td>',$w['bit_number'],
                ($w['flagIsSet']==0 ? '' : 'checked'),
                $w['description']
            );
            $i++;
        }
        $ret .= '</tr></table>';

        $ret .= '</div>' . '<!-- /#ItemFlagsTable -->';
        $ret .= '</div>' . '<!-- /#ItemFlagsContents -->';
        $ret .= '</div>' . '<!-- /#ItemFlagsFieldset -->';

        return $ret;
    }

    public function saveFormData($upc)
    {
        $flags = $this->form->flags;
        if (!is_array($flags)) {
            return false;
        }
        $numflag = 0;   
        foreach ($flags as $f) {
            if ($f != (int)$f) {
                continue;
            }
            $numflag = $numflag | (1 << ($f-1));
        }
        $dbc = $this->connection;
        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->numflag($numflag);
        $saved = $model->save();

        return $saved ? true : false;
    }
}

