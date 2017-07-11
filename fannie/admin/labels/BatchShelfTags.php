<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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
if (!class_exists('FannieAPI.php')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchShelfTags extends FanniePage {

    protected $title = "Fannie : Batch Barcodes";
    protected $header = "Batch Barcodes";

    public $description = '[Batch Shelf Tags] generates PDF shelftags for items in a batch.';
    public $themed = true;

    private $layouts = array();

    function preprocess(){
        if (!function_exists('scan_layouts')) {
            require('scan_layouts.php');
        }
        $this->layouts = scan_layouts();
        return True;
    }

    function body_content(){
        global $FANNIE_URL, $FANNIE_DEFAULT_PDF;
        ob_start();
        ?>
        <ul class="nav nav-tabs" role="tablist">
            <li><a href="ShelfTagIndex.php">Regular shelf tags</a></li>
            <li class="active"><a href="BatchShelfTags.php">Batch shelf tags</a></li>
        </ul>
        <?php
        $ret = ob_get_clean();

        $ret .= "<form id=\"batch-tag-form\" action=genLabels.php method=get>";
        //echo "<form action=barcodenew.php method=get>";
        $ret .= "<label>Select batch(es*) to be printed</label>";
        
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $fetchQ = $dbc->prepare("select b.batchID,b.batchName
              from batches as b left join
              batchBarcodes as c on b.batchID = c.batchID
              where c.upc is not null
                  group by b.batchID,b.batchName
                  order by b.batchID desc");
        $fetchR = $dbc->execute($fetchQ);
        $ret .= "<select name=batchID[] multiple class=\"form-control\" size=15>";
        while($fetchW = $dbc->fetchRow($fetchR))
            $ret .= "<option value=$fetchW[0]>$fetchW[1]</option>";
        $ret .= "</select>";
        $ret .= '<p><div class="form-group form-inline">';
        $ret .= "<label>Offset</label>: <input type=\"number\" 
            class=\"form-control\" name=offset value=0 />";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<select name=layout class=\"form-control\">";
        $tagEnabled = $this->config->get('ENABLED_TAGS');
        foreach($this->layouts as $l){
            if (!in_array($l, $tagEnabled) && count($tagEnabled) > 0) continue;
            if ($l == $FANNIE_DEFAULT_PDF)
                $ret .= "<option selected>".$l."</option>";
            else
                $ret .= "<option>".$l."</option>";
        }   
        $ret .= "</select>";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<button type=submit class=\"btn btn-default\">Print</button>";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<button type=submit class=\"btn btn-default\" 
            onclick=\"\$('#batch-tag-form').attr('action','EditBatchTags.php').submit(); return false;\">
            Edit Tag Info</button>";
        $ret .= "</div></p>";
        $ret .= "</form>";

        $ret .= '<div class="well">';
        $ret .= "<a href={$FANNIE_URL}batches/newbatch/index.php>Back to batch list</a><p />";
        $ret .= "* Hold the apple key while clicking to select multiple batches ";
        $ret .= "(or the control key if you're not on a Mac)";
        $ret .= '</div>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Select one or more batches and generate a PDF
            with the associated tags.</p>
            <p>The dropdown box lists all available shelf tag layouts. The
            offset value will leave a number of tags at the beginning of
            the sheet blank. This is intended for re-using partial sheets.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

