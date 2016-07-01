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

class ExtraInfoModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<div id="ExtraInfoFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#ExtraInfoFieldsetContent').toggle();return false;\">
               Extra Info
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="ExtraInfoFieldsetContent" class="panel-body' . $css . '">';

        $info = array('cost'=>0.00,'deposit'=>0,'local'=>0,'inUse'=>1,'modified'=>'Unknown','idEnforced'=>0);
        $dbc = $this->db();
        $p = $dbc->prepare('SELECT cost,deposit,local,inUse,modified,idEnforced FROM products WHERE upc=?');
        $r = $dbc->execute($p,array($upc));
        if ($dbc->num_rows($r) > 0) {
            $info = $dbc->fetch_row($r);
        }

        $local_opts = array(0=>'No');
        $origin = new OriginsModel($dbc);
        $local_opts = array_merge($local_opts, $origin->getLocalOrigins());
        if (count($local_opts) == 1) {
            $local_opts[1] = 'Yes'; // generic local if no origins defined
        }

        $localSelect = '<select name="local" id="local-origin-id" class="form-control"
            onchange="$(\'#prod-local\').val(this.value);">';
        foreach($local_opts as $id => $val) {
            $localSelect .= sprintf('<option value="%d" %s>%s</option>',
                $id, ($id == $info['local']?'selected':''), $val);
        }
        $localSelect .= '</select>';

        $ageSelect = '<select name="idReq" id="idReq" class="form-control"
            onchange="$(\'#id-enforced\').val(this.value);">';
        $ages = array('n/a'=>0, 18=>18, 21=>21);
        foreach($ages as $label => $age) {
            $ageSelect .= sprintf('<option %s value="%d">%s</option>',
                            ($age == $info['idEnforced'] ? 'selected' : ''),
                            $age, $label);
        }
        $ageSelect .= '</select>';
        
        $ret .= "<table class=\"table table-bordered\" width='100%'><tr>";
        $ret .= '<tr><th>Deposit'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('PLU/UPC of linked deposit item').'</th>
            <th>Age Req.</th>
            <th>Local</th>
            <th>In Use'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('Uncheck to temporarily disable').'</th></tr>';
        $ret .= sprintf('<tr>
                <td align="center"><input type="text" class="form-control" value="%d" name="deposit" 
                    id="deposit" onchange="$(\'#deposit-upc\').val(this.value);" /></td>
                <td align="center">%s</td>
                <td align="center">%s</td>
                <td align="center">
                    <input type="checkbox" id="extra-in-use-checkbox" name="inUse" value="1" %s 
                        onchange="$(\'#in-use-checkbox\').prop(\'checked\', $(this).prop(\'checked\'));" />
                </td></tr>',
                $info['deposit'],
                $ageSelect,$localSelect,
                ($info['inUse']==1 ? 'checked': '')
        );
        $ret .= '</table>
                </div>
                </div>';

        return $ret;
    }

    function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        try {
            $dbc = $this->db();

            $pm = new ProductsModel($dbc);
            $pm->upc($upc);
            $pm->store_id(1);
            $pm->deposit($this->form->deposit);
            $pm->local($this->form->local);
            $pm->inUse($this->form->inUse);
            $pm->idEnforced($this->form->idReq);
            $pm->enableLogging(false);
            $r1 = $pm->save();

            return $r1 === false ? false : true;
        } catch (Exception $ex) {
            return false;
        }
    }
}

