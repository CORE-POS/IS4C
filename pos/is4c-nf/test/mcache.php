<?php
include('../lib/LocalStorage/MemcacheStorage.php');

$ms = new MemcacheStorage();

$ms->set("asdf",array(100));

