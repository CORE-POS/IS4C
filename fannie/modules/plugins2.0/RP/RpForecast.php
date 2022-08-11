<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpForecast extends FannieRESTfulPage
{
    protected $header = 'Order Forecast';
    protected $title = 'Order Forecast';
    protected $must_authenticate = true;

    protected function get_id_view()
    {
        $parP = $this->connection->prepare("
            SELECT SUM(movement)
            FROM " . FannieDB::fqn('Smoothed', 'plugin:WarehouseDatabase') . "
            WHERE 1=1
                " . ($this->id > 0 ? ' AND storeID=? ' : '') . "
                AND upc=?"); 

        $priceP = $this->connection->prepare("
            SELECT CASE WHEN p.special_price > 0 THEN p.special_price ELSE p.normal_price END AS price
            FROM upcLike AS u
                INNER JOIN products AS p ON p.upc=u.upc
            WHERE u.likeCode=?");

        $prep = $this->connection->prepare("
            SELECT r.upc,
                r.categoryID,
                c.name,
                v.vendorName AS vendorName,
                b.vendorName AS backupVendor,
                r.vendorSKU,
                r.vendorItem,
                r.backupSKU,
                r.backupItem,
                r.caseSize,
                r.vendorID,
                r.backupID,
                r.cost
            FROM RpOrderItems AS r
                LEFT JOIN RpOrderCategories AS c ON r.categoryID=c.rpOrderCategoryID
                LEFT JOIN vendors AS v ON r.vendorID=v.vendorID
                LEFT JOIN vendors AS b ON r.backupID=b.vendorID
            WHERE 
                r.deleted=0
                " . ($this->id > 0 ? ' AND r.storeID=? ' : '') . "
            ORDER BY r.vendorID, c.seq, c.name, r.vendorItem");
        $args = $this->id > 0 ? array($this->id) : array();
        $res = $this->connection->execute($prep, $args);
        $vendorID = false;
        $dailySales = 0;
        $seen = array();
        $ret = '';
        while ($row = $this->connection->fetchRow($res)) {
            if (isset($seen[$row['upc']])) {
                continue;
            }
            $seen[$row['upc']] = true;
            if ($row['vendorID'] != $vendorID) {
                if ($vendorID !== false) {
                    $ret .= '</table>';
                }
                $ret .= '<h3>' . $row['vendorName'] . '</h3>';
                $vendorID = $row['vendorID'];
                $ret .= '<table class="table">';

            }
            $lc = str_replace('LC', '', $row['upc']);
            $parArgs = $this->id > 0 ? array($this->id, $row['upc']) : array($row['upc']);
            $par = $this->connection->getValue($parP, $parArgs);
            if ($row['caseSize'] != 0 && ($par / $row['caseSize']) < 0.1) {
                $par = 0.1 * $row['caseSize'];
            }
            $price = $this->connection->getValue($priceP, array(substr($row['upc'], 2)));
            $ret .= sprintf('<tr><td class="par-cell">%.2f</td><td>%s</td></tr>',
                $par / $row['caseSize'], $row['vendorItem']);

            $dailySales += ($par * $price);
        }
        $ret .= '</table>';

        $ret .= '<p>Daily Sales: ' . $dailySales . '</p>';

        $dt1 = new DateTime(FormLib::get('date1'));
        $dt2 = new DateTime(FormLib::get('date2'));
        $diff = $dt1->diff($dt2, true);
        $days = $diff->days + 1;

        $ret .= '<p>Period Sales: ' . $dailySales * $days . '</p>';

        $segP = $this->connection->prepare("SELECT SUM(sales)
            FROM RpSegments
            WHERE storeID=?
                AND startDate BETWEEN ? AND ?");
        $segSales = $this->connection->getValue($segP, array(
            $this->id,
            $dt1->format('Y-m-d'),
            $dt2->format('Y-m-d'),
        ));
        $ret .= '<p>Projected Sales: ' . $segSales . '</p>';

        if ($segSales) {
            $adj = $segSales / ($dailySales * $days);
            $ret .= sprintf('<p>Adjustment factor: %.2f%%</p>', $adj * 100);
        }

        $this->addOnloadCommand("scalePars({$days});");
        if (isset($adj) && $adj) {
            $this->addOnloadCommand("scalePars({$adj});");
        }
        $this->addOnloadCommand('roundAll();');

        return $ret;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker('id');
        $dates = FormLib::standardDateFields();
        $this->addOnloadCommand("\$('select[name=id]').val(0);");

        return <<<HTML
<form method="get" action="RpForecast.php">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default">Get Forecast</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }

    protected function javascriptContent()
    {
        return <<<SCRIPT
function scalePars(factor) {
    $('td.par-cell').each(function () {
        var cur = $(this).html() * 1;
        cur = Math.round(cur * factor * 100) / 100;
        $(this).html(cur);
    });
}
function roundAll() {
    $('td.par-cell').each(function () {
        var cur = $(this).html() * 1;
        cur = Math.round(cur);
        $(this).html(cur);
    });
}
SCRIPT;
    }
}

FannieDispatch::conditionalExec();

