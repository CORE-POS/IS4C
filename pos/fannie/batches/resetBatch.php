<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

// include('src/funct1Mem.php');
require_once('../src/mysql_connect.php');

$batchID = $_GET['batchID'];

$resetQ="UPDATE is4c_op.products AS p,
	is4c_op.batches AS b,
	is4c_op.batchList AS l
	SET p.start_date = NULL,
	p.end_date = NULL,
	p.special_price = 0,
	p.discounttype = 0
	WHERE l.upc = p.upc
	AND b.batchID = l.batchID
	AND b.batchID = $batchID";

$resetR = mysql_query($resetQ);

//$batchUpQ = "EXEC batchUpdate";
//$batchUpR = mysql_query($batchUpQ);

echo $resetQ;
echo "<h2>Batch $batchID has been reset</h2></br>";
echo "<p>Return to batch list";
echo "<form action=index.php method=post>";
echo "<input type=submit name=back value=back></form></p>";


?>