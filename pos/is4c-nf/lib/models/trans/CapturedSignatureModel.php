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
  @class CapturedSignatureModel
*/
class CapturedSignatureModel extends BasicModel
{

    protected $name = "CapturedSignature";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'capturedSignatureID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'tdate' => array('type'=>'DATETIME', 'index'=>true),
    'emp_no' => array('type'=>'INT'),
    'register_no' => array('type'=>'INT', 'index'=>true),
    'trans_no' => array('type'=>'INT'),
    'trans_id' => array('type'=>'INT'),
    'filetype' => array('type'=>'CHAR(3)'),
    'filecontents' => array('type'=>'BLOB'),
    );

    public function doc()
    {
        return '
Table: CapturedSignature

Columns:
    capturedSignatureID int
    tdate datetime
    emp_no int
    register_no int
    trans_no int
    trans_id int
    filetype varchar
    filecontents binary data

Depends on:
    none

Use:
This table contains digital images of customer signatures.
The standard dtransactions columns indicate what transaction
line the signature goes with. Filetype is a three letter extension
indicating what kind of image it is, and filecontents is the
raw image data. This data is in the database because it\'s the
only existing pathway to transfer information from the lane
to the server.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function capturedSignatureID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["capturedSignatureID"])) {
                return $this->instance["capturedSignatureID"];
            } elseif(isset($this->columns["capturedSignatureID"]["default"])) {
                return $this->columns["capturedSignatureID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["capturedSignatureID"] = func_get_arg(0);
        }
    }

    public function tdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tdate"])) {
                return $this->instance["tdate"];
            } elseif(isset($this->columns["tdate"]["default"])) {
                return $this->columns["tdate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["tdate"] = func_get_arg(0);
        }
    }

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } elseif(isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["emp_no"] = func_get_arg(0);
        }
    }

    public function register_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["register_no"])) {
                return $this->instance["register_no"];
            } elseif(isset($this->columns["register_no"]["default"])) {
                return $this->columns["register_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["register_no"] = func_get_arg(0);
        }
    }

    public function trans_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_no"])) {
                return $this->instance["trans_no"];
            } elseif(isset($this->columns["trans_no"]["default"])) {
                return $this->columns["trans_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_no"] = func_get_arg(0);
        }
    }

    public function trans_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_id"])) {
                return $this->instance["trans_id"];
            } elseif(isset($this->columns["trans_id"]["default"])) {
                return $this->columns["trans_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_id"] = func_get_arg(0);
        }
    }

    public function filetype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["filetype"])) {
                return $this->instance["filetype"];
            } elseif(isset($this->columns["filetype"]["default"])) {
                return $this->columns["filetype"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["filetype"] = func_get_arg(0);
        }
    }

    public function filecontents()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["filecontents"])) {
                return $this->instance["filecontents"];
            } elseif(isset($this->columns["filecontents"]["default"])) {
                return $this->columns["filecontents"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["filecontents"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

