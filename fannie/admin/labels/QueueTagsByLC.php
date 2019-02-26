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
use COREPOS\Fannie\API\lib\Store;

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class QueueTagsByLC extends FannieRESTfulPage 
{

    protected $title = 'Fannie - Add Shelf Tags by A Like Code';
    protected $header = 'Shelf Tag Queue by Like Code';
    public $description = '[Shelf Tag Queue by Like Code] Queue a set of shelf tags 
        by picking like codes.';
    public $themed = true;

    public function preprocess()
    {

        $this->__routes[] = 'get<list>';

        return parent::preprocess();
    }

    public function get_list_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = $this->get_view();
        $tagID = FormLib::get('tagID');
        $likecodes = FormLib::get('list');
        $upcP = $dbc->prepare("SELECT u.upc
            FROM upcLike AS u LEFT JOIN productUser AS p ON u.upc=p.upc
            WHERE u.likeCode=? ORDER BY p.description DESC");

        $product = new ProductsModel($dbc);
        $tag = new ShelftagsModel($dbc);
        $info = "";
        $aSuccess = "";
        $aDanger = "";
        foreach ($likecodes as $lc) {
            $upc = $dbc->getValue($upcP, array($lc));
            $product->upc($upc);
            $product->store_id($this->config->get('STORE_ID'));
            $product->load();
            unset($info);
            $info = $product->getTagData();

            $tag = new ShelftagsModel($dbc);
            $tag->upc($upc);
            $tag->id($tagID);
            $tag->description($info['description']);
            $tag->brand($info['brand']);
            $tag->normal_price($info['normal_price']);
            $tag->sku($info['sku']);
            $tag->size($info['size']);
            $tag->units($info['units']);
            $tag->vendor($info['vendor']);
            $tag->pricePerUnit($info['pricePerUnit']);
            $saved = $tag->save();
            if ($saved) {
                $aSuccess .= "{$upc}<br/>";    
            } else {
                $aDanger.= "{$upc}<br/>";    
            }
        }
        if ($aSuccess) {
            $success = "<div class='alert alert-success'>
                Successfully Queued UPCs<br/>{$aSuccess}</div>";
        }
        if ($aDanger) {
            $danger = "<div class='alert alert-danger'>
                UPCs that did not save to queue{$aDanger}</div>";
        }

        return <<<HTML
{$success}
{$danger}
{$ret}
HTML;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $queues = new ShelfTagQueuesModel($dbc); 
        $queues->load();
        $options = $queues->toOptions(6);

        $likeR = $dbc->query("SELECT l.likeCode, l.likeCodeDesc, MAX(m.inUse) AS inUse
            FROM likeCodes AS l
                LEFT JOIN LikeCodeActiveMap AS m ON l.likeCode=m.likeCode
            GROUP BY l.likeCode, l.likeCodeDesc
            ORDER BY l.likeCode");
        $lOpts = '';
        while ($row = $dbc->fetchRow($likeR)) {
            $lOpts .= sprintf('<option value="%d">%d %s</option>',
                $row['likeCode'],
                $row['likeCode'],
                $row['likeCodeDesc'] . (!($row['inUse']) ? ' (inactive)' : '')
            );
        }

        $this->addScript('../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen();");
        
        return <<<HTML
<p><button class="glyphicon glyphicon-chevron-left btn btn-default" id="back"></button></p>
<select class="form-control chosen" onchange="queueLC(event);">
    <option value="">Select likecode(s)</option>
    {$lOpts}
</select>
<form class="form-inline" method="get">
    <select name="list[]" class="form-control" size="10" id="lcList" multiple style="min-width: 100px;"></select>
    <select name="tagID" class="form-control">{$options}</select>
    <button type="submit" class="btn btn-default">Add to Queue</button>
</form>    
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
$('#back').click(function(){
    window.location.href = 'ShelfTagIndex.php';
    return false;
});
function queueLC(e) {
    var cur = $(e.target).find('option:selected');
    var newopt = '<option value="' + cur.val() + '">' + cur.text() + '</option>';
    $('#lcList').append(newopt);
    $('#lcList option').prop('selected', true);
}
HTML;
    }
}

FannieDispatch::conditionalExec();
