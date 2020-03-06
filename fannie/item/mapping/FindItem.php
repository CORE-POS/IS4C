<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class FindItem extends FannieRESTfulPage
{
    public $discoverable = false;
    protected $header = 'Find Item';
    protected $title = 'Find Item';

    protected function get_id_view()
    {
        $upc = BarcodeLib::padUPC($this->id);
        $infoP = $this->connection->prepare('SELECT p.brand, p.description,
            u.brand AS uBrand, u.description AS uDesc, u.photo
            FROM products AS p
                INNER JOIN productUser AS u ON p.upc=u.upc
            WHERE p.upc=?');
        $info = $this->connection->getRow($infoP, array($upc));
        $itemName = $info['brand'] . ' ' . $info['description'];
        $aka = '';
        if ($info['uDesc']) {
            $aka = $info['uBrand'] . ' ' . $info['uDesc'];
        }
        $img = '';
        if ($info['photo']) {
            $img = sprintf('<img width="500" src="../images/done/%s" />', $info['photo']);
        }
        $stores = new StoresModel($this->connection);
        $stores->hasOwnItems(1);
        $itemP = $this->connection->prepare('SELECT last_sold FROM products WHERE upc=? AND store_id=?');
        $storeSection = '<div class="row">';
        $floorP = $this->connection->prepare('
            SELECT f.floorSectionID, f.name
            FROM FloorSectionProductMap AS m
                INNER JOIN FloorSections AS f ON m.floorSectionID=f.floorSectionID
            WHERE m.upc=? AND f.storeID=?');
        foreach ($stores->find('description') as $s) {
            $storeSection .= '<div class="col-sm-3">';
            $storeSection .= '<strong>' . $s->description() . '</strong><br />';
            $lastSold = $this->connection->getValue($itemP, array($upc, $s->storeID()));
            if ($lastSold) {
                $today = new DateTime();
                list($lastSold,) = explode(' ', $lastSold);
                $then = new DateTime($lastSold);
                $days = $today->diff($then)->days;
                $storeSection .= 'Last sold: ' . $days . ' ' . ($days != 1 ? 'days' : 'day') . ' ago<br />';
            } else {
                $storeSection .= 'Last sold: never<br />';
            }
            $locations = $this->connection->getAllRows($floorP, array($upc, $s->storeID()));
            $storeSection .= 'Location(s):<br />';
            if (count($locations) == 0) {
                $storeSection .= 'Unknown<br />';
            }  else {
                foreach ($locations as $l) {
                    $storeSection .= sprintf('<a href="StoreFloorsPage.php?id=%d&section=%d">%s</a><br />',
                        $s->storeID(),
                        $l['floorSectionID'],
                        $l['name']);
                }
            }
            $storeSection .= '</div>';
        }
        $storeSection .= '</div>';

        return <<<HTML
<p style="font-size: 125%;">
    {$itemName}<br />
    {$aka}<br />
</p>
{$storeSection}
<p>
    {$img}
</p>
HTML;
    }

}

FannieDispatch::conditionalExec();

