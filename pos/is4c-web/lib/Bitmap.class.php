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
	
	const DIB1 = 12;
	const DIB2 = 64;
	const DIB3 = 40;
	const DIB4 = 108;
	const DIB5 = 124;
	
	private static $error = null;
	
	private $magic;
	private $dibVersion;
	private $width;
	private $height;
	private $bpp; // bits per pixel
	private $hppm; // horiz. pixels per meter
	private $vppm; // vert. pixels per meter
	private $palSize;
	private $palSizeImp;
	private $palette;
	private $image;
	private $rowBytes;
	
	
	/*
	* INTERNAL METHODS
	*/
	
	private static function ReturnError($err=null, $ret=null) {
		self::$error = is_string($err) ? $err : "Unknown error";
		return $ret;
	} // ReturnError()
	
	private static function ParseInt($data, $left=0, $right=-1, $signed=false, $bigEndian=false) {
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
	
	private static function RenderInt($val, $bytes, $signed=false, $bigEndian=false) {
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
	
	
	/*
	* CLASS METHODS
	*/
	
	static function LastError($reset=true) {
		$err = self::$error;
		if ($reset)
			self::$error = null;
		return $err;
	} // LastError()
	
	static function Load($filename, $filedata=null) {
		if ($filename === true) {
			$data = $filedata;
		} else {
			$data = file_get_contents($filename, FILE_BINARY);
			if (!$data)
				return self::ReturnError("Load(): failed reading file \"".$filename."\"");
		}
		$datasize = strlen($data);
		
		// read the BMP header
		
		if ($datasize < 18)
			return self::ReturnError("Load(): incomplete BMP header (file is ".$datasize." bytes)");
		$magic = substr($data, 0, 2);
		$fileSize = self::ParseInt($data, 2, 5);
		// bytes 6-9 are unused (application specific)
		$imageAt = self::ParseInt($data, 10, 13);
		
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
			return self::ReturnError("Load(): unknown magic numbers \"".dechex(ord($magic[0]))." ".dechex(ord($magic[1]))."\"");
		}
		if ($fileSize != $datasize)
			return self::ReturnError("Load(): incorrect file size (".$fileSize." reported, ".$datasize." actual)");
		
		// read the DIB header
		
		$headerSize = self::ParseInt($data, 14, 17);
		if ($datasize < (14 + $headerSize))
			return self::ReturnError("Load(): incomplete DIB header (file is ".$datasize." bytes)");
		switch ($headerSize) {
		case self::DIB1: // OS/2 V1 "BITMAPCOREHEADER" 12 bytes
			$width = self::ParseInt($data, 18, 19);
			$height = self::ParseInt($data, 20, 21);
			$colorPlanes = self::ParseInt($data, 22, 23);
			$bpp = self::ParseInt($data, 24, 25);
			if ($bpp == 16 || $bpp == 32) // not supported in this header
				return self::ReturnError("Load(): ".$bpp." bits-per-pixel invalid in V1 header");
			$compression = 0;
			$imgDataSize = null;
			$hppm = null;
			$vppm = null;
			$palSize = 0;
			$palSizeImp = 0;
			break;
			
		case self::DIB3: // Windows V3 "BITMAPINFOHEADER" 40 bytes
			$width = self::ParseInt($data, 18, 21, true);
			$height = self::ParseInt($data, 22, 25, true);
			$colorPlanes = self::ParseInt($data, 26, 27);
			$bpp = self::ParseInt($data, 28, 29);
			$compression = self::ParseInt($data, 30, 33);
			$imgDataSize = self::ParseInt($data, 34, 37);
			$hppm = self::ParseInt($data, 38, 41, true);
			$vppm = self::ParseInt($data, 42, 45, true);
			$palSize = self::ParseInt($data, 46, 49);
			$palSizeImp = self::ParseInt($data, 50, 53);
			break;
			
		// TODO: more header formats
			
		default:
			return self::ReturnError("Load(): unknown DIB header size (".$headerSize." bytes)");
		}
		
		// validate the DIB header
		
		if ($width < 1)
			return self::ReturnError("Load(): invalid image width ".$width);
		if ($height == 0) // height can be negative, meaning data is top-to-bottom instead of bottom-to-top
			return self::ReturnError("Load(): invalid image height ".$height);
		if ($colorPlanes != 1)
			return self::ReturnError("Load(): invalid color plane count ".$colorPlanes);
		if ($bpp != 1 && $bpp != 4 && $bpp != 8 && $bpp != 16 && $bpp != 24 && $bpp != 32)
			return self::ReturnError("Load(): invalid bits-per-pixel ".$bpp);
		switch ($compression) {
		case 0: // BI_RGB (uncompressed)
			break;
		case 1: // BI_RLE8 (RLE; 8 bpp only)
		case 2: // BI_RLE4 (RLE; 4 bpp only)
		case 3: // BI_BITFIELDS (bitfield; 16 and 32 bpp only)
		case 4: // BI_JPEG (JPEG)
		case 5: // BI_PNG (PNG)
			// TODO: support compression?
			return self::ReturnError("Load(): image data compression not supported");
		default:
			return self::ReturnError("Load(): invalid compression code ".$compression);
		}
		$rowDataSize = (int)((($width * $bpp) + 31) / 32) * 4;
		if ($imgDataSize === null)
			$imgDataSize = abs($height) * $rowDataSize;
		else if ($imgDataSize != (abs($height) * $rowDataSize))
			return self::ReturnError("Load(): incorrect image data size (".$imgDataSize." reported, ".(abs($height) * $rowDataSize)." expected)");
		if ($hppm !== null && $hppm <= 0)
			return self::ReturnError("Load(): invalid horizontal pixels-per-meter ".$hppm);
		if ($vppm !== null && $vppm <= 0)
			return self::ReturnError("Load(): invalid vertical pixels-per-meter ".$hppm);
		
		// read the palette
		
		if (!$palSize && $bpp < 16)
			$palSize = pow(2, $bpp);
		if ($palSize) {
			$palColorSize = ($headerSize == self::DIB1) ? 3 : 4;
			$palDataSize = $palSize * $palColorSize;
			if ($datasize < (14 + $headerSize + $palDataSize))
				return self::ReturnError("Load(): incomplete palette (file is ".$datasize." bytes)");
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
			return self::ReturnError("Load(): incomplete image (file is ".$datasize." bytes)");
		$image = substr($data, $imageAt, $imgDataSize);
		$rowBytes = (int)((($width * $bpp) + 7) / 8);
		if ($rowBytes > $rowDataSize)
			return self::ReturnError("Load(): consistency error (calculated byte width ".$rowBytes.", data width ".$rowDataSize.")");
		if ($rowBytes < $rowDataSize) {
			// strip off word-alignment padding (remember "." doesn't match newline, hence "|\\n")
			$image = preg_replace('/((?:.|\\n){'.$rowBytes.'})((?:.|\\n){'.($rowDataSize-$rowBytes).'})/', '\\1', $image);
		}
		if ($height < 0) {
			$height = abs($height);
		} else {
			// flip image rows vertically (BMPs are stored upside-down by default)
			$image = implode('', array_reverse(str_split($image, $rowBytes)));
		}
		
		// initialize the object
		
		$bmp = new self();
		$bmp->magic = $magic;
		$bmp->dibVersion = $headerSize;
		$bmp->width = $width;
		$bmp->height = $height;
		$bmp->bpp = $bpp;
		$bmp->rowBytes = $rowBytes;
		$bmp->hppm = $hppm;
		$bmp->vppm = $vppm;
		$bmp->palSize = $palSize;
		$bmp->palSizeImp = $palSizeImp;
		$bmp->palette = $palette;
		$bmp->image = $image;
		
		// all done
		return $bmp;
	} // Load()
	
	
	/*
	* OBJECT METHODS
	*/
	
	function __construct($width=1, $height=1, $bpp=1, $dpi=72) {
		if (!is_numeric($width) || (int)$width < 1)
			throw new Exception('Bitmap width must be at least 1');
		if (!is_numeric($height) || (int)$height < 1)
			throw new Exception('Bitmap height must be at least 1');
		if (!is_numeric($bpp) || (int)$bpp != 1)
			throw new Exception('Color bitmaps not yet supported');
		if (!is_numeric($dpi) || (int)$dpi < 1)
			throw new Exception('Bitmap DPI must be at least 1');
		$this->magic = "BM";
		$this->dibVersion = self::DIB3;
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
			return self::ReturnError("Save(): consistency error (calculated byte width ".$rowBytes.", data width ".$rowDataSize.")");
		$pad = str_repeat("\x00", $rowDataSize - $rowBytes); // might be 0 -> "" pad, which is ok
		$image = implode($pad, array_reverse(str_split($this->image, $rowBytes))) . $pad;
		$imgDataSize = $this->height * $rowDataSize;
		if ($imgDataSize != strlen($image))
			return self::ReturnError("Save(): consistency error (calculated image data size ".$imgDataSize.", prepared ".strlen($image).")");
		
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
		case self::DIB1: // OS/2 V1 "BITMAPCOREHEADER" 12 bytes
			return self::ReturnError("Save(): only DIB3 is supported for writing");
			
		case self::DIB3: // Windows V3 "BITMAPINFOHEADER" 40 bytes
			$dibHead .= self::RenderInt(self::DIB3, 4);
			$dibHead .= self::RenderInt($this->width, 4, true);
			$dibHead .= self::RenderInt($this->height, 4, true);
			$dibHead .= self::RenderInt(1, 2); // colorPlanes
			$dibHead .= self::RenderInt($this->bpp, 2);
			$dibHead .= self::RenderInt(0, 4); // compression
			$dibHead .= self::RenderInt($imgDataSize, 4);
			$dibHead .= self::RenderInt($this->hppm, 4, true);
			$dibHead .= self::RenderInt($this->vppm, 4, true);
			$dibHead .= self::RenderInt($this->palSize, 4);
			$dibHead .= self::RenderInt($this->palSizeImp, 4);
			break;
			
		// TODO: more header formats
			
		default:
			return self::ReturnError("Save(): only DIB3 is supported for writing");
		}
		if (strlen($dibHead) != $this->dibVersion)
			return self::ReturnError("Save(): consistency error (calculated DIB size ".$this->dibVersion.", prepared ".strlen($dibHead).")");
		
		// prepare the BMP header
		$imageAt = 14 + $this->dibVersion + $palDataSize;
		$fileSize = $imageAt + $imgDataSize;
		$bmpHead = "";
		$bmpHead .= $this->magic;
		$bmpHead .= self::RenderInt($fileSize, 4);
		$bmpHead .= self::RenderInt(0, 4); // bytes 6-9 are unused (application specific)
		$bmpHead .= self::RenderInt($imageAt, 4);
		
		// write or return the file
		$data = $bmpHead . $dibHead . $palette . $image;
		if ($filename === true)
			return $data;
		$bytes = file_put_contents($filename, $data, FILE_BINARY);
		if ($bytes != strlen($data))
			return self::ReturnError("Save(): failed writing file \"".$filename."\" (".$bytes." of ".strlen($data)." bytes written)");
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
		return self::ParseInt($this->palette, $byte, $byte + 2);
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
			return self::ParseInt($this->image, $byte, $byte + 2); // BMPs don't actually have an alpha channel, so ignore the 4th byte
		case 24:
			$byte = ($y * $this->rowBytes) + ((int)($x * 3));
			return self::ParseInt($this->image, $byte, $byte + 2);
		case 16:
			$byte = ($y * $this->rowBytes) + ($x << 1); // (int)($x * 2)
			return self::ParseInt($this->image, $byte, $byte + 1);
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
			$this->image = substr_replace($this->image, self::RenderInt($val,3), $byte, 3); // BMPs don't actually have an alpha channel, so ignore the 4th byte
			return true;
		case 24:
			$byte = ($y * $this->rowBytes) + ((int)($x * 3));
			$this->image = substr_replace($this->image, self::RenderInt($val,3), $byte, 3);
			return true;
		case 16:
			$byte = ($y * $this->rowBytes) + ($x << 1); // (int)($x * 2)
			$this->image = substr_replace($this->image, self::RenderInt($val,2), $byte, 2);
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

