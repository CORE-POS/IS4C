<?php

use COREPOS\Fannie\API\item\signage\TagsNoPrice; 

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI.php')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorTags extends FannieRESTfulPage
{
    protected $header = 'Vendor Tags';
    protected $title = 'Vendor Tags';
    public $description = '[Vendor Tags] can print ordering shelf tags for vendor catalog items that aren\'t carried in-store';

    protected function post_id_handler()
    {
        $upcs = explode("\n", $this->id);
        $upcs = array_filter($upcs, function($i) { return strlen(trim($i)) > 0; });
        $upcs = array_map(function($i) { return BarcodeLib::padUPC($i); }, $upcs);
        list($inStr, $args) = $this->connection->safeInClause($upcs);
        $query = "
            SELECT v.upc,
                v.description,
                v.brand,
                v.units,
                v.size,
                v.sku,
                0 AS scale,
                0 AS numflag,
                '' AS startDate,
                '' AS endDate,
                '' AS batchName,
                '' AS pricePerUnit,
                '' AS unitofmeasure,
                0 AS originID,
                '' AS originName,
                '' AS originShortName,
                v.description AS posDescription,
                1 AS signCount,
                n.vendorName AS vendor
            FROM vendorItems AS v
                LEFT JOIN vendors AS n ON n.vendorID=v.vendorID
            WHERE upc IN ({$inStr})";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = $row;
        }

        $tags = new TagsNoPrice($data, 'provided');
        $tags->drawPDF();

        return false;
    }

    protected function get_view()
    {
        $this->addOnloadCommand("\$('#upc-in').focus();");
        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>UPCs</label>
        <textarea class="form-control" rows="10" id="upc-in" name="id"></textarea>
    </div>
    <p>
        <button class="btn btn-default btn-core">Get Tags</button>
    </p>
</form>
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
<p>
Enter one or more UPCs to generate a PDF of ordering tags.
This tool fills a specific gap in that it will print tags
for items that <strong>are</strong> in the vendor catalog
but <strong>are not</strong> among the items that the store
actually carries and sells (e.g., recipe ingredients).
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

