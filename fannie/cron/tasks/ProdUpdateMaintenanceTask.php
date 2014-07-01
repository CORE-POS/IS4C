<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

    public function run()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $new_maintenance_method = false;
        $prodUpdate = $dbc->table_definition('prodUpdate');
        $priceHistory = $dbc->table_definition('prodPriceHistory');
        $deptHistory = $dbc->table_definition('prodDepartmentHistory');
        if (isset($prodUpdate['prodUpdateID']) && isset($priceHistory['prodUpdateID']) && isset($deptHistory['prodUpdateID'])) {
            // schema is updated
            $new_maintenance_method = true;
            echo $this->cronMsg('New archiving method for prodUpdate');

            // migrate data if needed
            // and rebuild the history tables from scratch
            // so they have correct prodUpdateID values
            if ($dbc->table_exists('prodUpdateArchive')) {
                $chk = $dbc->query($dbc->addSelectLimit('SELECT upc FROM prodUpdateArchive', 1));
                if ($dbc->num_rows($chk) > 0) {
                    echo $this->cronMsg('Need to migrate prodUpdateArchive data');
                    if ($this->migrateArchive($dbc)) {
                        $dbc->query('TRUNCATE TABLE prodPriceHistory');
                        $dbc->query('TRUNCATE TABLE prodDepartmentHistory');
                    }
                }
            }
        }

        if ($new_maintenance_method) {
            /**
              New method:
              1. Lookup the last prodUpdate record ID added to the price history
              2. Scan newer prodUpdate records for price changes
              3. Repeat for department history/changes
              4. Do not use prodUpdateArchive
            */
            $limitR = $dbc->query('SELECT MAX(prodUpdateID) as lastChange FROM prodPriceHistory');
            $limit = 0;
            if ($dbc->num_rows($limitR) > 0) {
                $limitW = $dbc->fetch_row($limitR);
                $limit = $limitW['lastChange'];
            }
            echo $this->cronMsg('Scanning price changes from prodUpdateID '.$limit);
            $this->scanPriceChanges($dbc, $limit);

            $limitR = $dbc->query('SELECT MAX(prodUpdateID) as lastChange FROM prodDepartmentHistory');
            $limit = 0;
            if ($dbc->num_rows($limitR) > 0) {
                $limitW = $dbc->fetch_row($limitR);
                $limit = $limitW['lastChange'];
            }
            echo $this->cronMsg('Scanning dept changes from prodUpdateID '.$limit);
            $this->scanDeptChanges($dbc, $limit);
        } else {
            /**
              old method:
              1. Scan prodUpdate for changes
              2. Log changes in history table
              3. Move prodUpdate records into prodUpdateArchive
            */
            $this->scanPriceChanges($dbc);
            $this->scanDeptChanges($dbc);

            $matching = $dbc->matchingColumns('prodUpdate', 'prodUpdateArchive');
            $col_list = '';
            foreach($matching as $column) {
                if ($column == 'prodUpdateID') {
                    continue;
                }
                $col_list .= $dbc->identifier_escape($column) . ',';
            }
            $col_list = substr($col_list, 0, strlen($col_list)-1);

            $worked = $dbc->query("INSERT INTO prodUpdateArchive ($col_list) SELECT $col_list FROM prodUpdate");
            if ($worked){
                $dbc->query("DELETE FROM prodUpdate");
            } else {
                echo $this->cronMsg("There was an archiving error on prodUpdate");
                flush();
            }
        }

    }

    /**
      Scan prodUpdate from price changes and log them
      in prodPriceHistory
      @param $dbc [SQLManager] db connection
      @param $offset [optional int] start scanning from this prodUpdateID
    */
    private function scanPriceChanges($dbc, $offset=0)
    {
        $prodUpdateQ = 'SELECT prodUpdateID FROM prodUpdate ';
        $args = array();
        if ($offset > 0) {
            $prodUpdateQ .= ' WHERE prodUpdateID > ? ';
            $args[] = $offset;
        }
        $prodUpdateQ .= ' ORDER BY upc, modified';
        $prodUpdateP = $dbc->prepare($prodUpdateQ);
        $prodUpdateR = $dbc->execute($prodUpdateP, $args);
       
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
        while($prodUpdateW = $dbc->fetch_row($prodUpdateR)) {
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
                $history->modified($update->modified());
                $history->price($update->price());
                $history->uid($update->user());
                $history->prodUpdateID($update->prodUpdateID());
                $history->save();
                echo $this->cronMsg('Add price change #' . $update->prodUpdateID());
            }

            $prevPrice = $update->price();
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
        $prodUpdateQ = 'SELECT prodUpdateID FROM prodUpdate ';
        $args = array();
        if ($offset > 0) {
            $prodUpdateQ .= ' WHERE prodUpdateID > ? ';
            $args[] = $offset;
        }
        $prodUpdateQ .= ' ORDER BY upc, modified';
        $prodUpdateP = $dbc->prepare($prodUpdateQ);
        $prodUpdateR = $dbc->execute($prodUpdateP, $args);
       
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
                $history->modified($update->modified());
                $history->dept_ID($update->dept());
                $history->uid($update->user());
                $history->prodUpdateID($update->prodUpdateID());
                echo $this->cronMsg('Add dept change #' . $update->prodUpdateID());
                $history->save();
            }

            $prevDept = $update->dept();
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
            $col_list .= $dbc->identifier_escape($column) . ',';
        }
        $col_list = substr($col_list, 0, strlen($col_list)-1);

        $worked = $dbc->query("INSERT INTO prodUpdate ($col_list) SELECT $col_list FROM prodUpdateArchive");
        if ($worked){
            echo $this->cronMsg("Migrated prodUpdateArchive back into prodUpdate successfully");
            $dbc->query("TRUNCATE TABLE prodUpdateArchive");
            return true;
        } else {
            echo $this->cronMsg("There was an archiving error on prodUpdate");
            return false;
        }
    }
}

