<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Modifications copyright 2010 Whole Foods Co-op

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

use COREPOS\pos\lib\FormLib;

class StripeQrCode {}

if (!file_exists(dirname(__FILE__) . '/../../../../../vendor/endroid/qrcode/src/Endroid/QrCode')) {
    return;
}

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

// QR codegen via composer
if (!class_exists('\\Endroid\\QrCode\\Exceptions\\DataDoesntExistsException')) {
    include(dirname(__FILE__) . '/../../../../../vendor/endroid/qrcode/src/Endroid/QrCode/Exceptions/DataDoesntExistsException.php');
}
if (!class_exists('\\Endroid\\QrCode\\Exceptions\\ImageFunctionUnknownException')) {
    include(dirname(__FILE__) . '/../../../../../vendor/endroid/qrcode/src/Endroid/QrCode/Exceptions/ImageFunctionUnknownException.php');
}
if (!class_exists('\\Endroid\\QrCode\\Exceptions\\ImageSizeTooLargeException')) {
    include(dirname(__FILE__) . '/../../../../../vendor/endroid/qrcode/src/Endroid/QrCode/Exceptions/ImageSizeTooLargeException.php');
}
if (!class_exists('\\Endroid\\QrCode\\Exceptions\\VersionTooLargeException')) {
    include(dirname(__FILE__) . '/../../../../../vendor/endroid/qrcode/src/Endroid/QrCode/Exceptions/VersionTooLargeException.php');
}
if (!class_exists('\\Endroid\\QrCode\\QrCode')) {
    include(dirname(__FILE__) . '/../../../../../vendor/endroid/qrcode/src/Endroid/QrCode/QrCode.php');
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__) && FormLib::get('data') !== '') {
    header('Content-Type: image/png');
    $data = base64_decode(FormLib::get('data'));
    $qr_code = new \Endroid\QrCode\QrCode();
    $qr_code->setText($data);
    $qr_code->setSize(280);
    $qr_code->setPadding(10);
    $qr_code->render();
}
