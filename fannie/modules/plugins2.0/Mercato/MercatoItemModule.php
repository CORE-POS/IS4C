<?php

use COREPOS\Fannie\API\item\ItemModule;

class MercatoItemModule extends ItemModule
{
    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $dbc = $this->db();
        $prep = $dbc->prepare("SELECT pieceWeight FROM MercatoItems WHERE upc=?");
        $val = $dbc->getValue($prep, array($upc));
        $ret = '<div id="MIMFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\"><a href=\"\" onclick=\"\$('#MIMFieldsetContent').toggle();return false;\">
                Mercato
                </a></div>";
        $ret .= '<div id="MIMFieldsetContent" class="panel-body collapse">';
        $ret .= '<div class="form-group">
                <label>Per-Piece Weight</label>
                <input type="text" name="mimWeight" class="form-control" value="' . $val . '" />
                </div>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function saveFormData($upc)
    {
        $dbc = $this->db();
        $prep = $dbc->prepare("UPDATE MercatoItems SET pieceWeight=? WHERE upc=?");
        $dbc->execute($prep, array(trim(FormLib::get('mimWeight')), $upc));
    }
}
