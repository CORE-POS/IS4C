<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

namespace COREPOS\common\sql;

/**
 @class TestManager
*/
class TestManager extends \COREPOS\common\SQLManager
{
    private $test_data = array();
    private $test_counter = 0;

    /**
      Test data is for faking queries.
      Setting the test data then running
      a unit test means the test will get
      predictable results.
    */
    public function setTestData($records)
    {
        $this->test_data = $records;
        $this->test_counter = 0;
    }

    private function getTestDataRow()
    {
        if (isset($this->test_data[$this->test_counter])) {
            $next = $this->test_data[$this->test_counter];
            $this->test_counter++;
            return $next;
        } else {
            return false;
        }
    }

    public function fetchArray($result_object,$which_connection='')
    {
        return $this->getTestDataRow();
    }
}

