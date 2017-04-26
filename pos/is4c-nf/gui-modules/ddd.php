<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of IT CORE.

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

use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\ajax\AjaxEnd;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
use COREPOS\common\mvc\ValueContainer;

ini_set('display_errors','Off');
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

/*
 * Mark items as shrink/unsellable.
 *
 * DDD is WFC lingo for unsaleable goods (dropped, dented, damaged,
 * etc) Functionally this works like canceling a transaction, but
 * marks items with a different trans_status (Z) so these items can be
 * pulled out in later reports.  A mappable reason code is stored in
 * localtemptrans.numflag.
 */

$shrinkReason = 0;
if (CoreLocal::get('shrinkReason') > 0) {
    $shrinkReason = CoreLocal::get('shrinkReason');
}

$db = Database::tDataConnect();
$query = "UPDATE localtemptrans SET trans_status='Z', numflag=" . ((int)$shrinkReason);
$db->query($query);

CoreLocal::set("plainmsg","items marked as shrink/unsellable");
CoreLocal::set("End",2);
CoreLocal::set('shrinkReason', 0);

$vals = new ValueContainer();
$vals->receiptType = 'ddd';
$vals->ref = ReceiptLib::receiptNumber();
TransRecord::finalizeTransaction(true);

if (!class_exists('AjaxEnd')) {
    include(__DIR__ . '/../AjaxEnd.php');
}
$ajax = new AjaxEnd(new WrappedStorage(), $vals);
$ajax->ajax();
if (!headers_sent()) {
    header("Location: ".MiscLib::base_url()."gui-modules/pos2.php");
}

