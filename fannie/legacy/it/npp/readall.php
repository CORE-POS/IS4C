<?php

require('invoice_importer.php');

$d = opendir('csv/');

while(false !== ($filename = readdir($d))){
    if ($filename[0] != "."){
        echo "<b>$filename</b><br />";
        copy("csv/".$filename, "tmp/invoice.csv");
        import_invoice();
        rename("csv/".$filename,"old/".$filename);
    }
}
unlink("tmp/invoice.csv");

