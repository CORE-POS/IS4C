<?php

include('../../config.php');
require($FANNIE_ROOT.'src/fpdf/fpdf.php');
define('FPDF_FONTPATH',$FANNIE_ROOT.'src/fpdf/font/');

if (isset($_REQUEST['start'])){
	$pdf = new FPDF('P','in','Letter');
	$pdf->SetMargins(0.5,0.5,0.5);
	$pdf->SetAutoPageBreak(False,0.5);
	$pdf->AddPage();

	$start = $_REQUEST['start'];
	$x = 0.5;
	$y = 0.5;
	$pdf->AddFont('Scala-Bold','B','Scala-Bold.php');
	$pdf->SetFont('Scala-Bold','B',16);
	for($i=0;$i<40;$i++){
		$current = $start+$i;	
		$pdf->SetXY($x,$y);
		$pdf->Cell(1.75,0.5,$current,0,0,'C');
		$pdf->Cell(1.75,0.5,$current,0,0,'C');
		if ($i % 2 == 0) $x += (1.75*2)+0.5;
		else {
			$x = 0.5;
			$y += 0.5;
		}
	}
	$pdf->Close();
	$pdf->Output("mem stickers $start.pdf","I");
}
else {
	$page_title='Fannie - Print Member Stickers';
	$header='Print Member Stickers';
	include($FANNIE_ROOT.'src/header.html');
	echo '<form action="index.php" method="get">
		<p>
		Generate a sheet of member stickers<br />
		Format: Avery 5267<br />
		Starting member number:
		<input type="text" name="start" size="6" /><br />
		<br />
		<input type="submit" value="Get PDF" />
		</p>
		</form>';
	include($FANNIE_ROOT.'src/footer.html');
}

?>
