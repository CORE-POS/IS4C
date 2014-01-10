<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

require('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class DeleteShelfTags extends FanniePage 
{

	protected $title = 'Fannie - Clear Shelf Tags';
	protected $header = 'Clear Shelf Tags';
	protected $must_authenticate = True;
	protected $auth_classes = array('barcodes');

	private $messages = '';

	function preprocess()
    {
		global $FANNIE_OP_DB;
		$id = FormLib::get_form_value('id',0);

		$dbc = FannieDB::get($FANNIE_OP_DB);
		$checkNoQ = $dbc->prepare_statement("SELECT * FROM shelftags where id=?");
		$checkNoR = $dbc->exec_statement($checkNoQ,array($id));

		$checkNoN = $dbc->num_rows($checkNoR);
		if($checkNoN == 0) {
			$this->messages = "Barcode table is already empty. <a href='ShelfTagIndex.php'>Click here to continue</a>";
			return true;
		}

		if(FormLib::get_form_value('submit', false) === '1') {
            /**
              Shelftags are not actually delete immediately
              Instead, the id field is negated so they disappear
              from view but can be manually retreived by IT if 
              someone comes complaining that they accidentally
              delete their shelftags (not that such a thing would
              ever occur). They're properly deleted by the 
              nightly.clipboard cron job.

              If the same user deletes the same UPC from shelftags
              multiple times in a day, the above procedure creates
              a primary key conflict. So any negative-id records
              that will create conflicts must be removed first.
            */
            $new_id = -1*$id;
            if ($id == 0) {
                $new_id = -999;
            }
            $problemQ = 'SELECT upc FROM shelftags WHERE id=? AND upc IN (SELECT upc FROM shelftags WHERE id=?)';
            $problemP = $dbc->prepare($problemQ);
            $problemR = $dbc->execute($problemP, array($new_id, $id));
            $clearP = $dbc->prepare('DELETE FROM shelftags WHERE id=? AND upc=?');
            while($problemW = $dbc->fetch_row($problemR)) {
               $dbc->execute($clearP, array($new_id, $problemW['upc'])); 
            }
			$deleteQ = "UPDATE shelftags SET id=? WHERE id=?";
			$prep = $dbc->prepare_statement($deleteQ);
			$deleteR = $dbc->exec_statement($prep, array($new_id, $id));
			$this->messages = "Barcode table cleared <a href='ShelfTagIndex.php'>Click here to continue</a>";

			return true;
		} else {
			$this->messages = "<span style=\"color:red;\"><a href='DeleteShelfTags.php?id=$id&submit=1'>Click 
				here to clear barcodes</a></span>";
			return true;
		}

		return true;
	}

	function body_content()
    {
		return $this->messages;
	}
}

FannieDispatch::conditionalExec(false);

