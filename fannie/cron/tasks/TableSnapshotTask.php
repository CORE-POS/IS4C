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

class TableSnapshotTask extends FannieTask
{

    public $name = 'Table Snapshot';

    public $description = 'Copies table contents to a backup table
    Currently applies to products & custdata. Deprecates nightly.tablecache.php.';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);
        $sql->throwOnFailure(true);

        // drop and recreate because SQL Server
        // really hates identity inserts
        try {
            $sql->query("DROP TABLE productBackup");
        } catch (Exception $ex) {
            /**
            @severity: most likely just means first ever run
            and the backup table does not exist yet
            */
            echo $this->cronMsg("Could not drop productBackup. Details: " . $ex->getMessage(),
                    FannieTask::TASK_TRIVIAL_ERROR);
        }

        try {
            if ($sql->dbms_name() == "mssql") {
                $sql->query("SELECT * INTO productBackup FROM products");
            } else {
                $sql->query("CREATE TABLE productBackup LIKE products");
                $sql->query("INSERT INTO productBackup SELECT * FROM products");
            }
        } catch (Exception $ex) {
            /**
            @severity: backup did not happen. that's the primary
            purpose of this task.
            */
            echo $this->cronMsg("Failed to back up products. Details: " . $ex->getMessage(),
                    FannieTask::TASK_LARGE_ERROR);
        }

        try {
            $sql->query("DROP TABLE custdataBackup");
        } catch (Exception $ex) {
            /**
            @severity: most likely just means first ever run
            and the backup table does not exist yet
            */
            echo $this->cronMsg("Could not drop custdataBackup. Details: " . $ex->getMessage(),
                    FannieTask::TASK_TRIVIAL_ERROR);
        }

        try {
            if ($sql->dbms_name() == "mssql") {
                $sql->query("SELECT * INTO custdataBackup FROM custdata");
            } else {
                $sql->query("CREATE TABLE custdataBackup LIKE custdata");
                $sql->query("INSERT INTO custdataBackup SELECT * FROM custdata");
            }
        } catch (Exception $ex) {
            /**
            @severity: backup did not happen. that's the primary
            purpose of this task.
            */
            echo $this->cronMsg("Failed to back up custdata. Details: " . $ex->getMessage(),
                    FannieTask::TASK_LARGE_ERROR);
        }
    }
}

