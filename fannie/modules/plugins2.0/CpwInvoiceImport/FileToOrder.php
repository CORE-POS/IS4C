<?php

namespace COREPOS\Fannie\Plugin\CpwInvoiceImport;
use COREPOS\Fannie\API\data\FileData;

class FileToOrder
{
    public function read($filename)
    {
        $arr = FileData::fileToArray($filename);
        if (!is_array($arr)) {
            throw new \Exception('Invalid file');
        }
        $inv = $this->getHeaderField($arr, 'Invoice');
        $oDate = $this->getHeaderField($arr, 'Order Date');
        $sDate = $this->getHeaderField($arr, 'Ship Date');
        $addr = $this->getShippingAddress($arr);

        $items = $this->getItemLines($arr);
        if (count($items) == 0) {
            throw new \Exception('Order is empty');
        }
        $fixed = array();
        foreach ($items as $i) {
            $fixed[] = $this->normalizeItemLine($i);
        }

        return array(
            'invoiceNo' => $inv,
            'orderDate' => FileData::excelFloatToDate($oDate),
            'shipDate' => FileData::excelFloatToDate($sDate),
            'shipTo' => $addr,
            'items' => $fixed,
        );
    }

    private function getShippingAddress($arr)
    {
        $ret = array();
        for ($i=0; $i<count($arr); $i++) {
            if (stristr($arr[$i][0], 'Ship To')) {
                $i++;
                while ($arr[$i][1] !== null) {
                    $ret[] = $arr[$i][1];
                    $i++;
                }
                break;
            }
        }

        return $ret;
    }

    private function getHeaderField($arr, $name)
    {
        foreach ($arr as $line) {
            if (stristr($line[4], $name)) {
                return $line[6];
            }
        }

        return false;
    }

    private function getItemLines($arr)
    {
        return array_filter($arr, function($i) {
            return (is_numeric($i[7]) && (is_numeric($i[5]) || stristr($i[2], 'charge')));
        });
    }

    private function normalizeItemLine($item)
    {
        return array(
            'description' => $item[2],
            'orderedQty' => $item[0] === null ? 0 : $item[0],
            'shippedQty' => $item[1] === null ? 0 : $item[1],
            'sku' => $item[5] === null ? $item[2] : $item[5],
            'casePrice' => $item[6] === null ? $item[7] : $item[6],
            'total' => $item[7],
        );
    }

}

