<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
require($FANNIE_ROOT.'src/fpdf/fpdf.php');
define('FPDF_FONTPATH',$FANNIE_ROOT.'src/fpdf/font/');

class MemberStickerPage extends FanniePage {

	protected $title='Fannie - Print Member Stickers';
	protected $header='Print Member Stickers';

	function preprocess(){
		if (FormLib::get_form_value('start',False) !== False){
			$pdf = new FPDF('P','in','Letter');
			$pdf->SetMargins(0.5,0.5,0.5);
			$pdf->SetAutoPageBreak(False,0.5);
			$pdf->AddPage();

			$start = FormLib::get_form_value('start');
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

			return False;
		}
		return True;
	}

	function body_content(){
		return '<form action="MemberStickerPage.php" method="get">
		<p>
		Generate a sheet of member stickers<br />
		Format: Avery 5267<br />
		Starting member number:
		<input type="text" name="start" size="6" /><br />
		<br />
		<input type="submit" value="Get PDF" />
		</p>
		</form>';
	}
}

FannieDispatch::conditionalExec(false);

