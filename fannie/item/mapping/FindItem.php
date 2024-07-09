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
        $aka = '';
        $img = '';
        $itemName = 'Unknown item?';
        if (is_array($info)) {
            $itemName = $info['brand'] . ' ' . $info['description'];
            if ($info['uDesc']) {
                $aka = $info['uBrand'] . ' ' . $info['uDesc'];
            }
            if ($info['photo']) {
                $img = sprintf('<img width="500" src="../images/done/%s" />', $info['photo']);
            }
        }
        $stores = new StoresModel($this->connection);
        $stores->hasOwnItems(1);
        $itemP = $this->connection->prepare('SELECT last_sold, numflag FROM products WHERE upc=? AND store_id=?');
        $storeSection = '<div class="row">';
        $floorP = $this->connection->prepare('
            SELECT f.floorSectionID, f.name, s.subSection
            FROM FloorSectionProductMap AS m
                INNER JOIN FloorSections AS f ON m.floorSectionID=f.floorSectionID
                LEFT JOIN FloorSubSections AS s ON m.floorSectionID=s.floorSectionID AND m.upc=s.upc
            WHERE m.upc=? AND f.storeID=?');
        foreach ($stores->find('description') as $s) {
            $storeSection .= '<div class="col-sm-3">';
            $storeSection .= '<strong>' . $s->description() . '</strong><br />';
            $item = $this->connection->getRow($itemP, array($upc, $s->storeID()));
            $lastSold = $item['last_sold'];
            $days = 30;
            if ($lastSold) {
                $today = new DateTime();
                list($lastSold,) = explode(' ', $lastSold);
                $then = new DateTime($lastSold);
                $days = $today->diff($then)->days;
                $storeSection .= '<div>Last sold: ' . $days . ' ' . ($days != 1 ? 'days' : 'day') . ' ago</div>';
            } else {
                $storeSection .= 'Last sold: never<br />';
                $storeSection .= '<em>This item is either new or<br /> has not been stocked at this location</em><br />';
            }
            if ($item['numflag'] & (1 << 18) || $days > 29) {
                $storeSection .= '<em>This item may be out of stock</em><br />';
            }
            if ($item['numflag'] & (1 << 19)) {
                $storeSection .= '<em>This item has been discontinued</em><br />';
            }
            $locations = $this->connection->getAllRows($floorP, array($upc, $s->storeID()));
            $storeSection .= '<br />Location(s):<br />';
            if (count($locations) == 0) {
                $storeSection .= 'Unknown<br />';
            }  else {
                foreach ($locations as $l) {
                    if ($l['subSection']) {
                        $l['name'] .= ' (' . $l['subSection'] . ')';
                    }
                    $storeSection .= sprintf('<a href="%s?id=%d&section=%s&subSection=%s">%s
                            <span class="fas fa-info-circle"></span></a><br />',
                        'NewStoreFloorsPage.php',
                        $s->storeID(),
                        ($l['subSection']) ? substr($l['name'], 0, strpos($l['name'], '(') - 1) : $l['name'],
                        isset($l['subSection']) ? $l['subSection'] : '',
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
<hr />
<p>
    {$img}
</p>
HTML;
    }

}

FannieDispatch::conditionalExec();

