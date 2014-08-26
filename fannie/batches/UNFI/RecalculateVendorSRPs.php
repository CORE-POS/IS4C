<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op

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

/* SRPs are re-calculated based on the current margin or testing
   settings, which may have changed since the order was imported */

/* configuration for your module - Important */
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RecalculateVendorSRPs extends FanniePage {
    protected $title = "Fannie - Vendor SRPs";
    protected $header = "Recalculate SRPs from Margins";

    public $description = '[Calculate Vendor SRPs] recalculates item SRPs based on vendor
    specific margin goals.';

    private $mode = 'form';

    function preprocess(){
        if(FormLib::get_form_value('vendorID') !== '')
            $this->mode = 'results';
        return True;
    }

    function body_content(){
        if ($this->mode == 'form')
            return $this->form_content();
        else if ($this->mode == 'results')
            return $this->results_content();
    }

    function results_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = FormLib::get_form_value('vendorID',0);

        $delQ = $dbc->prepare_statement("DELETE FROM vendorSRPs WHERE vendorID=?");
        $delR = $dbc->exec_statement($delQ,array($id));

        $query = 'select v.upc,v.cost,
            case when d.margin is not null then d.margin
                 when m.margin is not null then m.margin
                 else 0 end as margin
            from 
            vendorItems as v left join
            vendorDepartments as d
            on v.vendorID=d.vendorID
            and v.vendorDept=d.deptID
            left join products as p
            on v.upc=p.upc ';
        $departments = $dbc->tableDefinition('departments');
        if (isset($departments['margin'])) {
            $query .= ' LEFT JOIN departments AS m
                        ON p.department = m.dept_no ';
        } else if ($dbc->tableExists('deptMargin')) {
            $query .= ' left join deptMargin as m
                        on p.department=m.dept_ID ';
        }
        $query .= ' where v.vendorID=?
            and (d.margin is not null or m.margin is not null)';
        $fetchP = $dbc->prepare($query);
        $fetchR = $dbc->exec_statement($fetchP, array($id));
        $insP = $dbc->prepare_statement('INSERT INTO vendorSRPs VALUES (?,?,?)');
        while ($fetchW = $dbc->fetch_array($fetchR)) {
            // calculate a SRP from unit cost and desired margin
            $srp = round($fetchW['cost'] / (1 - $fetchW['margin']),2);

            // prices should end in 5 or 9, so add a cent until that's true
            while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" and
                   substr($srp,strlen($srp)-1,strlen($srp)) != "9")
                $srp+=.01;

            $insR = $dbc->exec_statement($insP,array($id,$fetchW['upc'],$srp));
        }

        $ret = "<b>SRPs have been updated</b><br />";
        $ret .= "<a href=index.php>Main Menu</a>";
        return $ret;
    }

    function form_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare_statement("SELECT vendorID,vendorName FROM vendors");
        $r = $dbc->exec_statement($q);
        $opts = "";
        while($w = $dbc->fetch_row($r))
            $opts .= "<option value=$w[0]>$w[1]</option>";
        ob_start();
        ?>
        <form action=RecalculateVendorSRPs.php method=get>
        Recalculate SRPs from margins for which vendor?<br />
        <select name=vendorID><?php echo $opts; ?></select>
        <input type=submit value="Recalculate" />
        </form>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
