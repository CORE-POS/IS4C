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
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CreateTagsByManu extends FanniePage {

    private $msgs = '';

    public $description = '[Brand Shelf Tags] generates a set of shelf tags for brand or UPC prefix.';
    public $themed = true;

    function preprocess()
    {
        global $FANNIE_OP_DB;

        $this->title = _("Fannie") . ' : ' . _("Manufacturer Shelf Tags");
        $this->header = _("Manufacturer Shelf Tags");

        if (FormLib::get_form_value('manufacturer',False) !== false) {
            $manu = FormLib::get_form_value('manufacturer');
            $pageID = FormLib::get_form_value('sID',0);
            $cond = "";
            if (is_numeric($_REQUEST['manufacturer']))
                $cond = " p.upc LIKE ? ";
            else
                $cond = " p.brand LIKE ? ";
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $prodP = $dbc->prepare("
                SELECT
                    p.upc
                FROM
                    products AS p
                WHERE $cond
            ");
            $prodR = $dbc->execute($prodP, array('%'.$manu.'%'));
            $tag = new ShelftagsModel($dbc);
            $product = new ProductsModel($dbc);
            while ($prodW = $dbc->fetch_row($prodR)) {
                $product->upc($prodW['upc']);
                $info = $product->getTagData();
                $tag->id($pageID);
                $tag->upc($prodW['upc']);
                $tag->setData($info);
                $tag->save();
            }
            $this->msgs = '<em>Created tags for manufacturer</em>
                    <br /><a href="ShelfTagIndex.php">Home</a>';
        }

        return true;
    }

    function body_content(){
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));

        $qmodel = new ShelfTagQueuesModel($dbc);
        $deptSubList = $qmodel->toOptions();

        $ret = '';
        if (!empty($this->msgs)){
            $ret .= '<div class="alert alert-success">';
            $ret .= $this->msgs;
            $ret .= '</div>';
        }

        ob_start();
        ?>
        <form action="CreateTagsByManu.php" method="get">
        <div class="form-group">
            <label>Name or UPC prefix</label>
            <input type="text" name="manufacturer" id="manu-field" 
                class="form-control" required />
        </div>
        <div class="form-group">
        <label>Page</label>
        <select name="sID" class="form-control">
            <?php echo $deptSubList; ?>
        </select>
        </div>
        <p>
            <button type="submit" class="btn btn-default">Create Shelftags</button>
        </p>
        </form>
        <?php
        $this->add_onload_command('$(\'#manu-field\').focus();');

        return $ret.ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Create shelf tags for all items with
            a given brand name or UPC prefix. Tags will be queued for
            printing under the selected super department.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

