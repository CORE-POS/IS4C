<?php

$log = __DIR__ . '/../../../log/debug_lane.log';
$out = tailCustom($log, 10);
echo $out;


    /**
     *    * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
     *    * @author Torleif Berger, Lorenzo Stanco
     *    * @link http://stackoverflow.com/a/15025877/995958
     *    * @license http://creativecommons.org/licenses/by/3.0/
     *    */
    function tailCustom($filepath, $lines = 1, $adaptive = true) 
    {

        // Open file
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;
       
        // Sets buffer size, according to the number of lines to retrieve.
        // This gives a performance boost when reading a few lines from the file.
        if (!$adaptive) $buffer = 4096;
        else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
   
        // Jump to last character
        fseek($f, -1, SEEK_END);
   
        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") $lines -= 1;
       
        // Start reading
        $output = '';
        $chunk = '';
    
        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
    
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);
   
            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);
   
            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;
    
            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
    
            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
   
        }
    
        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {
    
            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
    
        }
    
        // Close file and return
        fclose($f);
        return trim($output);
    }
