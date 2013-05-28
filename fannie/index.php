<?php

$slash = strrpos($_SERVER['REQUEST_URI'],"/");
$rel_uri = substr($_SERVER['REQUEST_URI'],0,$slash);
header("Location: $rel_uri/item/ItemEditorPage.php");
exit;
?>
