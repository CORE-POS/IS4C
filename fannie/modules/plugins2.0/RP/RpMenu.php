<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpMenu extends FannieRESTfulPage
{
    protected $header = 'Produce Ordering Menu';
    protected $title = 'Produce Ordering Menu';

    protected function get_view()
    {
        return <<<HTML
<ul>
    <li>Daily Tools
    <ul>
        <li><a href="RpOrderPage.php">Regular Ordering</a> - Alberts, CPW, RDW, and year-round direct</li>
        <li><a href="RpDirectPage.php">Direct Ordering</a> - local & seasonal</li>
        <li><a href="RpArchivePage.php">Direct Orders Archive</a> - local & seasonal</li>
        <li><a href="RpDailyPage.php">Daily Sheet Info</a> - sales info & item lists</li>
        <li><a href="RpPrintOrders.php">Reprint Order</a> - reprint an archived order</li>
    </ul>
</ul>
<hr />
<ul>
    <li>Floral</li>
    <ul>
        <li><a href="RpFloralPage.php">Order Guide</a></li>
        <li><a href="../../../item/shrink/ShrinkTool.php">Enter Shrink</a></li>
    </ul>
</ul>
<!--
<ul>
    <li>Plant Pre-Orders</li>
    <ul>
        <li><a href="../Pickup/ViewPickups.php">View Customer Orders</a></li>
        <li><a href="../../../item/inventory/InvCountPage.php">Perpetual Inventory</a></li>
        <li><a href="../../../purchasing/importers/FairhavenInvoiceImport.php">Upload Fairhaven Invoice</a></li>
        <li><a href="../../../item/shrink/ShrinkTool.php">Enter Shrink</a></li>
        <li><a href="../Pickup/PushUpdate.php">Update Website Inventory</a></li>
    </ul>
</ul>
-->
<hr />
<ul>
    <li>Data Management</li>
    <ul>
        <li><a href="RpCategoriesPage.php">Categories</a> - sort which order the categories appear in</li>
        <li><a href="RpFarmsPage.php">Farms</a> - list of local growers for direct ordering</li>
        <li><a href="RpLocalLCsPage.php">Local Like Codes</a> - subset of like codes that are ordered direct</li>
        <li><a href="RpFarmSchedule.php">Committment Schedule</a> - set primary and secondary farm for a given like code & time period</li>
        <li><a href="RpFixedMaps.php">Fixed Maps</a> - attach a specific vendor SKU to a likecode instead of using automated name-based matching</li>
        <li><a href="RpFileManager.php">Import from RP</a> - reload data from the RP Excel file</li>
        <li><a href="RpSegmentation.php">Sales Segmentation</a> - projected day-by-day sales for a week</li>
        <li><a href="RpMarginEst.php">Estimate Margin</a> - drop in pricing from RP and preview applied margin</li>
        <li><a href="RpPreBookPage.php">Enter Pre-Books</a> - input pre-books so they appear in the order guide.</a></li>
        <li><a href="RpManualEntries.php">View Added Items</a> - report of items manually added to the order guide</a></li>
        <li><a href="RpForecast.php">Project Order Quantities</a> - view anticipated order quantities based on segmentation and pars</a></li>
    </ul>
</ul>
<hr />
<ul>
    <li>Pricing</li>
    <ul>
        <li>Comparison Shop</li>
        <ul>
            <li><a href="../../../item/likecodes/LikeCodeSKUsPage.php?id=6&store=1">Active @ Hillside</a></li>
            <li><a href="../../../item/likecodes/LikeCodeSKUsPage.php?id=6&store=2">Active @ Denfeld</a></li>
            <li><a href="../../../item/likecodes/LikeCodeSKUsPage.php?id=6&store=0">Active @ Either</a></li>
        </ul>
        <li>Upload Pricesheet</li>
        <ul>
            <li><a href="../../../batches/UNFI/load-classes/AlbertsUploadPage.php">Alberts</a></li>
            <li><a href="../../../batches/UNFI/load-classes/CpwProduceUploadPage.php">CPW</a></li>
            <li><a href="../../../batches/UNFI/load-classes/RdwUploadPage.php">RDW</a></li>
        </ul>
    </ul>
</ul>
HTML;
    }
}

FannieDispatch::conditionalExec();

