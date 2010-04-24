<?php

if (!function_exists("initiate_session")) {
    include("session.php");
}

initiate_session();
header("Location:pos2.php");

