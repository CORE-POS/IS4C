<?php

namespace COREPOS\pos\plugins\Paycards\card;
use COREPOS\pos\lib\Database;
use \PaycardConf;

/**
  @class EWicBalanceEntry
  
  Object representing an eWIC balance entry.
  An entry includes a category, optionally a 
  subcategory, and a quantity.
*/
class EWicBalanceEntry
{
    private $category = 0;
    private $subcategory = 0;
    private $qty = 0;
    private $conf;

    /**
      @contructor
      @param $cat [int] category
      @param $subcat [int] subcategory (zero means no subcategory)
      @param $qty [decimal]
    */
    public function __construct($cat, $subcat, $qty)
    {
        $this->category = $cat;
        $this->subcategory = $subcat;
        $this->qty = $qty;
        $this->conf = new PaycardConf();
    }

    /**
      Get eWIC eligible items in the transaction
      matching this balance entry
      @return [array] of items

      Each entry in the returned array is [UPC, Price, Quantity]
    */
    public function getItems()
    {
        if ($this->subcategory) {
            return $this->getBySub();
        }

        return $this->getByCat();
    }

    /**
      Find eligible items by eWIC subcategory
    */
    private function getBySub()
    {
        $dbc = Database::tDataConnect();
        $opDB = $this->conf->get('pDatabase'). $dbc->sep();
        $query = "SELECT i.upc, i.upcCheck, i.alias, s.qtyMethod
                SUM(l.unitPrice) AS up,
                SUM(l.total) AS ttl,
                SUM(l.quantity) AS qty
            FROM localtemprans AS l
                INNER JOIN {$opDB}EWicItems AS i ON l.upc=i.upc
                INNER JOIN {$opDB}EWicSubCategories AS s ON i.eWicSubCategoryID=s.eWicSubCategoryID
            WHERE i.eWicSubCategoryID = ?
            GROUP BY i.upc, i.upcCheck, i.alias, s.qtyMethod";
        $prep = $dbc->prepare($query); 
        $res = $dbc->execute($prep, array($this->subcategory));

        return $this->processResult($dbc, $res);
    }

    /**
      Find eligible items by eWIC category
    */
    private function getByCat()
    {
        $dbc = Database::tDataConnect();
        $opDB = $this->conf->get('pDatabase'). $dbc->sep();
        $query = "SELECT i.upc, i.upcCheck, i.alias, c.qtyMethod
                SUM(l.unitPrice) AS up,
                SUM(l.total) AS ttl,
                SUM(l.quantity) AS qty
            FROM localtemprans AS l
                INNER JOIN {$opDB}EWicItems AS i ON l.upc=i.upc
                INNER JOIN {$opDB}EWicCategories AS c ON i.eWicCategoryID=s.eWicCategoryID
            WHERE i.eWicCategoryID = ?
            GROUP BY i.upc, i.upcCheck, i.alias, c.qtyMethod";
        $prep = $dbc->prepare($query); 
        $res = $dbc->execute($prep, array($this->category));

        return $this->processResult($dbc, $res);
    }

    /**
      Transform query results to item list
      @param $dbc [SQLManager]
      @param $res [SQL result object]
    */
    private function processResult($dbc, $res)
    {
        $remaining = $this->qty;
        $items = array();
        while ($row = $dbc->fetchRow($res)) {
            $using = $row['qtyMethod'] == 0 ? $row['qty'] : $row['ttl'];
            if ($using <= $remaining) {
                $items[] = array(
                    $row['alias'] ? $row['alias'] : $row['upcCheck'],
                    $row['ttl'],
                    $row['qty'],
                );
                $remaining -= $using;
            } elseif ($using > $remaining && $remaining > 0) {
                $ratio = $reamining / $using;
                $items[] = array(
                    $row['alias'] ? $row['alias'] : $row['upcCheck'],
                    $row['ttl'] * $ratio,
                    $row['qty'] * $ratio,
                );
                $remaining = 0;
            }

            if ($remaining <= 0) {
                break;
            }
        }

        return $items;
    }
}

