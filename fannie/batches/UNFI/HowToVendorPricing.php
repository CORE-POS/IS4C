<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class HowToVendorPricing extends FanniePage {
    protected $window_dressing = False;

    public $description = '[Vendor Pricing Documentation] describes uploading vendor catalog price files.';

    function css_content(){
        return 'img {
            border: solid 1px black;
        }';
    }

    function body_content(){
        ob_start();
        ?>
Step 1: Obtain a UNFI price file. The zip file I got had three files in it, only
one has pricing info and that's the one we need. Open it up in Excel
and save it as filename <i>unfi.csv</i>, format <i>CSV (Windows)</i>.<br />
<img src=images/saveas.png />
<br />
<hr />
<br />
Step 2: That file is probably too big. Right click on it and select
<i>Create archive</i> to make a zip file.<br />
<img src=images/archive.png />
<br />
<hr />
<br />
Step 3: Go to the <a href=UploadVendorPriceFile.php>upload page</a>, click Browse, and select
the zip file you just made (if done as above, it should be named 
<i>unfi.csv.zip</i>). Click Upload File and wait. It can take a while
for a big price file.<br />
<br />
<hr />
<br />
Step 4: If everything goes correctly, you'll get output something like this
(it doesn't matter if there are more or less UNFISPLIT files). If you get
anything drastically different, tell Andy.<br />
<img src=images/results.png />
<br />
<hr />
<br />
Step 5 (optional): track down a dedicated professional to help<br />
<img src=images/techsupport.jpg />
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
