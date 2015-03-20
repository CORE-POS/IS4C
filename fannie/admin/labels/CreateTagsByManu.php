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

    function preprocess(){
        global $FANNIE_OP_DB;

        $this->title = _("Fannie") . ' : ' . _("Manufacturer Shelf Tags");
        $this->header = _("Manufacturer Shelf Tags");

        if (FormLib::get_form_value('manufacturer',False) !== False){
            $manu = FormLib::get_form_value('manufacturer');
            $pageID = FormLib::get_form_value('sID',0);
            $cond = "";
            if (is_numeric($_REQUEST['manufacturer']))
                $cond = " p.upc LIKE ? ";
            else
                $cond = " x.manufacturer LIKE ? ";
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $q = $dbc->prepare_statement("
			    SELECT
			        p.upc,
			        p.description,
			        p.normal_price,
				    x.manufacturer,
				    x.distributor,
				    v.sku,
				    v.size AS pack_size_and_units,
				    CASE WHEN v.units IS NULL THEN 1 ELSE v.units END AS units_per_case
				FROM
				    products AS p
				    LEFT JOIN prodExtra AS x ON p.upc=x.upc
				    LEFT JOIN vendorItems AS v ON p.upc=v.upc
				    LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
				WHERE $cond
                ORDER BY p.upc,
                    CASE WHEN p.default_vendor_id=v.vendorID THEN 0 ELSE 1 END,
                    CASE WHEN x.distributor=n.vendorName THEN 0 ELSE 1 END,
                    v.vendorID
            ");
            $r = $dbc->exec_statement($q,array('%'.$manu.'%'));
            $tag = new ShelftagsModel($dbc);
            $prevUPC = 'invalidUPC';
            while($w = $dbc->fetch_row($r)){
                if ($prevUPC == $w['upc']) {
                    // multiple vendor matches for this item
                    // already created a tag for it w/ first
                    // priority vendor
                    continue;
                }
                $tag->id($pageID);
                $tag->upc($w['upc']);
                $tag->description($w['description']);
                $tag->normal_price($w['normal_price']);
                $tag->brand($w['manufacturer']);
                $tag->sku($w['sku']);
                $tag->size($w['pack_size_and_units']);
                $tag->units($w['units_per_case']);
                $tag->vendor($w['distributor']);
                $tag->pricePerUnit(\COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($w['normal_price'], $w['size']));
                $tag->save();
                $prevUPC = $w['upc'];
            }
            $this->msgs = '<em>Created tags for manufacturer</em>
                    <br /><a href="ShelfTagIndex.php">Home</a>';
        }
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
                GROUP BY superID,super_name
                ORDER BY superID");
        $deptSubR = $dbc->exec_statement($deptSubQ);

        $deptSubList = "";
        while($deptSubW = $dbc->fetch_array($deptSubR)){
            $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
        }

        $ret = '';
        if (!empty($this->msgs)){
            $ret .= '<div class="alert alert-success">';
            $ret .= $this->msgs;
            $ret .= '</blockquote>';
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
}

FannieDispatch::conditionalExec(false);

?>
