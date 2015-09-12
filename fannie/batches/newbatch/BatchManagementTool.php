<?php
if (basename(filter_input(INPUT_SERVER, 'PHP_SELF')) == basename(__FILE__)) {
    header('Location: BatchListPage.php');
}

