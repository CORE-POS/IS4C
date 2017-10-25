<?php

include('../../../../config.php');
include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');

class PhotogenicBasics extends FannieRESTfulPage
{
    protected $header = 'Basics With Pictures';
    protected $title = 'Basics With Pictures';
    public $discoverable = false;

    protected function get_view()
    {
        $query = "
            SELECT p.upc,
                SUM(CASE WHEN store_id=1 AND inUse=1 THEN 1 ELSE 0 END) AS hillside,
                SUM(CASE WHEN store_id=2 AND inUse=1 THEN 1 ELSE 0 END) AS denfeld,
                CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END AS brand,
                CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END AS description,
                t.description AS ruleType,
                m.super_name,
                p.department,
                u.photo
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                INNER JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID
                INNER JOIN PriceRuleTypes AS t ON r.priceRuleTypeID=t.priceRuleTypeID
            WHERE t.priceRuleTypeID IN (6,8)
            GROUP BY p.upc,
                brand,
                description,
                ruleType"; 
        $res = $this->connection->query($query);
        $ret = '';
        while ($row = $this->connection->fetchRow($res)) {
            if (!$row['hillside'] && !$row['denfeld']) continue;
            $ret .= '<div class="row"><div class="col-sm-5">';
            $ret .= '<table class="table small">';
            $ret .= '<tr><th>UPC</th><td><a href="../../../../item/ItemEditorPage.php?searchupc=' . $row['upc'] . '">'
                . $row['upc'] . '</a></td></tr>';
            $ret .= '<tr><th>Item</th><td>' . $row['brand'] . ' ' . $row['description'] . '</td></tr>';
            $ret .= '<tr><th>Category</th><td>' . $row['super_name'] . '</td></tr>';
            $ret .= '<tr><th>Type</th><td>' . $row['ruleType'] . '</td></tr>';
            $ret .= '<tr><th>Both Stores</th><td>' . ($row['hillside'] && $row['denfeld'] ? 'Yes' : 'No') . '</td></tr>';
            $ret .= '</table>';
            $ret .= '</div><div class="col-sm-5">';
            if ($row['photo']) {
                $ret .= '<a href="../../../../item/images/done/' . $row['photo'] . '">'
                    . '<img width="200px" src="../../../../item/images/done/' . $row['photo'] . '" /></a>';
            } else {
                $ret .= 'n/a';
            }
            $ret .= '</div></div>';
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();

