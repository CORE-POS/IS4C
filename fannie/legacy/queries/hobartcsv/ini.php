<?php
// define the number of scales
$num_scales = 2;

// specify scale types
$scale_types = array(
             "HOBART_QUANTUMTCP",
             "HOBART_QUANTUMTCP"
             );

// specify scale ips
$scale_ips = array(
           "30.30.1.20",
           "30.30.1.10"
           );

// department
$department = "Deli";

// important - the user running apache must own the following
// directories

// data gate weight directory
$DGW_dir = "/srv/www/htdocs/queries/hobartcsv/csv_output";

// csv creation directory
$CSV_dir = "/srv/www/htdocs/queries/hobartcsv/csvfiles";

