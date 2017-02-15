<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class ProdUpdateMaintenanceTask extends FannieTask
{

    public $name = 'Product Changelog Maintenance';

    public $description = 'Scans product update history for
changes to department and/or price and files those changes
separately for quick reference.

Deprecated the old CompressProdUpdate jobs. Do not schedule both
this and the older jobs - especially CompressProdUpdate/archive.php.';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private function isNewMethod($dbc)
    {
        $prodUpdate = $dbc->tableDefinition('prodUpdate');
        $priceHistory = $dbc->tableDefinition('prodPriceHistory');
        $deptHistory = $dbc->tableDefinition('prodDepartmentHistory');
        if (isset($prodUpdate['prodUpdateID']) && isset($priceHistory['prodUpdateID']) && isset($deptHistory['prodUpdateID'])) {
            return true;
        } else {
            return false;
        }
    }

    public function run()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $new_method = $this->isNewMethod($dbc);
        if ($new_method) {
            // schema is updated
            $this->cronMsg('New archiving method for prodUpdate', FannieLogger::INFO);

            // migrate data if needed
            // and rebuild the history tables from scratch
            // so they have correct prodUpdateID values
            if ($dbc->table_exists('prodUpdateArchive')) {
                $chk = $dbc->query($dbc->addSelectLimit('SELECT upc FROM prodUpdateArchive', 1));
                if ($dbc->num_rows($chk) > 0) {
                    $this->cronMsg('Need to migrate prodUpdateArchive data', FannieLogger::INFO);
                    if ($this->migrateArchive($dbc)) {
                        $dbc->query('TRUNCATE TABLE prodPriceHistory');
                        $dbc->query('TRUNCATE TABLE prodDepartmentHistory');
                    }
                }
            }

            /**
              New method:
              1. Lookup the last prodUpdate record ID added to the price history
              2. Scan newer prodUpdate records for price changes
              3. Repeat for department history/changes
              4. Do not use prodUpdateArchive
            */
            $limit = $this->lastUpdateID($dbc, 'prodPriceHistory');
            $this->cronMsg('Scanning price changes from prodUpdateID '.$limit, FannieLogger::INFO);
            $this->scanPriceChanges($dbc, $limit);

            if ($dbc->tableExists('ProdCostHistory')) {
                $limit = $this->lastUpdateID($dbc, 'ProdCostHistory');
                $this->cronMsg('Scanning cost changes from prodUpdateID '.$limit, FannieLogger::INFO);
                $this->scanCostChanges($dbc, $limit);
            }

            $limit = $this->lastUpdateID($dbc, 'prodDepartmentHistory');
            $this->cronMsg('Scanning dept changes from prodUpdateID '.$limit, FannieLogger::INFO);
            $this->scanDeptChanges($dbc, $limit);
        } else {
            $this->cronMsg('Old prodUpdate archiving is no longer supported. Apply schema updates to prodUpdate table.',
                FannieLogger::WARNING);
        }
    }

    private function lastUpdateID($dbc, $table)
    {
        $limitP = $dbc->prepare('SELECT MAX(prodUpdateID) AS lastChange FROM ' . $dbc->identifierEscape($table));
        $limit = $dbc->getValue($limitP);

        return $limit ? $limit : 0;
    }

    private function changesSince($dbc, $offset=0)
    {
        $prodUpdateQ = 'SELECT prodUpdateID FROM prodUpdate ';
        $args = array();
        if ($offset > 0) {
            $prodUpdateQ .= ' WHERE prodUpdateID > ? ';
            $args[] = $offset;
        }
        $prodUpdateQ .= ' ORDER BY upc, modified';
        $prodUpdateP = $dbc->prepare($prodUpdateQ);
        return $dbc->execute($prodUpdateP, $args);
    }

    /**
      Scan prodUpdate from price changes and log them
      in prodPriceHistory
      @param $dbc [SQLManager] db connection
      @param $offset [optional int] start scanning from this prodUpdateID
    */
    private function scanPriceChanges($dbc, $offset=0)
    {
        $prodUpdateR = $this->changesSince($dbc, $offset);
       
        $chkP = $dbc->prepare("SELECT modified,price FROM
            prodPriceHistory WHERE upc=?
            ORDER BY modified DESC");
        $upc = null;
        $prevPrice = null;
        $update = new ProdUpdateModel($dbc); 
        $history = new ProdPriceHistoryModel($dbc);

        /**
          Go through changes to each UPC in order
          When encountering a new UPC, lookup previous price
          (if any) from prodPriceHistory
          Only create new entries when the prodUpdate record's price
          does not match the previous price.
        */
        while ($prodUpdateW = $dbc->fetch_row($prodUpdateR)) {
            $update->reset();
            $update->prodUpdateID($prodUpdateW['prodUpdateID']);
            $update->load();

            if ($upc === null || $upc != $update->upc()) {
                $upc = $update->upc();
                $prevPrice = null;
                $chkR = $dbc->execute($chkP, array($upc));
                if ($dbc->num_rows($chkR) > 0) {
                    $chkW = $dbc->fetch_row($chkR);
                    $prevPrice = $chkW['price'];
                }
            }
            
            if ($prevPrice != $update->price()) {
                $history->reset();
                $history->upc($upc);
                $history->storeID($update->storeID());
                $history->modified($update->modified());
                $history->price($update->price());
                $history->uid($update->user());
                $history->prodUpdateID($update->prodUpdateID());
                $history->save();
                $this->cronMsg('Add price change #' . $update->prodUpdateID(), FannieLogger::INFO);
            }

            $prevPrice = $update->price();

            if ($this->test_mode) {
                break;
            }
        }
    }

    /**
      Scan prodUpdate from cost changes and log them
      in ProdCostHistory
      @param $dbc [SQLManager] db connection
      @param $offset [optional int] start scanning from this prodUpdateID
    */
    private function scanCostChanges($dbc, $offset=0)
    {
        $prodUpdateR = $this->changesSince($dbc, $offset);
       
        $chkP = $dbc->prepare("
            SELECT modified,
                cost 
            FROM ProdCostHistory 
            WHERE upc=?
            ORDER BY modified DESC");
        $upc = null;
        $prevPrice = null;
        $update = new ProdUpdateModel($dbc); 
        $history = new ProdCostHistoryModel($dbc);

        /**
          Go through changes to each UPC in order
          When encountering a new UPC, lookup previous price
          (if any) from prodPriceHistory
          Only create new entries when the prodUpdate record's price
          does not match the previous price.
        */
        while ($prodUpdateW = $dbc->fetchRow($prodUpdateR)) {
            $update->prodUpdateID($prodUpdateW['prodUpdateID']);
            if (!$update->load()) {
                continue;
            }

            if ($upc === null || $upc != $update->upc()) {
                $upc = $update->upc();
                $prevPrice = null;
                $chkR = $dbc->execute($chkP, array($upc));
                if ($dbc->numRows($chkR) > 0) {
                    $chkW = $dbc->fetch_row($chkR);
                    $prevPrice = $chkW['cost'];
                }
            }
            
            if ($prevPrice != $update->cost()) {
                $history->reset();
                $history->upc($upc);
                $history->storeID($update->storeID());
                $history->modified($update->modified());
                $history->cost($update->cost());
                $history->uid($update->user());
                $history->prodUpdateID($update->prodUpdateID());
                $history->save();
                $this->cronMsg('Add cost change #' . $update->prodUpdateID(), FannieLogger::INFO);
            }

            $prevPrice = $update->cost();

            if ($this->test_mode) {
                break;
            }
        }
    }


    /**
      Scan prodUpdate from dept changes and log them
      in prodDepartmentHistory
      @param $dbc [SQLManager] db connection
      @param $offset [optional int] start scanning from this prodUpdateID
    */
    private function scanDeptChanges($dbc, $offset=0)
    {
        $prodUpdateR = $this->changesSince($dbc, $offset);
       
        $chkP = $dbc->prepare("SELECT modified,dept_ID FROM
            prodDepartmentHistory WHERE upc=?
            ORDER BY modified DESC");
        $upc = null;
        $prevDept = null;
        $update = new ProdUpdateModel($dbc); 
        $history = new ProdDepartmentHistoryModel($dbc);

        /**
            See scanPriceChanges()
        */
        while($prodUpdateW = $dbc->fetch_row($prodUpdateR)) {
            $update->reset();
            $update->prodUpdateID($prodUpdateW['prodUpdateID']);
            $update->load();

            if ($upc === null || $upc != $update->upc()) {
                $upc = $update->upc();
                $prevDept = null;
                $chkR = $dbc->execute($chkP, array($upc));
                if ($dbc->num_rows($chkR) > 0) {
                    $chkW = $dbc->fetch_row($chkR);
                    $prevDept = $chkW['dept_ID'];
                }
            }
            
            if ($prevDept != $update->dept()) {
                $history->reset();
                $history->upc($upc);
                $history->storeID($update->storeID());
                $history->modified($update->modified());
                $history->dept_ID($update->dept());
                $history->uid($update->user());
                $history->prodUpdateID($update->prodUpdateID());
                $this->cronMsg('Add dept change #' . $update->prodUpdateID(), FannieLogger::INFO);
                $history->save();
            }

            $prevDept = $update->dept();

            if ($this->test_mode) {
                break;
            }
        }
    }

    /**
      One-time task moving to newer methodology
      Records in prodUpdateArchive must be copied *back* into
      prodUpdate. If successful, prodUpdateArchive is no
      longer needed.
    */
    private function migrateArchive($dbc)
    {
        $matching = $dbc->matchingColumns('prodUpdate', 'prodUpdateArchive');
        $col_list = '';
        foreach($matching as $column) {
            if ($column == 'prodUpdateID') {
                continue;
            }
            $col_list .= $dbc->identifierEscape($column) . ',';
        }
        $col_list = substr($col_list, 0, strlen($col_list)-1);

        $worked = $dbc->query("INSERT INTO prodUpdate ($col_list) SELECT $col_list FROM prodUpdateArchive");
        if ($worked){
            $this->cronMsg("Migrated prodUpdateArchive back into prodUpdate successfully", FannieLogger::INFO);
            $dbc->query("TRUNCATE TABLE prodUpdateArchive");
            return true;
        } else {
            $this->cronMsg("There was an archiving error on prodUpdate", FannieLogger::ERROR);
            return false;
        }
    }
}

