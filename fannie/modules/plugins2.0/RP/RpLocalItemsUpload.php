<?php

use COREPOS\Fannie\API\FannieUploadPage;

include(__DIR__ . '/../../../config.php');

if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('RpLocalItemsModel')) {
    include(__DIR__ . '/models/RpLocalItemsModel.php');
}

class RpLocalItemsUpload extends FannieUploadPage
{
    protected $header = 'Upload Local Items';
    protected $title = 'Upload Local Items';

    protected $preview_opts = array(
        'name' => array(
            'name' => 'name',
            'display_name' => 'Name',
            'default' => 0,
            'required' => true,
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Price',
            'default' => 1,
            'required' => true,
        ),
        'lc' => array(
            'name' => 'lc',
            'display_name' => 'Like Code',
            'default' => 3,
            'required' => true,
        ),
    );

    public function process_file($linedata, $indexes)
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $upcs = array();
        $catP = $dbc->prepare("SELECT rpOrderCategoryID
            FROM RpOrderCategories AS r
                INNER JOIN likeCodes AS l ON r.name = l.sortRetail
            WHERE l.likeCode=?");
        $dbc->query('TRUNCATE TABLE RpLocalItems');
        $dbc->startTransaction();
        foreach ($linedata as $line) {
            $name = trim($line[$indexes['name']]);
            $price = str_replace('$', '', trim($line[$indexes['price']]));
            $likecode = trim($line[$indexes['lc']]);
            if (!is_numeric($price) || !is_numeric($likecode)) {
                continue;
            }
            $model = new RpLocalItemsModel($dbc);
            $model->likeCode($likecode);
            $upc = 'LC' . $likecode;
            if (isset($upcs[$upc])) {
                $upc = substr($upc . '-' . md5($name), 0, 13);
            }
            $upcs[$upc] = true;
            $model->upc($upc);
            $model->vendorItem($name);
            $model->cost($price);
            $model->categoryID($dbc->getValue($catP, array($likecode)));
            $model->save();
        }
        $dbc->commitTransaction();

        return true;
    }
}

FannieDispatch::conditionalExec();

