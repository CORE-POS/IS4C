<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!headers_sent()) {
    header("Location: {$FANNIE_URL}item/likecodes/LikeCodePriceUploadPage.php");
}
return;

