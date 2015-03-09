<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

/**
  This file contains the default menu if the user has not defined one.
  
  A menu entry is an array. Every menu entry should contain two keys:
  'label' is the text to display
  'url' is where the menu item goes

  Optionally, a menu entry may contain a 'submenu' which is simply an
  array of more menu entries. 

  If the URL does not start with / or http://, $FANNIE_URL is automatically
  prepended.

  Top level menu entries may also contain a 'subheading'. This text goes
  below the label in Fannie's left-hand menu. The 'subheading' value will
  be ignored in deeper layers of the menu.  
*/

$FANNIE_MENU = array(
array('label'=>'Item Maintenance','url'=>'item/ItemEditorPage.php','subheading'=>'Manage our product DB',
'submenu'=>array(
	array('label'=>'Manage Products >>','url'=>'item/ItemEditorPage.php',
		'submenu'=>array(
			array('label'=>'By UPC/SKU or Brand Prefix','url'=>'item/ItemEditorPage.php'),
			array('label'=>'Product List and Tool','url'=>'item/ProductListPage.php'),
			array('label'=>'Product Flags','url'=>'item/ItemFlags.php')
		)
	),
	array('label'=>'Import Products >>','url'=>'item/import/',
		'submenu'=>array(
			array('label'=>'Products','url'=>'item/import/ProductImportPage.php'),
			array('label'=>'Upload a file','url'=>'item/import/UploadAnyFile.php')
		)
	),
	array('label'=>'Manage Departments >>','url'=>'item/departments/',
		'submenu'=>array(
			array('label'=>'Super Departments','url'=>'item/departments/SuperDeptEditor.php'),
			array('label'=>'Departments','url'=>'item/departments/DepartmentEditor.php'),
			array('label'=>'Sub Departments','url'=>'item/departments/SubDeptEditor.php')
		)
	),
	array('label'=>'Import Departments >>','url'=>'item/import/',
		'submenu'=>array(
			array('label'=>'Departments','url'=>'item/import/DepartmentImportPage.php'),
			array('label'=>'Sub Departments','url'=>'item/import/SubdeptImportPage.php'),
			array('label'=>'Upload a file','url'=>'item/import/UploadAnyFile.php')
		)
	),
	array('label'=>'Manage Likcodes','url'=>'item/likecodes/'),
	array('label'=>'Manage Vendors','url'=>'item/vendors/'),
	array('label'=>'Purchase Orders','url'=>'purchasing/'),
	array('label'=>'Store Coupons','url'=>'modules/plugins2.0/HouseCoupon/')
	)
),
array('label'=>'Sales Batches','url'=>'batches/','subheading'=>'Create automated sales & price changes',
'submenu'=>array(
	array('label'=>'Sales Batches','url'=>'batches/newbatch/'),
	array('label'=>'Upload Batch','url'=>'batches/xlsbatch/'),
	array('label'=>'Manage Batch Types','url'=>'batches/BatchTypeEditor.php'),
	array('label'=>'Co+op Deals Sales','url'=>'batches/CAP/'),
	array('label'=>'Vendor Pricing','url'=>'batches/UNFI/')
	)
),	
array('label'=>'Reports','url'=>'reports/','subheading'=>'Custom reporting tools',
'submenu'=>array(
	array('label'=>'Movement >>','url'=>'reports/',
		'submenu'=>array(
			array('label'=>'Department Movement','url'=>'reports/DepartmentMovement/'),
			array('label'=>_('Manufacturer Movement'),'url'=>'reports/ManufacturerMovement/'),
			array('label'=>'Item Movement','url'=>'reports/ProductMovement/'),
			array('label'=>'Correlated Movement','url'=>'reports/Correlated/'),
			array('label'=>'Non Movement','url'=>'reports/NonMovement/'),
			array('label'=>'Trends','url'=>'reports/Trends/'),
			array('label'=>'Monthly Movement','url'=>'reports/MonthOverMonth/'),
			array('label'=>'Movement By Price','url'=>'reports/PriceMovement/')
		)
	),
	array('label'=>'Sales >>','url'=>'reports/',
		'submenu'=>array(
			array('label'=>'General Sales Report','url'=>'reports/GeneralSales/'),
			array('label'=>'General Cost Report','url'=>'reports/GeneralCost/'),
			array('label'=>'Sales Today','url'=>'reports/SalesToday/'),
			array('label'=>'Hourly Sales','url'=>'reports/HourlySales/HourlySalesReport.php'),
			array('label'=>'Hourly Transactions','url'=>'reports/HourlyTrans/HourlyTransReport.php')
		)
	),
	array('label'=>'Product List and Tool','url'=>'item/ProductListPage.php'),
	array('label'=>'Price History Report','url'=>'reports/PriceHistory/'),
	array('label'=>'Department Settings','url'=>'reports/DepartmentSettings/'),
	array('label'=>'Open Rings','url'=>'reports/OpenRings/'),
	array('label'=>'Batch Report','url'=>'reports/BatchReport/'),
	array('label'=>'Customer Count','url'=>'reports/CustomerCount/')
	)
),
array('label'=>'Dayend Polling','url'=>'cron/management/','subheading'=>'Scheduled tasks',
'submenu'=>array(
	array('label'=>'Scheduled Tasks','url'=>'cron/management/'),
	array('label'=>'View Logs','url'=>'logs/'),
	)
),
array('label'=>'Synchronize','url'=>'sync/','subheading'=>'Update cash registers',
'submenu'=>array(
	array('label'=>'Products','url'=>'sync/TableSyncPage.php?tablename=products'),
	array('label'=>'ProductUser','url'=>'sync/TableSyncPage.php?tablename=productUser'),
	array('label'=>'Membership','url'=>'sync/TableSyncPage.php?tablename=custdata'),
	array('label'=>'Member Cards','url'=>'sync/TableSyncPage.php?tablename=memberCards'),
	array('label'=>_('Cashiers'),'url'=>'sync/TableSyncPage.php?tablename=employees'),
	array('label'=>'Departments','url'=>'sync/TableSyncPage.php?tablename=departments'),
	array('label'=>'Super Departments','url'=>'sync/TableSyncPage.php?tablename=MasterSuperDepts')
	)
),
array('label'=>'Admin','url'=>'admin/','subheading'=>'Administrative functions, etc.',
'submenu'=>array(
	array('label'=>_('Cashier Management') . ' >>','url'=>'admin/Cashiers/',
	'submenu'=>array(
		array('label'=>_('Add a new Cashier'),'url'=>'admin/Cashiers/AddCashierPage.php'),
		array('label'=>_('View/edit Cashiers'),'url'=>'admin/Cashiers/ViewCashiersPage.php'),
		array('label'=>_('Cashier performance report'),'url'=>'reports/cash_report/')
		)
	),
	array('label'=>'Member Management >>','url'=>'mem/',
	'submenu'=>array(
		array('label'=>'View/edit Members','url'=>'mem/MemberSearchPage.php'),
		array('label'=>'Manage Member Types','url'=>'mem/MemberTypeEditor.php'),
		array('label'=>'Create New Members','url'=>'mem/NewMemberTool.php'),
		array('label'=>'Print Member Stickers','url'=>'mem/numbers/'),
		array('label'=>'Import Member Information >>','url'=>'mem/import/',
			'submenu'=>array(
				array('label'=>'Names & Numbers','url'=>'mem/import/MemNameNumImportPage.php'),
				array('label'=>'Contact Information','url'=>'mem/import/MemContactImportPage.php'),
				array('label'=>'Existing Equity','url'=>'mem/import/EquityHistoryImportPage.php')
				)
			)
		)
	),
	array('label'=>'Email Statements >>','url'=>'mem/statements/',
	'submenu'=>array(
		array('label'=>'AR (Member)','url'=>'mem/statements/indvAR.php'),
		array('label'=>'AR (Business EOM)','url'=>'mem/statements/busAR.php'),
		array('label'=>'AR (Business Any Balance)','url'=>'mem/statements/busAR.php'),
		array('label'=>'Equity','url'=>'mem/statements/equity.php'),
		array('label'=>'Sent E-mail History','url'=>'mem/statements/history.php')
		)
	),
	array('label'=>'Tenders','url'=>'admin/Tenders/'),
	array('label'=>'Special Orders >>','url'=>'ordering/',
	'submenu'=>array(
		array('label'=>'Create Order','url'=>'ordering/view.php'),
		array('label'=>'Review Active Orders','url'=>'ordering/clearinghouse.php'),
		array('label'=>'Review Old Orders','url'=>'ordering/historical.php'),
		array('label'=>'Receiving Report','url'=>'ordering/receivingReport.php'),
		array('label'=>'Muzak','url'=>'ordering/muzak.php')
		)
	),
	array('label'=>'Print Shelftags','url'=>'admin/labels/'),
	array('label'=>'Transaction Lookup','url'=>'admin/LookupReceipt/'),
	)
)
);
