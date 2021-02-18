<?php

use COREPOS\pos\lib\Scanning\SpecialUPCs\DatabarCoupon;

include('test_env.php');

$barcode = '8110408537530081000122501100001311000040859453005';
/**
 * 8110         => coupon prefix
 * 4            => prefix length (effetively 10)
 * 0853753008   => first prefix
 * 100012       => offer code
 * 2            => value length
 * 50           => value code
 * 1            => primary req length
 * 1            => primary req value
 * 0            => primary req type code
 * 000          => primary req family
 * 1            => second req follows
 * 3            => second req rules code    !!!!!!!
 * 1            => second req length
 * 1            => second req value
 * 0            => second req type code
 * 000          => second req family code
 * 4            => second req prefix length (effectively 10)
 * 0859453005   => second req prefix
 */
$dc = new DatabarCoupon();
$json = $dc->handle($barcode, array());
