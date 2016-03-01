<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

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

class EncBlock extends LibraryClass
{
    /**
      In theory parses output produced by MagTek and ID tech
      devices (based on spec / examples)
      @return array with keys
       - Format is encrypt format
       - Block is encryped PAN block
       - Key is encrypted key
       - Issuer is card issuer (Visa, MasterCard, etc)
       - Last4 is last four PAN digits
       - Name is cardholder name (if available)
    */
    public static function parseEncBlock($str)
    {
        $ret = array(
            'Format'=>'MagneSafe',
            'Block'=>'',
            'Key'=>'',
            'Issuer'=>'Unknown',
            'Last4'=>'XXXX',
            'Name'=>'Cardholder'
        );
        if (strstr($str,"|")) {
            return self::magtekBlock($str, $ret);
        } else if (strlen($str)>2 && substr($str,0,2)=="02") {
            return self::idtechBlock($str, $ret);
        } elseif (strlen($str) > 4 && substr($str, 0, 4) == "23.0") {
            return self::ingenicoBlock($str, $ret);
        }

        return $ret;
    }

    private static function magtekBlock($str, $ret)
    {
        $parts = explode("|",$str);
        $tr1 = False;
        $tr2 = False;
        if ($str[0] == "%") {
            /* non-numbered format */
            $ret['Block'] = $parts[3];
            $ret['Key'] = $parts[9];
            $tr1 = $parts[0];
            $tr2 = $parts[1];
        } else if ($str[0] == "1") {
            /* numbered format */
            foreach($parts as $p) {
                if (strlen($p) > 2 && substr($p,0,2)=="3~") {
                    $ret['Block'] = substr($p,2);    
                } else if (strlen($p) > 3 && substr($p,0,3)=="11~") {
                    $ret['Key'] = substr($p,3);    
                } else if (strlen($p) > 2 && substr($p,0,3)=="6~") {
                    $tr1 = substr($p,2);
                } else if (strlen($p) > 2 && substr($p,0,3)=="7~") {
                    $tr2 = substr($p,2);
                }
            }
        }

        // extract info from masked tracks
        if ($tr1 && $tr1[0] == "%") {
            $split = explode("^",$tr1);
            $pan = substr($split[0],1);
            if (strlen($split[1]) <= 26) {
                $ret['Name'] = $split[1];
            }
            $ret['Last4'] = substr($pan,-4);
            $ret['Issuer'] = PaycardLib::paycard_issuer($pan);
        } else if($tr2 && $tr2[0] == ";") {
            $tr2 = substr($tr2,1);
            $pan = substr($tr2,0,strpos("="));
            $ret['Last4'] = substr($pan,-4);
            $ret['Issuer'] = PaycardLib::paycard_issuer($pan);
        }

        return $ret;
    }

    private static function decodeTrack1($str, $pos, $kl, $ret)
    {
        // read name and masked PAN from track 1
        $caret = strpos($str,"5E",$pos);
        $pan = substr($str,$pos,$caret-$pos);
        $pan = substr($pan,4); // remove leading %*
        $caret2 = strpos($str,"5E",$caret+2);
        if ($caret2 < ($pos + ($kl*2))) { // still in track 1
            $name = substr($str,$caret+2,$caret2-$caret-2);
            $ret['Name'] = self::dehexify($name);    
        }
        $pan = self::dehexify($pan);
        $ret['Last4'] = substr($pan,-4);
        $ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*","0",$pan));

        return $ret;
    }

    private static function decodeTrack2($str, $pos, $kl, $ret)
    {
        $equal = strpos($str,"3D",$pos);
        $pan = substr($str,$pos,$equal-$pos);
        $pan = substr($pan,2); // remove leading ;
        $pan = self::dehexify($pan);
        $ret['Last4'] = substr($pan,-4);
        $ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*",0,$pan));

        return $ret;
    }

    private static function parseTrack1($str, $pos, $kl, $ret)
    {
        $tr1 = substr($str, $pos, $kl);
        $pieces = explode('^', $tr1);
        if (isset($pieces[1])) {
            $ret['Name'] = $pieces[1];
        }
        $pan = str_replace('*', '0', substr($pieces[0],2));
        $ret['Last4'] = substr($pan,-4);
        $ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*","0",$pan));

        return $ret;
    }

    private static function parseTrack2($str, $pos, $kl, $ret)
    {
        $tr2 = substr($str, $pos, $kl);
        $pieces = explode('=', $tr2);
        $pan = str_replace('*', '0', substr($pieces[0],1));
        $ret['Last4'] = substr($pan,-4);
        $ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*","0",$pan));

        return $ret;
    }

    private static function idtechBlock($str, $ret)
    {
        // read track length from block
        $track_length = array(
            1 => hexdec(substr($str,10,2)),
            2 => hexdec(substr($str,12,2)),
            3 => hexdec(substr($str,14,2))
        );

        $decoded = strstr($str, '***');

        // skip to track data start point
        $pos = 20;
        // move through masked track data
        foreach ($track_length as $num=>$kl) {
            if ($num == 1 && $kl > 0) {
                if ($decoded) {
                    $ret = self::parseTrack1($str, $pos, $kl, $ret);
                } else {
                    $ret = self::decodeTrack1($str, $pos, $kl, $ret);
                }
            } elseif ($num == 2 && $kl > 0) {
                if ($decoded) {
                    $ret = self::parseTrack2($str, $pos, $kl, $ret);
                } else {
                    $ret = self::decodeTrack2($str, $pos, $kl, $ret);
                }
            }
            $pos += ($decoded ? $kl : $kl*2);
        }

        // mercury rejects track 1
        if ($track_length[1] > 0) {
            while($track_length[1] % 8 != 0) $track_length[1]++;
            // cannot send back track 1
            //$ret['Block'] = substr($str,$pos,$track_length[1]*2);
            $pos += ($track_length[1]*2);
        }

        // read encrypted track 2
        if ($track_length[2] > 0) {
            while($track_length[2] % 8 != 0) $track_length[2]++;
            $ret['Block'] = substr($str,$pos,$track_length[2]*2);
            $pos += ($track_length[2]*2);
        }

        // move past hash 1 if present, hash 2 if present
        if ($track_length[1] > 0) {
            $pos += (20*2);
        }
        if ($track_length[2] > 0) {
            $pos += (20*2);
        }

        // read key segment
        $ret['Key'] = substr($str,$pos,20);

        return $ret;
    }

    private static function ingenicoBlock($str, $ret)
    {
        $data = substr($str, 4);
        $tracks = explode('@@', $data);
        $track1 = false;
        $track2 = false;
        $track3 = $tracks[count($tracks)-1];
        if ($tracks[0][0] == '%') {
            $track1 = $tracks[0];
        } elseif ($tracks[0][0] == ';') {
            $track2 = $tracks[0];
        }
        if ($track2 === false && $tracks[1][0] == ';') {
            $track2 = $tracks[1];
        }

        if ($track1 !== false) {
            $pieces = explode('^', $track1);
            $masked = ltrim($pieces[0], '%');
            $ret['Issuer'] = PaycardLib::paycard_issuer($masked);
            $ret['Last4'] = substr($masked, -4);
            if (count($pieces) >= 3) {
                $ret['Name'] = $pieces[1];
            }
        } elseif ($track2 !== false) {
            list($start, $end) = explode('=', $track2, 2);
            $masked = ltrim($start, ';');
            $ret['Issuer'] = PaycardLib::paycard_issuer($masked);
            $ret['Last4'] = substr($masked, -4);
        }

        if (strstr($track3, ';')) {
            list($e2e, $actual_track3) = explode(';', $track3, 2);
            $track3 = $e2e;
        }

        $pieces = explode(':', $track3);
        if (count($pieces) == 4) {
            $ret['Block'] = $pieces[2];
            $ret['Key'] = $pieces[3];
        } elseif (count($pieces) == 2 && $track1 === false) {
            $ret['Block'] = $pieces[0];
            $ret['Key'] = $pieces[1];
        }

        return $ret;
    }

    public static function parsePinBlock($str)
    {
        $ret = array('block'=>'', 'key'=>'');
        if (strlen($str) == 36 && substr($str,0,2) == "FF") {
            // idtech
            $ret['key'] = substr($str,4,16);
            $ret['block'] = substr($str,-16);
        } else {
            // ingenico
            $ret['key'] = substr($str, -20);
            $ret['block'] = substr($str, 0, 16);
        }

        return $ret;
    }

    /*
      Utility. Convert hex string to ascii characters
    */
    private static function dehexify($in)
    {
        // must be two characters per digit
        if (strlen($in) % 2 != 0) {
            return false;
        }
        $ret = "";
        for ($i=0;$i<strlen($in);$i+=2) {
            $ret .= chr(hexdec(substr($in,$i,2)));
        }

        return $ret;
    }

}

