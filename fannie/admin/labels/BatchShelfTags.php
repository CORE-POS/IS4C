<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI.php')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchShelfTags extends FanniePage {

    protected $title = "Fannie : Batch Barcodes";
    protected $header = "Batch Barcodes";

    public $description = '[Batch Shelf Tags] generates PDF shelftags for items in a batch.';

    private $layouts = array();

    function preprocess(){
        if (!function_exists('scan_layouts')) {
            require('scan_layouts.php');
        }
        $this->layouts = scan_layouts();
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_URL, $FANNIE_DEFAULT_PDF;
        ob_start();
        ?>
        <a href="ShelfTagIndex.php">Regular shelf tags</a>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Batch shelf tags
        <p />
        <?php
        $ret = ob_get_clean();

        $ret .= "<form action=genLabels.php method=get>";
        //echo "<form action=barcodenew.php method=get>";
        $ret .= "<b>Select batch(es*) to be printed</b>:<br />";
        
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $fetchQ = $dbc->prepare_statement("select b.batchID,b.batchName
              from batches as b left join
              batchBarcodes as c on b.batchID = c.batchID
              where c.upc is not null
                  group by b.batchID,b.batchName
                  order by b.batchID desc");
        $fetchR = $dbc->exec_statement($fetchQ);
        $ret .= "<select name=batchID[] multiple style=\"{width:300px;}\" size=15>";
        while($fetchW = $dbc->fetch_array($fetchR))
            $ret .= "<option value=$fetchW[0]>$fetchW[1]</option>";
        $ret .= "</select><p />";
        $ret .= "<fieldset>";
        $ret .= "Offset: <input size=3 type=text name=offset value=0 />";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<select name=layout>";
        foreach($this->layouts as $l){
            if ($l == $FANNIE_DEFAULT_PDF)
                $ret .= "<option selected>".$l."</option>";
            else
                $ret .= "<option>".$l."</option>";
        }   
        $ret .= "</select>";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<input type=submit value=Print />";
        $ret .= "</fieldset>";
        $ret .= "</form>";
        $ret .= "<a href={$FANNIE_URL}batches/newbatch/index.php>Back to batch list</a><p />";
        $ret .= "* Hold the apple key while clicking to select multiple batches ";
        $ret .= "(or the control key if you're not on a Mac)";

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
