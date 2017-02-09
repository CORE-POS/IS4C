<?php

namespace COREPOS\pos\lib;
use \Exception;

/*
Bitmap.class.php
version 1 (2009-09-24)
author Alex Frase

References
    http://en.wikipedia.org/wiki/BMP_file_format
    http://atlc.sourceforge.net/bmp.html
    http://en.wikipedia.org/wiki/Bresenham%27s_line_algorithm#Optimization
*/

class Bitmap 
{
    private $DIB1 = 12;
    private $DIB2 = 64;
    private $DIB3 = 40;
    private $DIB4 = 108;
    private $DIB5 = 124;
    
    private $error;
    
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
    
    private function returnError($err=null, $ret=null) 
    {
        $this->error = is_string($err) ? $err : "Unknown error";

        return $ret;
    } // returnError()
    
    private function parseInt($data, $left=0, $right=-1, $signed=false, $bigEndian=false) 
    {
        if (is_string($data)) {
            $isarray = false;
            if ($right < 0) {
                $right = strlen($data) - 1;
            }
        } elseif (is_array($data)) {
            $isarray = true;
            $data = array_values($data);
            if ($right < 0) {
                $right = count($data) - 1;
            }
        } else {
            return null;
        }
        if ($right < $left) {
            return null;
        }
        // set traversal range and direction
        $start = $bigEndian ? $right : $left;
        $end   = $bigEndian ? $left  : $right;
        $delta = $bigEndian ? -1     : 1;
        // process bytes
        $num = $start;
        $val = $isarray ? (int)$data[$num] : ord($data[$num]);
        $factor = 256;
        while ($num != $end) {
            $num += $delta;
            $val += $factor * ($isarray ? (int)$data[$num] : ord($data[$num]));
            $factor <<= 8; // *= 256
        }
        // check sign bit
        if ($signed && (($isarray ? (int)$data[$end] : ord($data[$end])) & 0x80)) {
            $val = -$factor + $val;
        }
        // done
        return $val;
    } // parseInt()
    
    private function renderInt($val, $bytes, $signed=false, $bigEndian=false) 
    {
        $val = (int)$val;
        $bytes = (int)$bytes;
        $range = pow(2, $bytes * 8);
        $neg = false;
        if ($signed && $val < 0) {
            $val = (~ (-$val)) + 1;
        }
        // set traversal range and direction
        $start = $bigEndian ? ($bytes - 1) : 0;
        $end   = $bigEndian ? 0            : ($bytes - 1);
        $delta = $bigEndian ? -1           : 1;
        // set bytes
        $data = str_repeat("\x00", $bytes);
        for ($num = $start;  $num != $end;  $num += $delta) {
            $data[$num] = chr($val & 0xFF);
            $val >>= 8; // /= 256
        }
        // done

        return $data;
    } // renderInt()
    
    
    private function lastError($reset=true) 
    {
        $err = $this->error;
        if ($reset) {
            $this->error = null;
        }
        return $err;
    } // lastError()
    
    public function load($filename, $filedata=null) 
    {
        $data = "";
        if ($filename === true) {
            $data = $filedata;
        } else {
            $data = file_get_contents($filename);
            if (!$data) {
                return $this->returnError("load(): failed reading file \"".$filename."\"");
            }
        }
        $datasize = strlen($data);
        
        // read the BMP header
        
        if ($datasize < 18) {
            return $this->returnError("load(): incomplete BMP header (file is ".$datasize." bytes)");
        }
        $magic = substr($data, 0, 2);
        $fileSize = $this->parseInt($data, 2, 5);
        // bytes 6-9 are unused (application specific)
        $imageAt = $this->parseInt($data, 10, 13);
        
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
                return $this->returnError("load(): unknown magic numbers \"".dechex(ord($magic[0]))." ".dechex(ord($magic[1]))."\"");
        }
        if ($fileSize != $datasize) {
            return $this->returnError("load(): incorrect file size (".$fileSize." reported, ".$datasize." actual)");
        }
        
        // read the DIB header
        
        $headerSize = $this->parseInt($data, 14, 17);
        if ($datasize < (14 + $headerSize)) {
            return $this->returnError("load(): incomplete DIB header (file is ".$datasize." bytes)");
        }
        switch ($headerSize) {
            case $this->DIB1: // OS/2 V1 "BITMAPCOREHEADER" 12 bytes
                $width = $this->parseInt($data, 18, 19);
                $height = $this->parseInt($data, 20, 21);
                $colorPlanes = $this->parseInt($data, 22, 23);
                $bpp = $this->parseInt($data, 24, 25);
                if ($bpp == 16 || $bpp == 32) { // not supported in this header
                    return $this->returnError("load(): ".$bpp." bits-per-pixel invalid in V1 header");
                }
                $compression = 0;
                $imgDataSize = null;
                $hppm = null;
                $vppm = null;
                $palSize = 0;
                $palSizeImp = 0;
                break;
            
            case $this->DIB3: // Windows V3 "BITMAPINFOHEADER" 40 bytes
                $width = $this->parseInt($data, 18, 21, true);
                $height = $this->parseInt($data, 22, 25, true);
                $colorPlanes = $this->parseInt($data, 26, 27);
                $bpp = $this->parseInt($data, 28, 29);
                $compression = $this->parseInt($data, 30, 33);
                $imgDataSize = $this->parseInt($data, 34, 37);
                $hppm = $this->parseInt($data, 38, 41, true);
                $vppm = $this->parseInt($data, 42, 45, true);
                $palSize = $this->parseInt($data, 46, 49);
                $palSizeImp = $this->parseInt($data, 50, 53);
                break;
                
            // TODO: more header formats
            
            default:
                return $this->returnError("load(): unknown DIB header size (".$headerSize." bytes)");
        }
        
        // validate the DIB header
        
        if ($width < 1) {
            return $this->returnError("load(): invalid image width ".$width);
        }
        if ($height == 0) {// height can be negative, meaning data is top-to-bottom instead of bottom-to-top
            return $this->returnError("load(): invalid image height ".$height);
        }
        if ($colorPlanes != 1) {
            return $this->returnError("load(): invalid color plane count ".$colorPlanes);
        }
        if ($bpp != 1 && $bpp != 4 && $bpp != 8 && $bpp != 16 && $bpp != 24 && $bpp != 32) {
            return $this->returnError("load(): invalid bits-per-pixel ".$bpp);
        }

        switch ($compression) {
            case 0: // BI_RGB (uncompressed)
                break;
            case 1: // BI_RLE8 (RLE; 8 bpp only)
            case 2: // BI_RLE4 (RLE; 4 bpp only)
            case 3: // BI_BITFIELDS (bitfield; 16 and 32 bpp only)
            case 4: // BI_JPEG (JPEG)
            case 5: // BI_PNG (PNG)
                // TODO: support compression?
                return $this->returnError("load(): image data compression not supported");
            default:
                return $this->returnError("load(): invalid compression code ".$compression);
        }

        $rowDataSize = (int)((($width * $bpp) + 31) / 32) * 4;
        if ($imgDataSize === null || $imgDataSize === 0) {
            $imgDataSize = abs($height) * $rowDataSize;
        } elseif ($imgDataSize != (abs($height) * $rowDataSize)){
            /** modification by Andy 09Aug13
                I think this makes more sense and it's incorrect
                to assume all zero bytes at the end of the
                image are padding **/
            $padding = $imgDataSize % 4;
            if ($padding > 0) {
                $imgDataSize -= $padding;
                $data = substr($data,0,strlen($data)-$padding);
            }
            /* previous method for removing padding
            while(ord($data[strlen($data)-1])===0){
                $imgDataSize--;
                $data = substr($data,0,strlen($data)-1);
            }
            */
            if($imgDataSize != (abs($height) * $rowDataSize)) {
                return $this->returnError("load(): incorrect image data size (".$imgDataSize." reported, ".(abs($height) * $rowDataSize)." expected)");
            }
        }
        if ($hppm !== null && $hppm <= 0) {
            return $this->returnError("load(): invalid horizontal pixels-per-meter ".$hppm);
        }
        if ($vppm !== null && $vppm <= 0) {
            return $this->returnError("load(): invalid vertical pixels-per-meter ".$hppm);
        }
        
        // read the palette
        
        if (!$palSize && $bpp < 16) {
            $palSize = pow(2, $bpp);
        }
        $palColorSize = 0;
        $palDataSize = 0;
        $palette = null;
        if ($palSize) {
            $palColorSize = ($headerSize == $this->DIB1) ? 3 : 4;
            $palDataSize = $palSize * $palColorSize;
            if ($datasize < (14 + $headerSize + $palDataSize)) {
                return $this->returnError("load(): incomplete palette (file is ".$datasize." bytes)");
            }
            $palette = substr($data, 14 + $headerSize, $palDataSize);
            if ($palColorSize < 4) {
                // pad each palette color to 4 bytes for simpler lookup (remember "." doesn't match newline, hence "|\\n")
                $palette = preg_replace('/(.|\\n){'.$palColorSize.'}/', '\\1'.str_repeat("\x00",(4-$palColorSize)), $palette);
            }
        }
        
        // read the image
        
        if ($datasize < ($imageAt + $imgDataSize)) {
            return $this->returnError("load(): incomplete image (file is ".$datasize." bytes)");
        }
        $image = substr($data, $imageAt, $imgDataSize);
        $rowBytes = (int)((($width * $bpp) + 7) / 8);
        if ($rowBytes > $rowDataSize) {
            return $this->returnError("load(): consistency error (calculated byte width ".$rowBytes.", data width ".$rowDataSize.")");
        }
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
            for($i=0; $i<strlen($image); $i++) {
                if ($i % $rowBytes == 0) {
                    if (strlen($val) > 0) {
                        $str_split[] = $val;
                    }
                    $val = "";
                }
                $val .= $image[$i];
            }
            if (strlen($val) > 0) {
                $str_split[] = $val;
            }
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
    } // load()
    
    
    /*
    * OBJECT METHODS
    */
    
    public function __construct($width=1, $height=1, $bpp=1, $dpi=72) 
    {
        $this->error = null;
        
        if (!is_numeric($width) || (int)$width < 1) {
            throw('Bitmap width must be at least 1');
        }
        if (!is_numeric($height) || (int)$height < 1) {
            throw('Bitmap height must be at least 1');
        }
        if (!is_numeric($bpp) || (int)$bpp != 1) {
            throw('Color bitmaps not yet supported');
        }
        if (!is_numeric($dpi) || (int)$dpi < 1) {
            throw('Bitmap DPI must be at least 1');
        }
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
    
    public function save($filename) {
        // prepare the image
        $rowBytes = (int)((($this->width * $this->bpp) + 7) / 8);
        $rowDataSize = (int)((($this->width * $this->bpp) + 31) / 32) * 4;
        if ($rowBytes > $rowDataSize) {
            return $this->returnError("save(): consistency error (calculated byte width ".$rowBytes.", data width ".$rowDataSize.")");
        }
        $pad = str_repeat("\x00", $rowDataSize - $rowBytes); // might be 0 -> "" pad, which is ok
        $image = implode($pad, array_reverse(str_split($this->image, $rowBytes))) . $pad;
        $imgDataSize = $this->height * $rowDataSize;
        if ($imgDataSize != strlen($image)) {
            return $this->returnError("save(): consistency error (calculated image data size ".$imgDataSize.", prepared ".strlen($image).")");
        }
        
        // prepare the palette
        $palette = "";
        if ($this->palSize) {
            $palette = $this->palette;
            // if ($this->dibVersion == self::DIB1)  // strip padding...
        }
        $palDataSize = strlen($palette);
        
        // prepare the DIB header
        $dibHead = "";
        switch ($this->dibVersion) {
            case $this->DIB1: // OS/2 V1 "BITMAPCOREHEADER" 12 bytes
                return $this->returnError("save(): only DIB3 is supported for writing");
            
            case $this->DIB3: // Windows V3 "BITMAPINFOHEADER" 40 bytes
                $dibHead .= $this->renderInt($this->DIB3, 4);
                $dibHead .= $this->renderInt($this->width, 4, true);
                $dibHead .= $this->renderInt($this->height, 4, true);
                $dibHead .= $this->renderInt(1, 2); // colorPlanes
                $dibHead .= $this->renderInt($this->bpp, 2);
                $dibHead .= $this->renderInt(0, 4); // compression
                $dibHead .= $this->renderInt($imgDataSize, 4);
                $dibHead .= $this->renderInt($this->hppm, 4, true);
                $dibHead .= $this->renderInt($this->vppm, 4, true);
                $dibHead .= $this->renderInt($this->palSize, 4);
                $dibHead .= $this->renderInt($this->palSizeImp, 4);
                break;
            
            // TODO: more header formats
            
            default:
                return $this->returnError("save(): only DIB3 is supported for writing");
        }
        if (strlen($dibHead) != $this->dibVersion) {
            return $this->returnError("save(): consistency error (calculated DIB size ".$this->dibVersion.", prepared ".strlen($dibHead).")");
        }
        
        // prepare the BMP header
        $imageAt = 14 + $this->dibVersion + $palDataSize;
        $fileSize = $imageAt + $imgDataSize;
        $bmpHead = "";
        $bmpHead .= $this->magic;
        $bmpHead .= $this->renderInt($fileSize, 4);
        $bmpHead .= $this->renderInt(0, 4); // bytes 6-9 are unused (application specific)
        $bmpHead .= $this->renderInt($imageAt, 4);
        
        // write or return the file
        $data = $bmpHead . $dibHead . $palette . $image;
        if ($filename === true) {
            return $data;
        }
        $bytes = file_put_contents($filename, $data);
        if ($bytes != strlen($data)) {
            return $this->returnError("save(): failed writing file \"".$filename."\" (".$bytes." of ".strlen($data)." bytes written)");
        }

        return true;
    } // save()
    
    public function getWidth() 
    {
        return $this->width;
    }
    
    public function getHeight()
    {
        return $this->height;
    }
    
    private function getColorDepth()
    {
        return $this->bpp;
    }
    
    private function getHorizResolution($asDPI=false){
        return $asDPI ? (int)(($this->hppm / 39.37) + 0.5) : $this->hppm;
    }
    
    private function getVertResolution($asDPI=false)
    {
        return $asDPI ? (int)(($this->vppm / 39.37) + 0.5) : $this->vppm;
    }
    
    private function getPaletteSize()
    {
        return ($this->bpp < 16 && $this->palSize) ? $this->palSize : null;
    }
    
    private function getPaletteColor($index, $channel=null) 
    {
        if (!$this->palSize || $this->palette === null || $index < 0 || $index >= $this->palSize) {
            return null;
        }
        $byte = $index << 2; // (int)($index * 4)
        if ($channel !== null) {
            if ($channel < 0 || $channel > 2) {
                return null;
            }
            return ord($this->palette[$byte + $channel]);
        }

        return $this->parseInt($this->palette, $byte, $byte + 2);
    } // getPaletteColor()
    
    private function getPixelValue($x, $y) 
    {
        // validate coordinates
        if ($x < 0) {
            $x += $this->width;
        } elseif ($x >= $this->width) {
            return null;
        }
        if ($y < 0) {
            $y += $this->height;
        } elseif ($y >= $this->height) {
            return null;
        }
        // fetch pixel
        switch ($this->bpp) {
            case 32:
                $byte = ($y * $this->rowBytes) + ($x << 2); // (int)($x * 4)
                return $this->parseInt($this->image, $byte, $byte + 2); // BMPs don't actually have an alpha channel, so ignore the 4th byte
            case 24:
                $byte = ($y * $this->rowBytes) + ((int)($x * 3));
                return $this->parseInt($this->image, $byte, $byte + 2);
            case 16:
                $byte = ($y * $this->rowBytes) + ($x << 1); // (int)($x * 2)
                return $this->parseInt($this->image, $byte, $byte + 1);
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
    } // getPixelValue()
    
    private function setPixelValue($x, $y, $val) 
    {
        // validate coordinates
        if ($x < 0) {
            $x += $this->width;
        } elseif ($x >= $this->width) {
            return null;
        }
        if ($y < 0) {
            $y += $this->height;
        } elseif ($y >= $this->height) {
            return null;
        }
        // validate new pixel value
        if ($val < 0 || $val >= pow(2, $this->bpp) || ($this->palSize && $val >= $this->palSize)) {
            return null;
        }
        // set pixel
        switch ($this->bpp) {
            case 32:
                $byte = ($y * $this->rowBytes) + ($x << 2); // (int)($x * 4)
                $this->image = substr_replace($this->image, $this->renderInt($val,3), $byte, 3); // BMPs don't actually have an alpha channel, so ignore the 4th byte
                return true;
            case 24:
                $byte = ($y * $this->rowBytes) + ((int)($x * 3));
                $this->image = substr_replace($this->image, $this->renderInt($val,3), $byte, 3);
                return true;
            case 16:
                $byte = ($y * $this->rowBytes) + ($x << 1); // (int)($x * 2)
                $this->image = substr_replace($this->image, $this->renderInt($val,2), $byte, 2);
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
                if ($val) {
                    $this->image[$byte] = chr( ord($this->image[$byte]) | (0x01 << $shift) );
                } else {
                    $this->image[$byte] = chr( ord($this->image[$byte]) & (0xFF ^ (0x01 << $shift)) );
                }
                return true;
        }

        return null;
    } // setPixelValue()

    private function validateCoord($val, $max)
    {
        if ($val < 0) {
            return $val + $max;
        } elseif ($val >= $max) {
            throw new Exception('Coordinate out of range');
        }
        return $val;
    }
    
    // @hintable
    public function drawLine($pt1, $pt2, $val) 
    {
        // validate coordinates
        try {
            $pt1[0] = $this->validateCoord($pt1[0], $this->width);
            $pt2[0] = $this->validateCoord($pt1[0], $this->width);
            $pt1[1] = $this->validateCoord($pt1[1], $this->height);
            $pt2[1] = $this->validateCoord($pt2[1], $this->height);
        } catch (Exception $ex) {
            return null;
        }
        // validate new pixel(s) value
        if ($val < 0 || $val >= pow(2, $this->bpp) || ($this->palSize && $val >= $this->palSize)) {
            return null;
        }
        // draw!
        // http://en.wikipedia.org/wiki/Bresenham%27s_line_algorithm#Optimization
        $steep = (abs($pt2[1] - $pt1[1]) > abs($pt2[0] - $pt1[0]));
        if ($steep) {
            $tmp=$pt1[0]; $pt1[0]=$pt1[1]; $pt1[1]=$tmp; // swap $pt1[0],$pt1[1]
            $tmp=$pt2[0]; $pt2[0]=$pt2[1]; $pt2[1]=$tmp; // swap $pt2[0],$pt2[1]
        }
        if ($pt1[0] > $pt2[0]) {
            $tmp=$pt1; $pt1=$pt2; $pt2=$tmp; // swap $pt1,$pt2
        }
        $divx = ($pt2[0] - $pt1[0]);
        $divy = abs($pt2[1] - $pt1[1]);
        $err = $divx >> 1;
        $s_y = ($pt1[1] < $pt2[1]) ? 1 : -1;
        while ($pt1[0] <= $pt2[0]) {
            if ($steep) {
                $this->setPixelValue($pt1[1], $pt1[0], 1);
            } else {
                $this->setPixelValue($pt1[0], $pt1[1], 1);
            }
            $err -= $divy;
            if ($err < 0) {
                $pt1[1] += $s_y;
                $err += $divx;
            }
            $pt1[0]++;
        }

        return true;
    } // DrawLine()
    
    private function getPixelColor($x, $y, $channel=null) 
    {
        $val = $this->getPixelValue($x, $y);
        if ($val !== null && $this->palette !== null) {
            return $this->getPaletteColor($val, $channel);
        }
        if ($this->bpp == 16) {
            $val =
                (((((($val >> 10) & 0x1F) / 0x1F) * 0xFF) + 0.5) << 16)
                + (((((($val >> 5) & 0x1F) / 0x1F) * 0xFF) + 0.5) << 8)
                + (int)(((($val & 0x1F) / 0x1F) * 0xFF) + 0.5);
        }

        return $val;
    } // getPixelColor()
    
    public function getRawData() 
    {
        return $this->image;
    } // GetRawData()
    
    private function getRawBytesPerRow() 
    {
        return $this->rowBytes;
    } // GetRawBytesPerRow()

    /**
      Generate a bar graph bitmap
      @param $percent (0.05 and 5 both represent 5%)
      @param $width default 200
      @param $height default 40
      @return Bitmap object
    */
    public function barGraph($percent, $width=200, $height=40)
    {
        $graph = new Bitmap($width, $height, 1);
        $black = 1;
        $spacing = 5;

        // border top
        $graph->drawLine(array(0,0), array($width-1,0), $black);
        // border bottom
        $graph->drawLine(array(0,$height-1), array($width-1,$height-1), $black);
        // border left
        $graph->drawLine(array(0,1), array(0,$height-2), $black);
        // border right
        $graph->drawLine(array($width-1,1), array($width-1,$height-2), $black);

        $full_bar_size = $width - ($spacing*2);
        if ($percent > 1) $percent = (float)($percent / 100.00);
        if ($percent > 1) $percent = 1.0;
        $bar_size = round($percent * $full_bar_size);

        for($line=$spacing;$line<$height-$spacing;$line++){
            $graph->drawLine(array($spacing,$line), array($spacing+$bar_size,$line), $black);    
        }

        return $graph;
    }

    /**
      Turn bitmap into receipt string
      @param $arg string filename OR Bitmap obj
      @return receipt-formatted string
    */
    static private function renderBitmap($arg)
    {
        global $PRINT_OBJ;
        $slip = "";

        $bmp = null;
        if (is_object($arg) && is_a($arg, 'Bitmap')) {
            $bmp = $arg;
        } elseif (file_exists($arg)) {
            $bmp = new Bitmap();
            $bmp->load($arg);
        }

        // argument was invalid
        if ($bmp === null) {
            return "";
        }

        $bmpData = $bmp->getRawData();
        $bmpWidth = $bmp->getWidth();
        $bmpHeight = $bmp->getHeight();
        $bmpRawBytes = (int)(($bmpWidth + 7)/8);

        $printer = $PRINT_OBJ;
        $stripes = $printer->TransposeBitmapData($bmpData, $bmpWidth);
        for($i=0; $i<count($stripes); $i++)
            $stripes[$i] = $printer->InlineBitmap($stripes[$i], $bmpWidth);

        $slip .= $printer->AlignCenter();
        if (count($stripes) > 1) {
            $slip .= $printer->LineSpacing(0);
        }
        $slip .= implode("\n",$stripes);
        if (count($stripes) > 1) {
            $slip .= $printer->ResetLineSpacing()."\n";
        }
        $slip .= $printer->AlignLeft();

        return $slip;
    }
    
} // Bitmap

