<?php

use COREPOS\Fannie\API\FannieUploadPage;
use COREPOS\Fannie\API\item\ItemText;

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class MercatoDiff extends FannieUploadPage
{
    protected $header = 'Mercato Item Diff';
    protected $title = 'Mercato Item Diff';

    protected $preview_opts = array(
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU',
            'default' => 0,
            'required' => true,
        ),
    );

    private $results = array();

    public function process_file($linedata, $indexes)
    {
        $skus = array();
        foreach ($linedata as $data) {
            $sku = trim($data[$indexes['sku']]);
            if ($sku == '' || strtolower($sku) == 'sku') {
                continue;
            }
            $skus[] = $sku;
        }

        list($inStr, $args) = $this->connection->safeInClause($skus);
        $prep = $this->connection->prepare("
            SELECT m.upc,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . ",
                p.size,
                p.normal_price,
                s.super_name,
                d.dept_name,
                p.scale
            FROM MercatoItems AS m
            " . DTrans::joinProducts('m', 'p', 'INNER') . "
                LEFT JOIN productUser AS u ON m.upc=u.upc
                LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE m.upc NOT IN ({$inStr})
                AND m.altFlag = 0
            ORDER BY p.auto_par DESC
        ");
        $this->results = $this->connection->getAllRows($prep, $args);

        return true;
    }

    public function results_content()
    {
        $ret = '<p>Missing items: ' . count($this->results) . '</p>';
        $ret .= '<table class="table table-bordered">';
        foreach ($this->results as $r) {
            $ret .= sprintf('<tr %s>
                <td>%s <a href="../../../item/ItemEditorPage.php?searchupc=%s">edit</a></td>
                <td>%s %s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td>%s - %s</td>
                </tr>',
                $r['scale'] ? 'class="alert-info"' : '',
                $r['upc'], $r['upc'],
                $r['brand'], $r['description'],
                $r['size'],
                $r['normal_price'],
                $r['super_name'],
                $r['dept_name']
            );
        }
        $ret .= '</table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

