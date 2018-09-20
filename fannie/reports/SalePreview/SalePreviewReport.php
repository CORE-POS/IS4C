<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class SalePreviewReport extends FannieReportPage
{
    protected $title = "Fannie : Sale Preview";
    protected $header = "Sale Preview";

    protected $required_fields = array('date', 'super', 'store');
    public $description = '[Sale Preview] lists the number of items that will be on sale in each department on a given date';
    protected $new_tablesorter = true;
    protected $report_headers = array('Dept#', 'Department', '# of Items on Sale');

    public function report_description_content()
    {
        $imageFile = __DIR__ . '/../../item/images/done/floor' . $this->form->store . '.png';
        if (!file_exists($imageFile)) {
            return array();
        }

        $query = "SELECT l.upc, f.floorSectionID
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
                INNER JOIN StoreBatchMap AS m ON m.batchID=l.batchID
                INNER JOIN FloorSectionProductMap AS f ON l.upc=f.upc
                INNER JOIN FloorSections AS s ON f.floorSectionID=s.floorSectionID
            WHERE b.discountType > 0
                AND m.storeID=?
                AND s.storeID=?
                AND ? BETWEEN b.startDate AND b.endDate
            GROUP BY l.upc";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($this->form->store, $this->form->store, $this->form->date));
        $counts = array();
        while ($row = $this->connection->fetchRow($res)) {
            $key = $row['floorSectionID'];
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
            }
            $counts[$key]++;
        }

        $imageUrl = $this->config->get('URL') . 'item/images/done/floor' . $this->form->store . '.png';
        $ret = '<div style="padding: 2em;">
            <div style="position: relative;">
                <img src="' . $imageUrl . '" style="border: solid 1px black;" />';
        $model = new FloorSectionsModel($this->connection);
        $model->storeID($this->form->store);
        foreach ($model->find() as $section) {
            $ret .= sprintf('<div class="floorlabel"
                style="position: absolute; top: %dpx; left: %dpx;
                transform: rotate(%ddeg); transform-origin: left top 0;">%d (%s)</div>',
                $section->mapY(),
                $section->mapX(),
                $section->mapRotate(),
                (isset($counts[$section->floorSectionID()]) ? $counts[$section->floorSectionID()] : 0),
                $section->name()
            );
        }
        $ret .= '</div></div>';

        return array($ret);
    }

    public function fetch_report_data()
    {
        $query = "SELECT l.upc
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
                INNER JOIN StoreBatchMap AS m ON m.batchID=l.batchID
            WHERE b.discountType > 0
                AND m.storeID=?
                AND ? BETWEEN b.startDate AND b.endDate
            GROUP BY l.upc";
        $prep = $this->connection->prepare($query);
        $upcs = $this->connection->getAllValues($prep, array($this->form->store, $this->form->date));

        list($inStr, $args) = $this->connection->safeInClause($upcs);
        $query = "SELECT d.dept_no,
                        d.dept_name,
                        SUM(CASE WHEN p.upc IN ({$inStr}) THEN 1 ELSE 0 END) AS items
                    FROM products AS p
                        INNER JOIN departments AS d ON p.department=d.dept_no
                        INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                    WHERE 1=1
                        AND p.store_id=?
                        AND m.superID=?
                    GROUP BY d.dept_no, d.dept_name"; 
        $prep = $this->connection->prepare($query);
        $args[] = $this->form->store;
        $args[] = $this->form->super;
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $record = array(
                $row['dept_no'],
                $row['dept_name'],
                $row['items'],
            );
            if ($row['items'] == 0) {
                $record['meta'] = FannieReportPage::META_COLOR;
                $record['meta_background'] = '#f2dede';
            } elseif ($row['items'] < 5) {
                $record['meta'] = FannieReportPage::META_COLOR;
                $record['meta_background'] = '#ffeeba';
            }
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $model = new MasterSuperDeptsModel($this->connection);
        $opts = $model->toOptions();
        $stores = FormLib::storePicker();

        return <<<HTML
<form>
    <div class="form-group">
        <label>Super Department</label>
        <select name="super" class="form-control" required>
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="text" name="date" class="form-control date-field" />
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

