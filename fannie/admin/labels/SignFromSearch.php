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

    protected $signage_mod;
    protected $signage_obj;

    public function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'post<batch>'; 
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

        if (!$this->initModule()) {
            echo 'Error: no layouts available';
            return false;
        }

        $class_name = $this->signage_mod;
        $item_mode = FormLib::get('item_mode', 0);

        if (empty($this->upcs)) {
            echo 'Error: no valid data';
            return false;
        } else if (FormLib::get('pdf') == 'Print') {
            $this->signage_obj = new $class_name($this->upcs, '', $item_mode);
            $this->signage_obj->drawPDF();
            return false;
        } else {
            $this->signage_obj = new $class_name($this->upcs, '', $item_mode);
            return true;
        }
    }

    function post_batch_handler()
    {
        if (!is_array($this->batch)) {
            $this->batch = array($this->batch);
        }

        if (!$this->initModule()) {
            echo 'Error: no layouts available';
            return false;
        }

        $class_name = $this->signage_mod;

        if (empty($this->batch)) {
            echo 'Error: no valid data';
            return false;
        } else if (FormLib::get('pdf') == 'Print') {
            $this->signage_obj = new $class_name(array(), 'batch', $this->batch);
            $this->signage_obj->drawPDF();
            return false;
        } else {
            $this->signage_obj = new $class_name(array(), 'batch', $this->batch);
            return true;
        }
    }

    /**
      Detect selected or default layout module
      @return [boolean] success/failure
    */
    protected function initModule()
    {
        $mod = FormLib::get('signmod', false);
        if ($mod !== false) {
            $this->signage_mod = $mod;
            return true;
        } else {
            $mods = FannieAPI::listModules('FannieSignage');
            if (isset($mods[0])) {
                $this->signage_mod = $mods[0];
                return true;
            } else {
                return false;
            }
        }
    }

    function post_batch_view()
    {
        return $this->post_u_view();
    }

    function post_u_view()
    {
        $ret = '';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" id="signform">';
        $mods = FannieAPI::listModules('FannieSignage');
        $ret .= '<b>Layout</b>: <select name="signmod" onchange="$(\'#signform\').submit()">';
        foreach ($mods as $m) {
            $ret .= sprintf('<option %s>%s</option>',
                    ($m == $this->signage_mod ? 'selected' : ''), $m);
        }
        $ret .= '</select>';
        
        if (isset($this->upcs)) {
            foreach ($this->upcs as $u) {
                $ret .= sprintf('<input type="hidden" name="u[]" value="%s" />', $u);
            }
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $item_mode = FormLib::get('item_mode', 0);
            $modes = array('Current Retail', 'Upcoming Retail', 'Current Sale', 'Upcoming Sale');
            $ret .= '<select name="item_mode" onchange="$(\'#signform\').submit()">';
            foreach ($modes as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $item_mode ? 'selected' : ''),
                            $id, $label);
            }
            $ret .= '</select>';
        } else if (isset($this->batch)) {
            foreach ($this->batch as $b) {
                $ret .= sprintf('<input type="hidden" name="batch[]" value="%d" />', $b);
            }
        }
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" name="pdf" value="Print" />';
        $ret .= '</form>';
        $ret .= '<hr />';

        $ret .= $this->signage_obj->listItems();

        return $ret;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $batchQ = 'SELECT batchID, 
                    batchName,
                    startDate,
                    endDate
                   FROM batches AS b
                   WHERE
                    (b.startDate <= ? AND b.endDate >= ?)
                    OR b.startDate >=?
                   ORDER BY b.startDate DESC';
        $batchP = $dbc->prepare($batchQ);
        $today = date('Y-m-d');
        $batchR = $dbc->execute($batchP, array($today, $today, $today));

        $ret = '<b>Select batch(es)</b>:'; 
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<select name="batch[]" multiple size="15">';
        while ($batchW = $dbc->fetch_row($batchR)) {
            $ret .= sprintf('<option value="%d">%s (%s - %s)</option>',
                        $batchW['batchID'],
                        $batchW['batchName'],
                        date('Y-m-d', strtotime($batchW['startDate'])),
                        date('Y-m-d', strtotime($batchW['endDate']))
            );
        }
        $ret .= '</select>';
        $ret .= '<br /><br />';
        $ret .= '<input type="submit" value="Make Signs" />';
        $ret .= '</form>';

        return $ret;
    }

}

FannieDispatch::conditionalExec(false);

?>
