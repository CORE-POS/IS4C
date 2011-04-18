<?php
/*
Bitmap.class.php
version 1 (2009-09-24)
author Alex Frase

References
	http://en.wikipedia.org/wiki/BMP_file_format
	http://atlc.sourceforge.net/bmp.html
	http://en.wikipedia.org/wiki/Bresenham%27s_line_algorithm#Optimization
*/

class Bitmap {
	
	var $DIB1;
	var $DIB2;
	var $DIB3;
	var $DIB4;
	var $DIB5;
	
	var $error;
	
	var $magic;
	var $dibVersion;
	var $width;
	var $height;
	var $bpp; // bits per pixel
	var $hppm; // horiz. pixels per meter
	var $vppm; // vert. pixels per meter
	var $palSize;
	var $palSizeImp;
	var $palette;
	var $image;
	var $rowBytes;
	
	
	/*
	* INTERNAL METHODS
	*/
	
	function ReturnError($err=null, $ret=null) {
		$this->error = is_string($err) ? $err : "Unknown error";
		return $ret;
	} // ReturnError()
	
	function ParseInt($data, $left=0, $right=-1, $signed=false, $bigEndian=false) {
		if (is_string($data)) {
			$isarray = false;
			if ($right < 0)
				$right = strlen($data) - 1;
		} else if (is_array($data)) {
			$isarray = true;
			$data = array_values($data);
			if ($right < 0)
				$right = count($data) - 1;
		} else {
			return null;
		}
		if ($right < $left)
			return null;
		// set traversal range and direction
		$start = $bigEndian ? $right : $left;
		$end   = $bigEndian ? $left  : $right;
		$delta = $bigEndian ? -1     : 1;
		// process bytes
		$n = $start;
		$val = $isarray ? (int)$data[$n] : ord($data[$n]);
		$factor = 256;
		while ($n != $end) {
			$n += $delta;
			$val += $factor * ($isarray ? (int)$data[$n] : ord($data[$n]));
			$factor <<= 8; // *= 256
		}
		// check sign bit
		if ($signed && (($isarray ? (int)$data[$end] : ord($data[$end])) & 0x80))
			$val = -$factor + $val;
		// done
		return $val;
	} // ParseInt()
	
	function RenderInt($val, $bytes, $signed=false, $bigEndian=false) {
		$val = (int)$val;
		$bytes = (int)$bytes;
		$range = pow(2, $bytes * 8);
		$neg = false;
		if ($signed && $val < 0)
			$val = (~ (-$val)) + 1;
		// set traversal range and direction
		$start = $bigEndian ? ($bytes - 1) : 0;
		$end   = $bigEndian ? 0            : ($bytes - 1);
		$delta = $bigEndian ? -1           : 1;
		// set bytes
		$data = str_repeat("\x00", $bytes);
		for ($n = $start;  $n != $end;  $n += $delta) {
			$data[$n] = chr($val & 0xFF);
			$val >>= 8; // /= 256
		}
		// done
		return $data;
	} // RenderInt()
	
	
	function LastError($reset=true) {
		$err = $this->error;
		if ($reset)
			$this->error = null;
		return $err;
	} // LastError()
	
	function Load($filename, $filedata=null) {
		$data = "";
		if ($filename === true) {
			$data = $filedata;
		} else {
			$data = file_get_contents($filename);
			if (!$data)
				return $this->ReturnError("Load(): failed reading file \"".$filename."\"");
		}
		$datasize = strlen($data);
		
		// read the BMP header
		
		if ($datasize < 18)
			return $this->ReturnError("Load(): incomplete BMP header (file is ".$datasize." bytes)");
		$magic = substr($data, 0, 2);
		$fileSize = $this->ParseInt($data, 2, 5);
		// bytes 6-9 are unused (application specific)
		$imageAt = $this->ParseInt($data, 10, 13);
		
		// validate the BMP header
		
		switch ($magic) {
		case "BM": // Windows 3.1x, 95, NT, ...
		case "BA": // OS/2 Bitmap Array
		case "CI": // OS/2 Color Icon
		case "CP": // OS/2 Color Pointer
		case "IC": // OS/2 Icon
		case "PT": // OS/2 Pointer
			break;
		default:
			return $this->ReturnError("Load(): unknown magic numbers \"".dechex(ord($magic[0]))." ".dechex(ord($magic[1]))."\"");
		}
		if ($fileSize != $datasize)
			return $this->ReturnError("Load(): incorrect file size (".$fileSize." reported, ".$datasize." actual)");
		
		// read the DIB header
		
		$headerSize = $this->ParseInt($data, 14, 17);
		if ($datasize < (14 + $headerSize))
			return $this->ReturnError("Load(): incomplete DIB header (file is ".$datasize." bytes)");
		switch ($headerSize) {
		case $this->DIB1: // OS/2 V1 "BITMAPCOREHEADER" 12 bytes
			$width = $this->ParseInt($data, 18, 19);
			$height = $this->ParseInt($data, 20, 21);
			$colorPlanes = $this->ParseInt($data, 22, 23);
			$bpp = $this->ParseInt($data, 24, 25);
			if ($bpp == 16 || $bpp == 32) // not supported in this header
				return $this->ReturnError("Load(): ".$bpp." bits-per-pixel invalid in V1 header");
			$compression = 0;
			$imgDataSize = null;
			$hppm = null;
			$vppm = null;
			$palSize = 0;
			$palSizeImp = 0;
			break;
			
		case $this->DIB3: // Windows V3 "BITMAPINFOHEADER" 40 bytes
			$width = $this->ParseInt($data, 18, 21, true);
			$height = $this->ParseInt($data, 22, 25, true);
			$colorPlanes = $this->ParseInt($data, 26, 27);
			$bpp = $this->ParseInt($data, 28, 29);
			$compression = $this->ParseInt($data, 30, 33);
			$imgDataSize = $this->ParseInt($data, 34, 37);
			$hppm = $this->ParseInt($data, 38, 41, true);
			$vppm = $this->ParseInt($data, 42, 45, true);
			$palSize = $this->ParseInt($data, 46, 49);
			$palSizeImp = $this->ParseInt($data, 50, 53);
			break;
			
		// TODO: more header formats
			
		default:
			return $this->ReturnError("Load(): unknown DIB header size (".$headerSize." bytes)");
		}
		
		// validate the DIB header
		
		if ($width < 1)
			return $this->ReturnError("Load(): invalid image width ".$width);
		if ($height == 0) // height can be negative, meaning data is top-to-bottom instead of bottom-to-top
			return $this->ReturnError("Load(): invalid image height ".$height);
		if ($colorPlanes != 1)
			return $this->ReturnError("Load(): invalid color plane count ".$colorPlanes);
		if ($bpp != 1 && $bpp != 4 && $bpp != 8 && $bpp != 16 && $bpp != 24 && $bpp != 32)
			return $this->ReturnError("Load(): invalid bits-per-pixel ".$bpp);
		switch ($compression) {
		case 0: // BI_RGB (uncompressed)
			break;
		case 1: // BI_RLE8 (RLE; 8 bpp only)
		case 2: // BI_RLE4 (RLE; 4 bpp only)
		case 3: // BI_BITFIELDS (bitfield; 16 and 32 bpp only)
		case 4: // BI_JPEG (JPEG)
		case 5: // BI_PNG (PNG)
			// TODO: support compression?
			return $this->ReturnError("Load(): image data compression not supported");
		default:
			return $this->ReturnError("Load(): invalid compression code ".$compression);
		}
		$rowDataSize = (int)((($width * $bpp) + 31) / 32) * 4;
		if ($imgDataSize === null || $imgDataSize === 0)
			$imgDataSize = abs($height) * $rowDataSize;
		else if ($imgDataSize != (abs($height) * $rowDataSize)){
			while(ord($data[strlen($data)-1])===0){
				$imgDataSize--;
				$data = substr($data,0,strlen($data)-1);
			}
			if($imgDataSize != (abs($height) * $rowDataSize))
				return $this->ReturnError("Load(): incorrect image data size (".$imgDataSize." reported, ".(abs($height) * $rowDataSize)." expected)");
		}
		if ($hppm !== null && $hppm <= 0)
			return $this->ReturnError("Load(): invalid horizontal pixels-per-meter ".$hppm);
		if ($vppm !== null && $vppm <= 0)
			return $this->ReturnError("Load(): invalid vertical pixels-per-meter ".$hppm);
		
		// read the palette
		
		if (!$palSize && $bpp < 16)
			$palSize = pow(2, $bpp);
		if ($palSize) {
			$palColorSize = ($headerSize == $this->DIB1) ? 3 : 4;
			$palDataSize = $palSize * $palColorSize;
			if ($datasize < (14 + $headerSize + $palDataSize))
				return $this->ReturnError("Load(): incomplete palette (file is ".$datasize." bytes)");
			$palette = substr($data, 14 + $headerSize, $palDataSize);
			if ($palColorSize < 4) {
				// pad each palette color to 4 bytes for simpler lookup (remember "." doesn't match newline, hence "|\\n")
				$palette = preg_replace('/(.|\\n){'.$palColorSize.'}/', '\\1'.str_repeat("\x00",(4-$palColorSize)), $palette);
			}
		} else {
			$palColorSize = 0;
			$palDataSize = 0;
			$palette = null;
		}
		
		// read the image
		
		if ($datasize < ($imageAt + $imgDataSize))
			return $this->ReturnError("Load(): incomplete image (file is ".$datasize." bytes)");
		$image = substr($data, $imageAt, $imgDataSize);
		$rowBytes = (int)((($width * $bpp) + 7) / 8);
		if ($rowBytes > $rowDataSize)
			return $this->ReturnError("Load(): consistency error (calculated byte width ".$rowBytes.", data width ".$rowDataSize.")");
		if ($rowBytes < $rowDataSize) {
			// strip off word-alignment padding (remember "." doesn't match newline, hence "|\\n")
			$image = preg_replace('/((?:.|\\n){'.$rowBytes.'})((?:.|\\n){'.($rowDataSize-$rowBytes).'})/', '\\1', $image);
		}
		if ($height < 0) {
			$height = abs($height);
		} else {
			// flip image rows vertically (BMPs are stored upside-down by default)
			$str_split = array();
			$val = "";
			for($i=0; $i<strlen($image); $i++){
				if ($i % $rowBytes == 0){
					if (strlen($val) > 0)
						$str_split[] = $val;
					$val = "";
				}
				$val .= $image[$i];
			}
			if (strlen($val) > 0)
				$str_split[] = $val;
			$image = implode('', array_reverse($str_split));
		}
		
		// initialize the object
		
		$this->magic = $magic;
		$this->dibVersion = $headerSize;
		$this->width = $width;
		$this->height = $height;
		$this->bpp = $bpp;
		$this->rowBytes = $rowBytes;
		$this->hppm = $hppm;
		$this->vppm = $vppm;
		$this->palSize = $palSize;
		$this->palSizeImp = $palSizeImp;
		$this->palette = $palette;
		$this->image = $image;
		
		// all done
		return true;
	} // Load()
	
	
	/*
	* OBJECT METHODS
	*/
	
	function Bitmap($width=1, $height=1, $bpp=1, $dpi=72) {
		$this->DIB1 = 12;
		$this->DIB2 = 64;
		$this->DIB3 = 40;
		$this->DIB4 = 108;
		$this->DIB5 = 124;

		$this->error = null;
		
		if (!is_numeric($width) || (int)$width < 1)
			die('Bitmap width must be at least 1');
		if (!is_numeric($height) || (int)$height < 1)
			die('Bitmap height must be at least 1');
		if (!is_numeric($bpp) || (int)$bpp != 1)
			die('Color bitmaps not yet supported');
		if (!is_numeric($dpi) || (int)$dpi < 1)
			die('Bitmap DPI must be at least 1');
		$this->magic = "BM";
		$this->dibVersion = $this->DIB3;
		$this->width = (int)$width;
		$this->height = (int)$height;
		$this->bpp = (int)$bpp;
		$this->rowBytes = (int)((($this->width * $this->bpp) + 7) / 8);
		$this->hppm = (int)(($dpi * 39.37) + 0.5); // 39.37 inches per meter
		$this->vppm = $this->hppm;
		$this->palSize = 2;
		$this->palSizeImp = 0;
		$this->palette = "\xFF\xFF\xFF\x00\x00\x00\x00\x00";
		$rowBytes = (int)((($this->width * $this->bpp) + 7) / 8);
		$this->image = str_repeat("\x00", $rowBytes * $this->height);
	} // __construct()
	
	function Save($filename) {
		// prepare the image
		$rowBytes = (int)((($this->width * $this->bpp) + 7) / 8);
		$rowDataSize = (int)((($this->width * $this->bpp) + 31) / 32) * 4;
		if ($rowBytes > $rowDataSize)
			return $this->ReturnError("Save(): consistency error (calculated byte width ".$rowBytes.", data width ".$rowDataSize.")");
		$pad = str_repeat("\x00", $rowDataSize - $rowBytes); // might be 0 -> "" pad, which is ok
		$image = implode($pad, array_reverse(str_split($this->image, $rowBytes))) . $pad;
		$imgDataSize = $this->height * $rowDataSize;
		if ($imgDataSize != strlen($image))
			return $this->ReturnError("Save(): consistency error (calculated image data size ".$imgDataSize.", prepared ".strlen($image).")");
		
		// prepare the palette
		if ($this->palSize) {
			$palette = $this->palette;
			// if ($this->dibVersion == self::DIB1)  // strip padding...
		} else {
			$palette = "";
		}
		$palDataSize = strlen($palette);
		
		// prepare the DIB header
		$dibHead = "";
		switch ($this->dibVersion) {
		case $this->DIB1: // OS/2 V1 "BITMAPCOREHEADER" 12 bytes
			return $this->ReturnError("Save(): only DIB3 is supported for writing");
			
		case $this->DIB3: // Windows V3 "BITMAPINFOHEADER" 40 bytes
			$dibHead .= $this->RenderInt($this->DIB3, 4);
			$dibHead .= $this->RenderInt($this->width, 4, true);
			$dibHead .= $this->RenderInt($this->height, 4, true);
			$dibHead .= $this->RenderInt(1, 2); // colorPlanes
			$dibHead .= $this->RenderInt($this->bpp, 2);
			$dibHead .= $this->RenderInt(0, 4); // compression
			$dibHead .= $this->RenderInt($imgDataSize, 4);
			$dibHead .= $this->RenderInt($this->hppm, 4, true);
			$dibHead .= $this->RenderInt($this->vppm, 4, true);
			$dibHead .= $this->RenderInt($this->palSize, 4);
			$dibHead .= $this->RenderInt($this->palSizeImp, 4);
			break;
			
		// TODO: more header formats
			
		default:
			return $this->ReturnError("Save(): only DIB3 is supported for writing");
		}
		if (strlen($dibHead) != $this->dibVersion)
			return $this->ReturnError("Save(): consistency error (calculated DIB size ".$this->dibVersion.", prepared ".strlen($dibHead).")");
		
		// prepare the BMP header
		$imageAt = 14 + $this->dibVersion + $palDataSize;
		$fileSize = $imageAt + $imgDataSize;
		$bmpHead = "";
		$bmpHead .= $this->magic;
		$bmpHead .= $this->RenderInt($fileSize, 4);
		$bmpHead .= $this->RenderInt(0, 4); // bytes 6-9 are unused (application specific)
		$bmpHead .= $this->RenderInt($imageAt, 4);
		
		// write or return the file
		$data = $bmpHead . $dibHead . $palette . $image;
		if ($filename === true)
			return $data;
		$bytes = file_put_contents($filename, $data);
		if ($bytes != strlen($data))
			return $this->ReturnError("Save(): failed writing file \"".$filename."\" (".$bytes." of ".strlen($data)." bytes written)");
		return true;
	} // Save()
	
	function GetWidth() { return $this->width; }
	
	function GetHeight() { return $this->height; }
	
	function GetColorDepth() { return $this->bpp; }
	
	function GetHorizResolution($asDPI=false) { return $asDPI ? (int)(($this->hppm / 39.37) + 0.5) : $this->hppm; }
	
	function GetVertResolution($asDPI=false) { return $asDPI ? (int)(($this->vppm / 39.37) + 0.5) : $this->vppm; }
	
	function GetPaletteSize() { return ($this->bpp < 16 && $this->palSize) ? $this->palSize : null; }
	
	function GetPaletteColor($index, $channel=null) {
		if (!$this->palSize || $this->palette === null || $index < 0 || $index >= $this->palSize)
			return null;
		$byte = $index << 2; // (int)($index * 4)
		if ($channel !== null) {
			if ($channel < 0 || $channel > 2)
				return null;
			return ord($this->palette[$byte + $channel]);
		}
		return $this->ParseInt($this->palette, $byte, $byte + 2);
	} // GetPaletteColor()
	
	function GetPixelValue($x, $y) {
		// validate coordinates
		if ($x < 0)
			$x += $this->width;
		else if ($x >= $this->width)
			return null;
		if ($y < 0)
			$y += $this->height;
		else if ($y >= $this->height)
			return null;
		// fetch pixel
		switch ($this->bpp) {
		case 32:
			$byte = ($y * $this->rowBytes) + ($x << 2); // (int)($x * 4)
			return $this->ParseInt($this->image, $byte, $byte + 2); // BMPs don't actually have an alpha channel, so ignore the 4th byte
		case 24:
			$byte = ($y * $this->rowBytes) + ((int)($x * 3));
			return $this->ParseInt($this->image, $byte, $byte + 2);
		case 16:
			$byte = ($y * $this->rowBytes) + ($x << 1); // (int)($x * 2)
			return $this->ParseInt($this->image, $byte, $byte + 1);
		case 8:
			$byte = ($y * $this->rowBytes) + $x;
			return ord($this->image[$byte]);
		case 4:
			$byte = ($y * $this->rowBytes) + ($x >> 1); // (int)($x / 2)
			$shift = 1 - ($x % 2);
			return ((ord($this->image[$byte]) >> $shift) & 0x0F);
		case 1:
			$byte = ($y * $this->rowBytes) + ($x >> 3); // (int)($x / 8)
			$shift = 7 - ($x % 8);
			return ((ord($this->image[$byte]) >> $shift) & 0x01);
		}
		return null;
	} // GetPixelValue()
	
	function SetPixelValue($x, $y, $val) {
		// validate coordinates
		if ($x < 0)
			$x += $this->width;
		else if ($x >= $this->width)
			return null;
		if ($y < 0)
			$y += $this->height;
		else if ($y >= $this->height)
			return null;
		// validate new pixel value
		if ($val < 0 || $val >= pow(2, $this->bpp) || ($this->palSize && $val >= $this->palSize))
			return null;
		// set pixel
		switch ($this->bpp) {
		case 32:
			$byte = ($y * $this->rowBytes) + ($x << 2); // (int)($x * 4)
			$this->image = substr_replace($this->image, $this->RenderInt($val,3), $byte, 3); // BMPs don't actually have an alpha channel, so ignore the 4th byte
			return true;
		case 24:
			$byte = ($y * $this->rowBytes) + ((int)($x * 3));
			$this->image = substr_replace($this->image, $this->RenderInt($val,3), $byte, 3);
			return true;
		case 16:
			$byte = ($y * $this->rowBytes) + ($x << 1); // (int)($x * 2)
			$this->image = substr_replace($this->image, $this->RenderInt($val,2), $byte, 2);
			return true;
		case 8:
			$byte = ($y * $this->rowBytes) + $x;
			$this->image[$byte] = chr($val);
			return true;
		case 4:
			$byte = ($y * $this->rowBytes) + ($x >> 1); // (int)($x / 2)
			$shift = 1 - ($x % 2);
			$mask = (0x0F << $shift);
			$this->image[$byte] = chr( (ord($this->image[$byte]) & (~ $mask)) | ($val & $mask) );
			return true;
		case 1:
			$byte = ($y * $this->rowBytes) + ($x >> 3); // (int)($x / 8)
			$shift = 7 - ($x % 8);
			if ($val)
				$this->image[$byte] = chr( ord($this->image[$byte]) | (0x01 << $shift) );
			else
				$this->image[$byte] = chr( ord($this->image[$byte]) & (0xFF ^ (0x01 << $shift)) );
			return true;
		}
		return null;
	} // SetPixelValue()
	
	function DrawLine($x0, $y0, $x1, $y1, $val) {
		// validate coordinates
		if ($x0 < 0)
			$x0 += $this->width;
		else if ($x0 >= $this->width)
			return null;
		if ($y0 < 0)
			$y0 += $this->height;
		else if ($y0 >= $this->height)
			return null;
		if ($x1 < 0)
			$x1 += $this->width;
		else if ($x1 >= $this->width)
			return null;
		if ($y1 < 0)
			$y1 += $this->height;
		else if ($y1 >= $this->height)
			return null;
		// validate new pixel(s) value
		if ($val < 0 || $val >= pow(2, $this->bpp) || ($this->palSize && $val >= $this->palSize))
			return null;
		// draw!
		// http://en.wikipedia.org/wiki/Bresenham%27s_line_algorithm#Optimization
		$steep = (abs($y1 - $y0) > abs($x1 - $x0));
		if ($steep) {
			$t=$x0; $x0=$y0; $y0=$t; // swap $x0,$y0
			$t=$x1; $x1=$y1; $y1=$t; // swap $x1,$y1
		}
		if ($x0 > $x1) {
			$t=$x0; $x0=$x1; $x1=$t; // swap $x0,$x1
			$t=$y0; $y0=$y1; $y1=$t; // swap $y0,$y1
		}
		$dx = ($x1 - $x0);
		$dy = abs($y1 - $y0);
		$err = $dx >> 1;
		$sy = ($y0 < $y1) ? 1 : -1;
		while ($x0 <= $x1) {
			if ($steep)
				$this->SetPixelValue($y0, $x0, 1);
			else
				$this->SetPixelValue($x0, $y0, 1);
			$err -= $dy;
			if ($err < 0) {
				$y0 += $sy;
				$err += $dx;
			}
			$x0++;
		}
		return true;
	} // DrawLine()
	
	function GetPixelColor($x, $y, $channel=null) {
		$val = $this->GetPixelValue($x, $y);
		if ($val !== null && $this->palette !== null)
			return $this->GetPaletteColor($val, $channel);
		if ($this->bpp == 16) {
			$val =
				(((((($val >> 10) & 0x1F) / 0x1F) * 0xFF) + 0.5) << 16)
				+ (((((($val >> 5) & 0x1F) / 0x1F) * 0xFF) + 0.5) << 8)
				+ (int)(((($val & 0x1F) / 0x1F) * 0xFF) + 0.5);
		}
		return $val;
	} // GetPixelColor()
	
	function GetRawData() {
		return $this->image;
	} // GetRawData()
	
	function GetRawBytesPerRow() {
		return $this->rowBytes;
	} // GetRawBytesPerRow()
	
} // Bitmap

function RenderBitmapFromFile($fn){
	$slip = "";

	$bmp = new Bitmap();
	$bmp->Load($fn);

	$bmpData = $bmp->GetRawData();
	$bmpWidth = $bmp->GetWidth();
	$bmpHeight = $bmp->GetHeight();
	$bmpRawBytes = (int)(($bmpWidth + 7)/8);

	$printer = new ESCPOSPrintHandler();
	$stripes = $printer->TransposeBitmapData($bmpData, $bmpWidth);
	for($i=0; $i<count($stripes); $i++)
		$stripes[$i] = $printer->InlineBitmap($stripes[$i], $bmpWidth);

	$slip .= $printer->AlignCenter();
	if (count($stripes) > 1)
		$slip .= $printer->LineSpacing(0);
	$slip .= implode("\n",$stripes);
	if (count($stripes) > 1)
		$slip .= $printer->ResetLineSpacing()."\n";
	$slip .= $printer->AlignLeft();

	return $slip;
}

