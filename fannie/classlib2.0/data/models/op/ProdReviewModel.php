<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../../FannieDB.php');
}
if (!class_exists('BarcodeLib')) {
    include(dirname(__FILE__).'/../../../lib/BarcodeLib.php');
}

class ProdReviewModel extends BasicModel
{
    protected $name = 'prodReview';

    protected $preferred_db = 'op';

    protected $columns = array(
    'id' => array('type'=>'int(11)', 'primary_key'=>true, 'increment'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'unique'=>true),
    'user' => array('type'=>'int(8)'),
    'reviewed' => array('type'=>'date'),
    );
    
    protected $unique = array('upc');

    public function doc()
    {
        return '
Use:
Tracks the last time a product\'s cost and price were
reviewed by a person.
            ';
    }

}

