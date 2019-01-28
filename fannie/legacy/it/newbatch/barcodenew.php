<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once(__DIR__ . "/../../../src/SQLManager.php");
include('../../db.php');

if (!isset($_GET['batchID'])){

    echo "<form action=barcodenew.php method=get>";
    echo "<b>Select batch(es*) to be printed</b>:<br />";
    $fetchQ = "select b.batchID,b.batchName
              from batches as b left join
              batchBarcodes as c on b.batchID = c.batchID
              where c.upc is not null
              group by b.batchID,b.batchName
              order by b.batchID desc";
    $fetchR = $sql->query($fetchQ);
    echo "<select name=batchID[] multiple style=\"{width:300px;}\" size=20>";
    while($fetchW = $sql->fetchRow($fetchR))
        echo "<option value=$fetchW[0]>$fetchW[1]</option>";
    echo "</select><br />";
    echo "<input type=submit value=Print /> ";
    echo "<input type=checkbox name=narrow /> Narrow";
    echo "</form>";
    echo "<a href=index.php>Back to batch list</a><p />";
    echo "* Hold the apple key while clicking to select multiple batches ";
    echo "(or the control key if you're not on a Mac)";
}
else {
    $batchIDList = '';
    $args = array();
    foreach($_GET['batchID'] as $x) {
        $batchIDList .= '?,';
        $args[] = $x;
    }
    $batchIDList = substr($batchIDList,0,strlen($batchIDList)-1);
    $narrow = (isset($_GET['narrow']))?True:False;
    
    define('FPDF_FONTPATH','font/');
    require(__DIR__ . '/../../../src/fpdf/fpdf.php');
    
    /****Credit for the majority of what is below for barcode generation
     has to go to Olivier for posting the script on the FPDF.org scripts
     webpage.****/
    
    class PDF extends FPDF
    {
       function EAN13($x,$y,$barcode,$h=16,$w=.35)
       {
            $this->Barcode($x,$y,$barcode,$h,$w,13);
       }
    
       function UPC_A($x,$y,$barcode,$h=16,$w=.35)
       {
          $this->Barcode($x,$y,$barcode,$h,$w,12);
        }
    
        function GetCheckDigit($barcode)
       {
          if (strlen($barcode) < 12)
            $barcode = str_pad($barcode,12,"0",STR_PAD_LEFT);
           //Compute the check digit
          $sum=0;
          for($i=1;$i<=11;$i+=2)
            $sum+=3*$barcode{$i};
          for($i=0;$i<=10;$i+=2)
            $sum+=$barcode{$i};
          $r=$sum%10;
          if($r>0)
            $r=10-$r;
          return $r;
       }
    
       function TestCheckDigit($barcode)
       {
          //Test validity of check digit
          $sum=0;
          for($i=1;$i<=11;$i+=2)
            $sum+=3*$barcode{$i};
          for($i=0;$i<=10;$i+=2)
            $sum+=$barcode{$i};
          return ($sum+$barcode{12})%10==0;
       }
    
       function Barcode($x,$y,$barcode,$h,$w,$len)
       {
          //Padding
          $barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
          if($len==12)
            $barcode='0'.$barcode;
          //Add or control the check digit
          if(strlen($barcode)==12)
            $barcode.=$this->GetCheckDigit($barcode);
            /*
          elseif(!$this->TestCheckDigit($barcode)){
            $this->Error('This is an Incorrect check digit' . $barcode);
            //echo $x.$y.$barcode."\n";
          }*/
          //Convert digits to bars
          $codes=array(
            'A'=>array(
                '0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
                '5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
            'B'=>array(
                '0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
                '5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
            'C'=>array(
                '0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
                '5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100')
            );
          $parities=array(
            '0'=>array('A','A','A','A','A','A'),
            '1'=>array('A','A','B','A','B','B'),
            '2'=>array('A','A','B','B','A','B'),
            '3'=>array('A','A','B','B','B','A'),
            '4'=>array('A','B','A','A','B','B'),
            '5'=>array('A','B','B','A','A','B'),
            '6'=>array('A','B','B','B','A','A'),
            '7'=>array('A','B','A','B','A','B'),
            '8'=>array('A','B','A','B','B','A'),
            '9'=>array('A','B','B','A','B','A')
            );
          $code='101';
          $p=$parities[$barcode{0}];
          for($i=1;$i<=6;$i++)
            $code.=$codes[$p[$i-1]][$barcode{$i}];
          $code.='01010';
          for($i=7;$i<=12;$i++)
            $code.=$codes['C'][$barcode{$i}];
          $code.='101';
          //Draw bars
          for($i=0;$i<strlen($code);$i++)
          {
            if($code{$i}=='1')
                $this->Rect($x+$i*$w,$y,$w,$h,'F');
          }
          //Print text uder barcode
          $this->SetFont('Arial','',9);
          if (isset($_GET['narrow']))
              $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
          else
              $this->Text($x+6,$y+$h+11/$this->k,substr($barcode,-$len));
        }
    }
    
    $query = $sql->prepare("SELECT upc,description,normal_price,brand,sku,size,units,vendor FROM batchBarcodes WHERE batchID in ($batchIDList) and description <> '' order by batchID");

    $result = $sql->execute($query, $args);
    
    if (!$narrow){
        $pdf=new PDF('P','mm','Letter'); //start new instance of PDF
        $pdf->Open(); //open new PDF Document
        $pdf->SetTopMargin(20);  //Set top margin of the page
        $pdf->SetLeftMargin(4);  //Set left margin of the page
        $pdf->SetRightMargin(0);  //Set the right margin of the page
        $pdf->AddPage();  //Add a page
        
        //Set increment counters for rows 
        $i = 9;  //x location of barcode
        $j = 33; //y locaton of barcode
        $l = 28; //y location of size and price on label
        $k = 25; //x location of date and price on label
        $m = 0;  //number of labels created
        $n = 20; //y location of description for label
        $r = 24; //y location of brand name for label
        $p = 5;  //x location fields on label
        $t = 32; //y location of SKU and vendor info
        $u = 20; //x locaiton of vendor info for label
        $down = 31;

        $diff = 0;
        $j -= $diff;
        $l -= $diff;
        $n -= $diff;
        $r -= $diff;
        $t -= $diff;
        
        //cycle through result array of query
        while($row = $sql->fetchRow($result)){
           //If $m == 32 add a new page and reset all counters..
           if($m == 32){
              $pdf->AddPage();
              $i = 9;
              $j = 33;
              $l = 28;
              $k = 25;
              $m = 0;
              $n = 20;
              $p = 5;  
              $r = 24;
              $t = 32;
              $u = 20;
            $diff = 0;
            $j -= $diff;
            $l -= $diff;
            $n -= $diff;
            $r -= $diff;
            $t -= $diff;
           }
        
           //If $i > 175, start a new line of labels
           if($i > 175){
              $i = 9;
              $j = $j + $down;
              $k = 25;
              $l = $l + $down;
              $n = $n + $down;
              $r = $r + $down;
              $p = 5;
              $u = 20;
              $t = $t + $down;
           }
           $price = $row['normal_price'];
           $desc = strtoupper(substr($row['description'],0,27));
           $brand = ucwords(strtolower(substr($row['brand'],0,13)));
           $pak = $row['units'];
           $size = $row['units'] . "-" . $row['size'];
           $sku = $row['sku'];
           $upc = ltrim($row['upc'],0);
           $check = $pdf->GetCheckDigit($upc);
           $tagdate = date('m/d/y');
           $vendor = substr($row['vendor'],0,7);
           
           //Start laying out a label 
           $pdf->SetFont('Arial','',8);  //Set the font 
           $pdf->TEXT($p,$n,$desc);   //Add description to label
           $pdf->TEXT($p,$r,$brand);  //Add brand name to label
           $pdf->TEXT($p,$l,$size);  //Add size to label
           $pdf->TEXT($k+9,$t,$tagdate);  //Add date to lable
           $pdf->SetFont('Arial','',10);  //change font size
           $pdf->TEXT($p,$t,$sku);  //add UNFI SKU
           $pdf->TEXT($u-2,$t,$vendor);  //add vendor 
           $pdf->SetFont('Arial','B',24); //change font for price
           $pdf->TEXT($k,$l,$price);  //add price
        
           $newUPC = $upc . $check; //add check digit to upc
           $pdf->UPC_A($i,$j,$upc,7);  //generate barcode and place on label
        
           //increment counters    
           $i =$i+ 53;
           $k = $k + 53;
           $m = $m + 1;
           $p = $p + 53;
           $u = $u + 53;
        }
    
        $pdf->Output();  //Output PDF file to screen.
    }
    else {
        $pdf=new PDF('P','mm','Letter'); //start new instance of PDF
        $pdf->Open(); //open new PDF Document
        $pdf->SetTopMargin(40);  //Set top margin of the page
        $pdf->SetLeftMargin(4);  //Set left margin of the page
        $pdf->SetRightMargin(0);  //Set the right margin of the page
        $pdf->AddPage();  //Add a page

        //Set increment counters for rows 
        $i = 5;  //x location of barcode
        $j = 33; //y locaton of barcode
        $l = 32; //y location of size and price on label
        $k = 5; //x location of date and price on label
        $m = 0;  //number of labels created
        $n = 20; //y location of description for label
        $r = 26; //y location of date for label
        $p = 5;  //x location fields on label
        $t = 30; //y location of SKU and vendor info
        $u = 20; //x locaiton of vendor info for label
        $down = 31.0;

        //cycle through result array of query
        while($row = $sql->fetchRow($result)){
           //If $m == 32 add a new page and reset all counters..
           if($m == 32){
              $pdf->AddPage();
              $i = 5;
              $j = 33;
              $l = 32;
              $k = 5;
              $m = 0;
              $n = 20;
              $p = 5;  
              $r = 26;
              $t = 30;
              $u = 20;
           }

           //If $i > 175, start a new line of labels
           if($i > 175){
              $i = 5;
              $j = $j + $down;
              $k = 5;
              $l = $l + $down;
              $n = $n + $down;
              $r = $r + $down;
              $p = 5;
              $u = 20;
              $t = $t + $down;
           }
           $price = $row['normal_price'];
           $desc = strtoupper(substr($row['description'],0,27));
           $brand = ucwords(strtolower(substr($row['brand'],0,13)));
           $pak = $row['units'];
           $size = $row['units'] . "-" . $row['size'];
           $sku = $row['sku'];
           $upc = ltrim($row['upc'],0);
           $check = $pdf->GetCheckDigit($upc);
           $tagdate = date('m/d/y');
           $vendor = substr($row['vendor'],0,7);
           
           //Start laying out a label 
           $pdf->SetFont('Arial','',8);  //Set the font 

           $words = preg_split("/[ ,-]/",$desc);
           $limit = 13;
           $lineheight = 0;
           $curStr = "";
           foreach($words as $w){
            if (strlen($curStr." ".$w) <= $limit)
                $curStr .= " ".$w;
            else {
                $pdf->TEXT($p,$n+$lineheight,$curStr);
                $curStr = "";
                $lineheight += 3;
            }
           }
           $pdf->TEXT($p,$n+$lineheight,$curStr);
           
           //$pdf->TEXT($p,$n,$desc);   //Add description to label

           $pdf->TEXT($p,$r,$tagdate);  //Add date to lable
           $pdf->TEXT($p+12,$r,$size);  //Add size to label
           $pdf->SetFont('Arial','B',18); //change font for price
           $pdf->TEXT($k,$l,$price);  //add price

           $newUPC = $upc . $check; //add check digit to upc
           $pdf->UPC_A($i,$j,$upc,7,.23);  //generate barcode and place on label

           //increment counters    
           $i =$i+ 52.7;
           $k = $k + 52.7;
           $m = $m + 1;
           $p = $p + 52.7;
           $u = $u + 52.7;
        }

        $pdf->Output();  //Output PDF file to screen.
    }
}    

