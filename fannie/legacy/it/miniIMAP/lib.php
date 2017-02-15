<?php

function enc($name, $pass, $iv){
    $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES,'',MCRYPT_MODE_CBC,'');
    mcrypt_generic_init($cipher,substr(sha1($name),0,24),substr($iv,0,8));
    $block = mcrypt_generic($cipher,$pass);
    mcrypt_generic_deinit($cipher);
    mcrypt_module_close($cipher);
    return base64_encode($block);
}

function dec($name, $pass, $iv){
    $pass = base64_decode($pass);
    $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES,'',MCRYPT_MODE_CBC,'');
    mcrypt_generic_init($cipher,substr(sha1($name),0,24),substr($iv,0,8));
    $block = mdecrypt_generic($cipher,$pass);
    mcrypt_generic_deinit($cipher);
    mcrypt_module_close($cipher);
    return $block;
}

function get_body($mb,$num,$fix_newlines=True){
    $dataTxt = get_part($mb, $num, "TEXT/PLAIN");
    $dataHtml = get_part($mb, $num, "TEXT/HTML");

    if(!empty($dataHtml)) return $dataHtml;    
    if ($fix_newlines)
        $dataTxt = ereg_replace("\n","<br>",$dataTxt);
    return $dataTxt;
}

function get_mime_type(&$structure) {
   $primary_mime_type = array("TEXT", "MULTIPART","MESSAGE", "APPLICATION", "AUDIO","IMAGE", "VIDEO", "OTHER");
   if($structure->subtype) {
       return $primary_mime_type[(int) $structure->type] . '/' .$structure->subtype;
   }
       return "TEXT/PLAIN";
}

function get_part($stream, $msg_number, $mime_type, $structure = false,$part_number    = false) {
   
       if(!$structure) {
           $structure = imap_fetchstructure($stream, $msg_number);
       }
       if($structure) {
           if($mime_type == get_mime_type($structure)) {
               if(!$part_number) {
                   $part_number = "1";
               }
               $text = imap_fetchbody($stream, $msg_number, $part_number);
               if($structure->encoding == 3) {
                   return imap_base64($text);
               } else if($structure->encoding == 4) {
                   return imap_qprint($text);
               } else {
               return $text;
           }
       }
   
        if($structure->type == 1) /* multipart */ {
           while(list($index, $sub_structure) = each($structure->parts)) {
               if($part_number) {
                   $prefix = $part_number . '.';
               }
               $data = get_part($stream, $msg_number, $mime_type, $sub_structure,$prefix .    ($index + 1));
               if($data) {
                   return $data;
               }
           } // END OF WHILE
           } // END OF MULTIPART
       } // END OF STRUTURE
       return false;
} // END OF FUNCTION

$MIME_PRIMARY_TYPE = array(
    0    => "text",
    1    => "multipart",
    2    => "message",
    3    => "application",
    4    => "audio",
    5    => "image",
    6    => "video",
    7    => "other"
);

function get_attachments($imap, $msg){
    global $MIME_PRIMARY_TYPE;
    $ret = array();
    $struct = imap_fetchstructure($imap,$msg);
    $pos = 1;
    foreach($struct->parts as $p){
        $obj = array('filename'=>'','name'=>'','size'=>$p->bytes,'num'=>$pos);

        if ($p->ifdparameters){
            foreach($p->dparameters as $o){
                if (strtolower($o->attribute)=='filename'){
                    $obj['filename']=$o->value;
                    break;
                }
            }
        }

        if ($p->ifparameters){
            foreach($p->parameters as $o){
                if (strtolower($o->attribute)=='name'){
                    $obj['name']=$o->value;
                    break;
                }
            }
        }

        $obj["mime"] = $MIME_PRIMARY_TYPE[$p->type]."/".strtolower($p->subtype);

        if (!empty($obj['filename']) || !empty($obj['name']))
            array_push($ret,$obj);

        $pos++;
    }

    return $ret;
}

function better_size($bytes){
    if ($bytes < 1024){
        return $bytes."B";
    }
    if ($bytes < (1024*1024)){
        return (round($bytes/1024.0,1))."KB";
    }
    return (round($bytes/(1024.0*1024.0),1))."MB";
}

function stream_attachment($imap,$msg,$num,$name){
    global $MIME_PRIMARY_TYPE;

    $struct = imap_fetchstructure($imap,$msg);
    $part = $struct->parts[$num-1];
    $mime = $MIME_PRIMARY_TYPE[$part->type]."/".strtolower($part->subtype);

    $bytes = imap_fetchbody($imap,$msg,$num);
    if ($part->encoding == 3)
        $bytes = base64_decode($bytes);
    elseif($part->encoding == 4)
        $bytes = imap_qprint($bytes);
    
    header("Content-type: ".$mime);
    if ($part->type == 3)
        header("Content-Disposition: attachment; filename=".$name);
    echo $bytes;
}

