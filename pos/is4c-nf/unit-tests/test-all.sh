#!/bin/sh

unittest="phpunit --bootstrap bootstrap.php"

$unittest BaseLibsTest.php
$unittest FooterBoxesTest.php
$unittest KickersTest.php
$unittest LocalStorageTest.php
$unittest PagesTest.php
$unittest ParsersTest.php
$unittest ReceiptTest.php
$unittest SQLManagerTest.php
$unittest ScanningTest.php
$unittest SearchTest.php
$unittest TendersTest.php
