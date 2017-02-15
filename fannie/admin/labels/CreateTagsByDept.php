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

class CreateTagsByDept extends FanniePage {

    protected $title = "Fannie : Department Shelf Tags";
    protected $header = "Department Shelf Tags";

    public $description = '[Department Shelf Tags] generates a set of shelf tags for given POS
    department(s).';
    public $themed = true;

    private $msgs = '';

    function preprocess()
    {
        global $FANNIE_OP_DB;
        if (FormLib::get_form_value('deptStart',False) !== false) {
            $start = FormLib::get_form_value('deptStart');
            $end = FormLib::get_form_value('deptEnd');
            $pageID = FormLib::get_form_value('sID',0);
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $prodP = $dbc->prepare("
                SELECT p.upc
                FROM products AS p
                WHERE p.department BETWEEN ? AND ?
            ");
            $prodR = $dbc->execute($prodP, array($start,$end));
            $tag = new ShelftagsModel($dbc);
            $product = new ProductsModel($dbc);
            while ($row = $dbc->fetch_row($prodR)) {
                $product->upc($row['upc']);
                $info = $product->getTagData();
                $tag->id($pageID);
                $tag->upc($row['upc']);
                $tag->setData($info);
                $tag->save();
            }
            $this->msgs = sprintf('<em>Created tags for departments #%d through #%d</em>
                    <br /><a href="ShelfTagIndex.php">Home</a>',
                $start, $end);
        }

        return true;
    }

    function body_content()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $deptsQ = $dbc->prepare("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $dbc->execute($deptsQ);
        $deptsList = "";

        $qmodel = new ShelfTagQueuesModel($dbc);
        $deptSubList = $qmodel->toOptions();

        while ($deptsW = $dbc->fetchRow($deptsR))
          $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

        $ret = '';
        if (!empty($this->msgs)){
            $ret .= '<div class="alert alert-success">';
            $ret .= $this->msgs;
            $ret .= '</div>';
        }

        ob_start();
        ?>
        <form action="CreateTagsByDept.php" method="get">
        <div class="row form-group form-horizontal"> 
            <label class="col-sm-2">Department Start</label>
            <div class="col-sm-4">
                <select onchange="$('#deptStart').val($(this).val());"
                    class="form-control">
                    <?php echo "$deptsList\n" ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type=text name=deptStart id=deptStart class="form-control" value=1 />
            </div>
        </div>
        <div class="row form-group form-horizontal"> 
            <label class="col-sm-2">Department End</label>
            <div class="col-sm-4">
                <select onchange="$('#deptEnd').val($(this).val());"
                    class="form-control">
                    <?php echo "$deptsList\n" ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type=text name=deptEnd id=deptEnd class="form-control" value=1 />
            </div>
        </div>
        <div class="row form-group form-horizontal">
            <label class="col-sm-2">Page</label>
            <div class="col-sm-4">
                <select name="sID" class="form-control">
                    <?php echo $deptSubList; ?></select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-default">Create Shelftags</button>
            </div>
        </div>
        </form>
        <?php
        return $ret.ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Create shelf tags for all items in a 
            POS department range. Tags will be queued for
            printing under the selected super department.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

