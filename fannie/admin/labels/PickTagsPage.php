<?php

use COREPOS\Fannie\API\item\signage\Tags4x8P;

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class PickTagsPage extends FannieRESTfulPage 
{
    protected $header = 'Pick Tags';
    protected $title = 'Pick Tags';
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute('post<u>');
        return parent::preprocess();
    }

    protected function post_id_handler()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->id);
        $map = array();
        for ($i=0; $i<count($this->id); $i++) {
            $map[$this->id[$i]] = $this->form->qty[$i];
        }

        $itemP = $this->connection->prepare("
            SELECT p.upc,
                p.brand,
                p.description,
                p.size,
                v.units,
                n.vendorName,
                v.sku
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE p.upc IN ({$inStr})
            ORDER BY d.salesCode, p.brand, p.description");
        $itemR = $this->connection->execute($itemP, $args);
        $items = array();
        while ($itemW = $this->connection->fetchRow($itemR)) {
            $upc = $itemW['upc'];
            if (!isset($map[$upc])) continue;

            $item = array(
                'upc' => $upc,
                'description' => $itemW['description'],
                'posDescription' => $itemW['description'],
                'brand' => $itemW['brand'],
                'normal_price' => 0,
                'units' => $itemW['units'],
                'size' => $itemW['size'],
                'sku' => $itemW['sku'],
                'vendor' => $itemW['vendorName'],
                'scale' => 0,
                'numflag' => 0,
                'pricePerUnit' => '',
            );
            for ($i=0; $i<$map[$upc]; $i++) {
                $item['normal_price'] = ($i+1) . '/' . $map[$upc];
                $items[] = $item;
            }
            unset($map[$upc]);
        }

        $pdf = new Tags4x8P($items, 'provided');
        $pdf->drawPDF();

        return false;
    }

    protected function post_u_view()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->u);
        $simpP = $this->connection->prepare("
            SELECT upc, brand, description
            FROM products
            WHERE upc IN ({$inStr})
            GROUP BY upc, brand, description
            ORDER BY upc, brand, description");
        $simpR = $this->connection->execute($simpP, $args);
        $tableBody = '';
        while ($row = $this->connection->fetchRow($simpR)) {
            $tableBody .= sprintf('<tr>
                <td><input type="hidden" name="id[]" value="%s" />
                <input type="number" class="form-control" name="qty[]" value="1" /></td>
                <td>%s</td><td>%s</td><td>%s</td></tr>',
                $row['upc'], $row['upc'], $row['brand'], $row['description']);
        }

        return <<<HTML
<form method="post">
    <p><button type="submit" class="btn btn-default">Get Tags</button></p>
    <table class="table table-bordered table-striped">
        <tr><th>Qty</th><th>UPC</th><th>Brand</th><th>Description</th></tr>
        {$tableBody}
    </table>
    <p><button type="submit" class="btn btn-default">Get Tags</button></p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

