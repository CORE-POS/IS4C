<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeOriginReport extends FannieReportPage
{
    protected $header = 'Like Code Origins';
    protected $title = 'Like Code Origins';
    protected $report_headers = array('Like Code', 'Description', 'COOL', 'Changed', 'Vendor');
    protected $required_fields = array('submit');

    public function fetch_report_data()
    {
        $query = "
            SELECT m.likeCode,
                l.likeCodeDesc,
                COALESCE(s.coolText, '') AS coolText,
                COALESCE(s.tdate, 'n/a') AS tdate,
                n.vendorName
            FROM VendorLikeCodeMap AS m
                INNER JOIN likeCodes AS l ON m.likeCode=l.likeCode
                INNER JOIN vendors AS n ON m.vendorID=n.vendorID
                INNEr JOIN SkuCOOLHistory AS s ON m.sku=s.sku AND m.vendorID=s.vendorID
            WHERE s.ordinal=1";
        $args = array();
        if (FormLib::get('date')) {
            $query .= ' AND s.tdate >= ?';
            $args[] = FormLib::get('date');
        }
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['likeCode'],
                $row['likeCodeDesc'],
                $row['coolText'],
                $row['tdate'],
                $row['vendorName'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Changed since</label>
        <input type="text" class="form-control date-field" name="date"
            placeholder="Optional; leave blank for all origins" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default" name="submit" value="1">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

