<?php
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
// make old bookmarks work
header('Location: ProductListPage.php');
?>
