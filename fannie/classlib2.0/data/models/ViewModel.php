<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ViewModel
*/
class ViewModel extends BasicModel
{

    protected $name = '__VirtualClass_ShouldNotExist';

    /**
      Generate SQL definition of view.
      Is a method rather than property in case
      any part of the query needs to be generated
      based on DBMS and/or configuration values
      @return [string] view definition

      Note: This should be everything that
      follows the "CREATE VIEW view_name AS".
      Do not include that part.
    */
    public function definition()
    {
        return 'SELECT 1 as columnName';
    }

    /**
      Create specified view
      @return [boolean]
    */
    public function create()
    { 
        if ($this->connection->isView($this->name)) {
            return true;
        }

        $selectQuery = $this->definition();
        $createQuery = 'CREATE VIEW '
            . $this->connection->identifierEscape($this->name)
            . ' AS '
            . $selectQuery;

        $try = $this->connection->query($createQuery);

        return $try ? true : false;
    }

    /**
      Deletes the view. This will drop an
      existing view but will not drop an 
      existing table.
      @return [boolean]
    */
    public function delete()
    {
        if (!$this->connection->isView($this->name)) {
            return false;
        }

        $try = $this->connection->query('DROP VIEW ' . $this->connection->identifierEscape($this->name));

        return $try ? true : false;
    }

    /**
      Does nothing. No meaning in context of a view.
    */
    public function save()
    { 
        return false; 
    }

    /**
      Normalize will drop a table and recreate as a view if needed
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        // don't try to detect a db structure corresponding to
        // the ViewModel class itself
        if ($this->name == '__VirtualClass_ShouldNotExist') {
            return 0;
        }

        if ($mode != BasicModel::NORMALIZE_MODE_CHECK && $mode != BasicModel::NORMALIZE_MODE_APPLY) {
            echo "Error: Unknown mode ($mode)\n";
            return false;
        }

        echo "==========================================\n";
        printf("%s view %s\n", 
            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Checking":"Updating", 
            "{$db_name}.{$this->name}"
        );
        echo "==========================================\n";

        if (!$this->currently_normalizing_lane) {
            $this->connection = FannieDB::get($db_name);
        }

        if (!$this->connection->table_exists($this->name)) {
            if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
                echo "View {$this->name} not found!\n";
                echo "==========================================\n";
                printf("%s view %s\n","Check complete. Need to create", $this->name);
                echo "==========================================\n\n";
                return 999;
            } else if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                echo "==========================================\n";
                if ($doCreate) {
                    $cResult = $this->create(); 
                    printf("Update complete. Creation of view %s %s\n",$this->name, ($cResult)?"OK":"failed");
                    // create succeeded, normalize_lanes enabled
                    if ($cResult && $this->normalize_lanes && !$this->currently_normalizing_lane) {
                        $this->normalizeLanes($db_name, $mode, $doCreate);
                    }
                } else {
                    printf("Update complete. Creation of view %s %s\n",$this->name, ($doCreate)?"OK":"not supported");
                }
                echo "==========================================\n\n";
                return true;
            }
        } elseif (!$this->connection->isView($this->name)) {
            if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
                echo "Table {$this->name} should be a view!\n";
                echo "==========================================\n";
                printf("%s view %s\n","Check complete. Need to drop and re-create", $this->name);
                echo "==========================================\n\n";
                return 999;
            } else if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                echo "==========================================\n";
                if ($doCreate) {
                    $cResult = $this->delete();
                    if ($cResult) {
                        $cResult = $this->create(); 
                    }
                    printf("Update complete. Creation of view %s %s\n",$this->name, ($cResult)?"OK":"failed");
                    // create succeeded, normalize_lanes enabled
                    if ($cResult && $this->normalize_lanes && !$this->currently_normalizing_lane) {
                        $this->normalizeLanes($db_name, $mode, $doCreate);
                    }
                } else {
                    printf("Update complete. Creation of view %s %s\n",$this->name, ($doCreate)?"OK":"not supported");
                }
                echo "==========================================\n\n";
                return true;
            }
        }

        return 0;
    }

    /* START ACCESSOR FUNCTIONS */
    /* END ACCESSOR FUNCTIONS */
}

