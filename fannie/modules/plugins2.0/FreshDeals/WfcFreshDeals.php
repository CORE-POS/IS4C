<?php

class WfcFreshDeals extends \COREPOS\Fannie\API\item\ItemModule 
{
    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();
        $prod = new ProductsModel($dbc);
        $prod->upc($upc);
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $prod->store_id(FannieConfig::config('STORE_ID'));
        }
        $prod->load();
        $ret = '<div id="FreshDealsFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#FreshDealsDiv').toggle();return false;\">
                Fresh Deals</a>
                </div>";
        $ret .= '<div id="FreshDealsDiv" class="panel-body">';
        $ret .= sprintf('<table class="table table-bordered"><tr>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>$%.2f</td>
            <td>$%.2f</td>
            </tr></table>',
            $prod->brand(),
            $prod->description(),
            $prod->upc(),
            $prod->cost(),
            $prod->normal_price()
        );
        $ret .= '</div></div>';

        return $ret;
    }
}

