<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class update_20120412114913 extends UpdateObj {

	protected $timestamp = '20120412114913';

	protected $description = 'This update adds primary keys
to many operational database tables that currently have no primary key.
It also adds indexes to some of the tables.';

	protected $author = 'Andy Theuninck (WFC)';

	protected $queries = array(
		'op' => array(
			'ALTER TABLE batchBarcodes ADD PRIMARY KEY (batchID,upc)',
			'ALTER TABLE batchCutPaste ADD PRIMARY KEY (batchID,upc,uid)',	
			'ALTER TABLE batchMergeTable ADD PRIMARY KEY (batchID,upc)',
                	'ALTER TABLE batchMergeTable ADD INDEX (upc)',
			'ALTER TABLE batchMergeTable ADD INDEX (batchID)',
			'ALTER TABLE batchType ADD PRIMARY KEY (batchTypeID)',
			'ALTER TABLE batchowner ADD PRIMARY KEY (batchID)',
			'ALTER TABLE customReceipt ADD PRIMARY KEY (seq, type)',
			'ALTER TABLE departments ADD PRIMARY KEY (dept_no)',
			'ALTER TABLE deptMargin ADD PRIMARY KEY (dept_ID)',
			'ALTER TABLE houseCouponItems ADD PRIMARY KEY (coupID, upc)',
			'ALTER TABLE houseCouponItems ADD INDEX (coupID)',
			'ALTER TABLE houseCouponItems ADD INDEX (upc)',
			'ALTER TABLE memberCards ADD PRIMARY KEY (card_no)',
			'ALTER TABLE memberCards ADD INDEX (upc)',
			'ALTER TABLE meminfo ADD PRIMARY KEY (card_no)',
			'ALTER TABLE reasoncodes ADD PRIMARY KEY (mask)',
			'ALTER TABLE scaleItems ADD PRIMARY KEY (plu)',
			'ALTER TABLE shelftags ADD PRIMARY KEY (id,upc)',
			'ALTER TABLE shelftags ADD INDEX (upc)',
			'ALTER TABLE shelftags ADD INDEX (id)',
			'ALTER TABLE subdepts ADD PRIMARY KEY (subdept_no)',
			'ALTER TABLE superdepts ADD PRIMARY KEY (superID, dept_ID)',
			'ALTER TABLE superdepts ADD INDEX (superID)',
			'ALTER TABLE superdepts ADD INDEX (dept_ID)',
			'ALTER TABLE suspensions ADD PRIMARY KEY (cardno)',
			'ALTER TABLE tenders ADD PRIMARY KEY (TenderID)',
			'ALTER TABLE tenders ADD INDEX (TenderCode)',
			'ALTER TABLE unfiCategories ADD PRIMARY KEY (categoryID)',
			'ALTER TABLE unfi_order ADD PRIMARY KEY (upcc)',
			'ALTER TABLE upcLike ADD PRIMARY KEY (upc)',
			'ALTER TABLE vendorDepartments ADD PRIMARY KEY (vendorID, deptID)',
			'ALTER TABLE vendorDepartments ADD INDEX (vendorID)',
			'ALTER TABLE vendorDepartments ADD INDEX (deptID)'
		),
		'trans' => array(),
		'archive' => array()
	);
}

?>
