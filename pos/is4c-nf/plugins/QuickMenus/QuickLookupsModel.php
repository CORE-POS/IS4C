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
  @class QuickLookupsModel
*/
class QuickLookupsModel extends BasicModel
{

    protected $name = "QuickLookups";

    protected $preferred_db = 'op';

    protected $columns = array(
    'quickLookupID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'lookupSet' => array('type'=>'SMALLINT', 'default'=>0),
    'label' => array('type'=>'VARCHAR(100)'),
    'action' => array('type'=>'VARCHAR(25)'),
    'sequence' => array('type'=>'SMALLINT', 'default'=>0),
	);

    /* START ACCESSOR FUNCTIONS */

    public function quickLookupID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quickLookupID"])) {
                return $this->instance["quickLookupID"];
            } elseif(isset($this->columns["quickLookupID"]["default"])) {
                return $this->columns["quickLookupID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["quickLookupID"] = func_get_arg(0);
        }
    }

    public function lookupSet()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lookupSet"])) {
                return $this->instance["lookupSet"];
            } elseif(isset($this->columns["lookupSet"]["default"])) {
                return $this->columns["lookupSet"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["lookupSet"] = func_get_arg(0);
        }
    }

    public function label()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["label"])) {
                return $this->instance["label"];
            } elseif(isset($this->columns["label"]["default"])) {
                return $this->columns["label"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["label"] = func_get_arg(0);
        }
    }

    public function action()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["action"])) {
                return $this->instance["action"];
            } elseif(isset($this->columns["action"]["default"])) {
                return $this->columns["action"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["action"] = func_get_arg(0);
        }
    }

    public function sequence()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sequence"])) {
                return $this->instance["sequence"];
            } elseif(isset($this->columns["sequence"]["default"])) {
                return $this->columns["sequence"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sequence"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

