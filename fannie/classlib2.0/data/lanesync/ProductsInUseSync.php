<?php

namespace COREPOS\Fannie\API\data\lanesync;

/**
  Use mysqldump to copy products to the lanes
  with only records where inUse=1
*/
class ProductsInUseSync extends MySQLSync
{
    public function push($tableName, $dbName)
    {
        $ret = array('success'=>false, 'details'=>'');
        $tempfile = tempnam(sys_get_temp_dir(),$table.".sql");
        $cmd = $this->dumpCommand()
            . ' -w ' . escapeshellarg('inUse=1')
            . ' '   . escapeshellarg($dbName)
            . ' '   . escapeshellarg($table)
            . ' > ' . escapeshellarg($tempfile)
            . ' 2> ' . escapeshellarg($tempfile . '.err');

        exec($cmd, $output, $exitCode);

        if ($exitCode > 0) {
            $ret['details'] = 'mysqldump failed';
            if (file_exists($tempfile . '.err')) {
                $ret['details'] .= ': ' . file_get_contents($tempfile . '.err');
                unlink($tempfile . '.err');
            }

            return $ret;
        }

        $laneNumber = 1;
        $ret['success'] = true;
        foreach ($this->config->get('LANES') as $lane) {
            $laneCmd = $this->laneConnect($lane, $dbName, $tempfile); 
            exec($laneCmd, $output, $exitCode);
            if ($exitCode == 0) {
                $ret['details'] .= "Lane {$laneNumber} ({$lane['host']}) completed successfully\n";
            } else {
                $ret['details'] .= "Lane {$laneNumber} ({$lane['host']}) failed, returned: " . implode(', ', $output) . "\n";
                $ret['success'] = false;
            }
            unset($output);
            unset($exitCode);
            $laneNumber++;
        }
        unlink($tempfile);

        return $ret;
    }
}

