<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class EndCapUpload extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $header = 'End Cap Upload';
    protected $title = 'End Cap Upload';

    private $results = '';
    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC',
            'default' => 0,
            'required' => true,
        ),
        'brand' => array(
            'name' => 'brand',
            'display_name' => 'Brand',
            'default' => 2,
            'required' => false,
        ),
        'item' => array(
            'name' => 'item',
            'display_name' => 'Description',
            'default' => 3,
            'required' => true,
        ),
        'ecSet' => array(
            'name' => 'ecSet',
            'display_name' => 'End Cap',
            'default' => 15,
            'required' => true,
        ),
    );

    public function preview_content()
    {
        return <<<HTML
<div class="form-inline">
    <div class="form-group">
        <label>Naming Prefix</label>
        <input type="text" name="prefix" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Start Date</label>
        <input type="text" name="start" class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>End Date</label>
        <input type="text" name="end" class="form-control date-field" required />
    </div>
</div>
HTML;
    }

    function process_file($linedata, $indexes)
    {
        $prefix = FormLib::get('prefix');
        $start = FormLib::get('start');
        $end = FormLib::get('end');
        $json = array();
        $current = false;
        $shelfNum = 0;
        $count = 0;
        $maxP = $this->connection->prepare("SELECT MAX(endCapID) FROM EndCaps");
        $insP = $this->connection->prepare("INSERT INTO EndCaps (endCapID, json) VALUES (?, ?)");
        foreach ($linedata as $data) {
            $set = trim($data[$indexes['ecSet']]);
            $set = strpos($set, ' ') ? strtolower($set) : strtoupper($set);
            $upc = $data[$indexes['upc']];
            if (!is_numeric($upc)) {
                continue;
            }
            if ($set !== $current) {
                if ($current !== false && $current !== 'X' && $current !== '') {
                    $json['name'] = $prefix . ' ' . $current;
                    $json['startDate'] = $start;
                    $json['endDate'] = $end;
                    $json['pen'] = array();
                    $json['initID'] = false;
                    $json['permanentID'] = $this->connection->getValue($maxP) + 1;
                    $this->results .= '<p>' . json_encode($json) . '</p>';
                    $this->connection->execute($insP, array($json['permanentID'], json_encode($json)));
                }
                $current = $set;
                $json = array();
                $shelfNum = 0;
                $count = 0;
            }

            $item = $data[$indexes['item']];
            $uuid = Ramsey\Uuid\Uuid::uuid4();
            if (isset($indexes['brand']) && $data[$indexes['brand']]) {
                $item = $data[$indexes['brand']] . ' ' . $item;
            }
            if (!isset($json['shelves'])) {
                $json['shelves'] = array();
            }
            if (!isset($json['shelves'][$shelfNum])) {
                $json['shelves'][$shelfNum] = array();
            }
            $json['shelves'][$shelfNum][] = array(
                'id' => $uuid,
                'name' => $item,
                'upc' => $upc,
                'isLine' => false,
                'width' => 1,
            );
            $count++;
            if ($count >= 4) {
                $shelfNum++;
                $count = 0;
            }
        }

        return true;
    }

    public function results_content()
    {
        return $this->results;
    }
}

FannieDispatch::conditionalExec();

