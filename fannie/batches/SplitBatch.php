<?php

include(dirname(__FILE__). '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class SplitBatch extends FannieRESTfulPage
{
    protected $header = 'Split Batch';
    protected $title = 'Split Batch';

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    protected function post_id_handler()
    {
        $sourceID = $this->id;
        $source = new BatchesModel($this->connection);
        $source->batchID($sourceID);
        $source->load();

        // copy source batch
        $target = new BatchesModel($this->connection);
        $target->startDate($source->startDate());
        $target->endDate($source->endDate());
        $target->batchName($source->batchName() . ' SPLIT');
        $target->batchType($source->batchType());
        $target->discountType($source->discountType());
        $target->priority($source->priority());
        $target->owner($source->owner());
        $target->transLimit($source->transLimit());
        $target->notes($source->notes());
        $targetID = $target->save();

        // copy store mapping
        $storeP = $this->connection->prepare('SELECT storeID FROM StoreBatchMap WHERE batchID=?');
        $addP = $this->connection->prepare('INSERT INTO StoreBatchMap (storeID, batchID) VALUES (?, ?)');
        $storeR = $this->connection->execute($storeP, array($sourceID));
        while ($row = $this->connection->fetchRow($storeR)) {
            $this->connection->execute($addP, array($row['storeID'], $targetID));
        }

        // move items to target batch
        $split = FormLib::get('split');
        $keep = FormLib::get('keep');
        $moveP = $this->connection->prepare('UPDATE batchList SET batchID=?
            WHERE batchID=? AND upc=?');
        foreach ($split as $upc) {
            if (!in_array($upc, $keep)) {
                $this->connection->execute($moveP, array($targetID, $sourceID, $upc));
            }
        }

        return 'newbatch/EditBatchPage.php?id=' . $targetID;
    }

    protected function get_id_view()
    {
        $idP = $this->connection->prepare('SELECT b.upc, p.department, p.brand, p.description
                FROM batchList AS b
                    LEFT JOIN products AS p ON b.upc=p.upc AND p.store_id=?
                WHERE b.batchID=?');
        $items = $this->connection->getAllRows($idP, array($this->config->get('STORE_ID'), $this->id));
        $groups = array();
        foreach ($items as $item) {
            $groupKey = substr($item['upc'], 0, 8) . '-' . $item['department'];
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = array();
            }
            $groups[$groupKey][] = $item;
        }

        $alreadyHas = function($items, $dept) {
            foreach ($items as $i) {
                if ($i['department'] == $dept) {
                    return true;
                }
            }
            return false; 
        };

        sort($groups);
        $ascendA = array();
        $ascendB = array();
        $flip = 0;
        for ($i=0; $i<count($groups); $i++) {
            if ($alreadyHas($ascendA, $groups[$i][0]['department']) && !$alreadyHas($ascendB, $groups[$i][0]['department'])) {
                $flip = true;
            } else {
                $flip = false;
            }
            if (!$flip && $i % 2) {
                foreach ($groups[$i] as $item) {
                    $ascendA[] = $item;
                }
            } else {
                foreach ($groups[$i] as $item) {
                    $ascendB[] = $item;
                }
            }
        }

        $finalA = $ascendA;
        $finalB = $ascendB;

        $ret = '<div class="row">
            <form method="post" action="SplitBatch.php">
            <div class="col-sm-6">
            <h3>Keep in Batch (' . count($finalA) . ')</h3>
            <table class="table table-bordered table-striped">
            <tr><th>UPC</th><th>Brand</th><th>Description</th><th>Also Split</th></tr>';
        foreach ($finalA as $item) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td>
                <td><input type="checkbox" name="split[]" value="%s" /></tr>',
                $item['upc'], $item['brand'], $item['description'], $item['upc']);
        }
        $ret .= '</table>
            </div>
            <div class="col-sm-6">
            <h3>Split to New Batch (' . count($finalB) . ')</h3>
            <table class="table table-bordered table-striped">
            <tr><th>UPC</th><th>Brand</th><th>Description</th><th>Don\'t Split</th></tr>';
        foreach ($finalB as $item) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td>
                <td><input type="checkbox" name="keep[]" value="%s" />
                <input type="hidden" name="split[]" value="%s" /></tr>',
                $item['upc'], $item['brand'], $item['description'], $item['upc'], $item['upc']);
        }
        $ret .= '</table>
            </div>
            <div class="col-sm-5">
                <input type="hidden" name="id" value="' . $this->id . '" />
                <p><button type="submit" class="btn btn-default btn-core">Split Batch</button></p>
            </div>
            </form>
            </div>';

        return $ret;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="get" action="SplitBatch.php">
    <div class="form-group">
        <label>Batch ID</label>
        <input type="number" class="form-control" name="id" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Continue</button>
    </div>
</form>
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
<p>
This tool splits an existing batch into two roughly similar sized
batches. When splitting it does try to keep product lines together.
</p>
<p>
When the preliminary results are shown you can use the checkboxes
to override the tools choice on either list. Checking an item in
the "keep" list will cause that item to be split to the new batch.
Checking an item in the "split" list will cause that item to
remain in the original batch.
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

