<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class CCredHistoryTodayModel
*/
class CCredHistoryTodayModel extends ViewModel 
{

    // Actual name of view being created.
    protected $name = "CCredHistoryToday";
    protected $preferred_db = 'plugin:CoopCredDatabase';

    protected $columns = array(
    'programID' => array('type'=>'INT'),
    'cardNo' => array('type'=>'INT'),
    'charges' => array('type'=>'MONEY'),
    'payments' => array('type'=>'MONEY'),
    'tdate' => array('type'=>'DATE'),
    'transNum' => array('type'=>'INT')
    );

    public function name()
    {
        return $this->name;
    }

    public function definition()
    {
        global $FANNIE_TRANS_DB;

        /* List of CoopCred paymentDepartment's
         * Initially none, so set to dummy if empty after filling.
         */
        $dlist = '';
        $source = 'CCredPrograms';
        if ($this->connection->tableExists("$source")) {
            $dQuery = "SELECT paymentDepartment
                FROM $source
                WHERE paymentDepartment != 0";
            $dResults = $this->connection->query($dQuery);
            if ($dResults === False) {
                $this->connection->logger("Failed: $dQuery");
            } else {
                $dlist = '(';
                $sep = '';
                foreach($dResults as $row) {
                    $dlist .= ($sep . $row['paymentDepartment']);
                    $sep = ',';
                }
                $dlist .= ')';
            }
        } else {
            $this->connection->logger("Warning: Table $source doesn't exist. " .
                "View {$this->name} will not function properly.");
        }
        if (strlen($dlist) <= 2) {
            $dlist = '(-999)';
        }
        //$this->connection->logger("dlist: $dlist");

        /* List of CoopCred tenderType's
         * Initially none, so set to dummy if empty after filling.
         */
        $tlist = '';
        $source = 'CCredPrograms';
        if ($this->connection->tableExists("$source")) {
            $tQuery = "SELECT tenderType
                FROM $source
                WHERE tenderType != ''";
            $tResults = $this->connection->query($tQuery);
            if ($tResults === False) {
                $this->connection->logger("Failed: $tQuery");
            } else {
                $tlist = '(';
                $sep = '';
                foreach($tResults as $row) {
                    $tlist .= sprintf("%s'%s'", $sep, $row['tenderType']);
                    $sep = ',';
                }
                $tlist .= ')';
            }
        } else {
            $this->connection->logger("Warning: Table $source doesn't exist. " .
                "View {$this->name} will not function properly.");
        }
        if (strlen($tlist) <= 2) {
            $tlist = "('99')";
        }

        return "
    SELECT CASE WHEN t.trans_subtype in {$tlist}
                THEN p.programId
                ELSE q.programID END
                AS programID,
            t.card_no AS cardNo,
            SUM(CASE WHEN t.trans_subtype in {$tlist} THEN -t.total ELSE 0 END) AS charges,
            SUM(CASE WHEN t.department IN {$dlist} THEN t.total ELSE 0 END) AS payments,
            MAX(t.tdate) AS tdate,
            t.trans_num AS transNum
        FROM {$FANNIE_TRANS_DB}.dlog t
            LEFT JOIN coop_cred.CCredPrograms p
                ON t.trans_subtype = p.tenderType
            LEFT JOIN coop_cred.CCredPrograms q
                ON t.department = q.paymentDepartment
        WHERE ((t.trans_subtype in {$tlist} OR t.department IN {$dlist})
                AND " . $this->connection->datediff($this->connection->now(),'t.tdate') . "=0)
        GROUP BY programID, cardNo, transNum
        ";

    }

    /*
            LEFT JOIN coop_cred.CCredPrograms p
                ON t.trans_subtype = p.tenderType
            LEFT JOIN coop_cred.CCredPrograms q
                ON t.department = q.paymentDepartment
     */
}

