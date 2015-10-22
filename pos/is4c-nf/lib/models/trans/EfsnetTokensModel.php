<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\models\trans;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class EfsnetTokensModel
*/
class EfsnetTokensModel extends BasicModel
{

    protected $name = "efsnetTokens";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'expireDay' => array('type'=>'DATETIME'),
    'refNum' => array('type'=>'VARCHAR(50)', 'primary_key'=>true),
    'token' => array('type'=>'VARCHAR(100)', 'primary_key'=>true),
    'processData' => array('type'=>'VARCHAR(255)'),
    'acqRefData' => array('type'=>'VARCHAR(255)'),
    );

    public function doc()
    {
        return '
Use:
This table logs tokens used for modifying
later transactions.

expireDay is when(ish) the token is no longer valid

refNum maps to efsnetRequest & efsnetResponse
records

token is the actual token

processData and acqRefData are additional
values needed in addition to the token for
certain kinds of modifying transactions
        ';
    }
}

