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
$page_title = 'Fannie - Batch Module';
$header = 'Item Batcher';
include('../src/header.html');
require_once('../src/mysql_connect.php');

$batchID = $_GET['batchID'];

$forceQ="UPDATE is4c_op.products as p,
	is4c_op.batches as b,
	is4c_op.batchList as l
	SET p.start_date = b.startDate,
	p.end_date = b.endDate,
	p.special_price = l.salePrice,
	p.discounttype = b.discounttype,
	l.active = 1,
	b.active = 1 
	WHERE l.upc = p.upc
	AND b.batchID = l.batchID
	AND b.batchID = $batchID";

$forceR = mysql_query($forceQ);

echo "<h2>Batch $batchID has been forced</h2></br>";
echo "<p>Return to batch list:";
echo "<form action=index.php method=post>";
echo "<input type=submit name=back value=back></form></p>";

include('../src/footer.html');

?>
