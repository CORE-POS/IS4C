<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');

class ExtraInfoModule extends ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<fieldset id="ExtraInfoFieldset">';
        $ret .=  "<legend onclick=\"\$('#ExtraInfoFieldsetContent').toggle();\">
                <a href=\"\" onclick=\"return false;\">Extra Info</a>
                </legend>";
        $css = ($expand_mode == 1) ? '' : 'display:none;';
        $ret .= '<div id="ExtraInfoFieldsetContent" style="' . $css . '">';

        $info = array('cost'=>0.00,'deposit'=>0,'local'=>0,'inUse'=>1,'modified'=>'Unknown','idEnforced'=>0);
        $dbc = $this->db();
        $p = $dbc->prepare_statement('SELECT cost,deposit,local,inUse,modified,idEnforced FROM products WHERE upc=?');
        $r = $dbc->exec_statement($p,array($upc));
        if ($dbc->num_rows($r) > 0) {
            $info = $dbc->fetch_row($r);
        }

        $local_opts = array(0=>'No');
        $p = $dbc->prepare_statement('SELECT originID,shortName FROM originName WHERE local=1 ORDER BY originID');
        $r = $dbc->exec_statement($p);
        while($w = $dbc->fetch_row($r)) {
            $local_opts[$w['originID']] = $w['shortName'];  
        }
        if (count($local_opts) == 0) {
            $local_opts[1] = 'Yes'; // generic local if no origins defined
        }

        $localSelect = '<select name="local">';
        foreach($local_opts as $id => $val) {
            $localSelect .= sprintf('<option value="%d" %s>%s</option>',
                $id, ($id == $info['local']?'selected':''), $val);
        }
        $localSelect .= '</select>';

        $ageSelect = '<select name="idReq">';
        $ages = array('n/a'=>0, 18=>18, 21=>21);
        foreach($ages as $label => $age) {
            $ageSelect .= sprintf('<option %s value="%d">%s</option>',
                            ($age == $info['idEnforced'] ? 'selected' : ''),
                            $age, $label);
        }
        $ageSelect .= '</select>';
        
        $ret .= "<table style=\"margin-top:5px;margin-bottom:5px;\" border=1 cellpadding=5 cellspacing=0 width='100%'><tr>";
        $ret .= '<tr><th>Deposit'.FannieHelp::ToolTip('PLU/UPC of linked deposit item').'</th>
            <th>Age Req.</th>
            <th>Local</th>
            <th>In Use'.FannieHelp::ToolTip('Uncheck to temporarily disable').'</th></tr>';
        $ret .= sprintf('<tr>
                <td align="center"><input type="text" size="5" value="%d" name="deposit" /></td>
                <td align="center">%s</td>
                <td align="center">%s</td>
                <td align="center"><input type="checkbox" name="inUse" value="1" %s /></td></tr>',
                $info['deposit'],
                $ageSelect,$localSelect,
                ($info['inUse']==1 ? 'checked': '')
        );
        $ret .= '</table>
                </div>
                </fieldset>';

        return $ret;
    }

    function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $deposit = FormLib::get_form_value('deposit',0);
        $inUse = FormLib::get_form_value('inUse',0);
        $local = FormLib::get_form_value('local',0);
        $idReq = FormLib::get_form_value('idReq',0);

        $dbc = $this->db();

        $pm = new ProductsModel($dbc);
        $pm->upc($upc);
        $pm->deposit($deposit);
        $pm->local($local);
        $pm->inUse($inUse);
        $pm->idEnforced($idReq);
        $r1 = $pm->save();

        if ($r1 === false) {
            return false;
        } else {
            return true;    
        }
    }
}

