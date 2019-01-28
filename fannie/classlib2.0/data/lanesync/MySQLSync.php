<?php

namespace COREPOS\Fannie\API\data\lanesync;
use COREPOS\Fannie\API\data\SyncSpecial;

/**
  Export table using mysqldump
  Import table into each lane by piping
  the export into CLI mysql
*/
class MySQLSync extends SyncSpecial
{
    public function push($tableName, $dbName, $includeOffline=false)
    {
        $ret = array('success'=>false, 'details'=>'');
        
        $tempfile = tempnam(sys_get_temp_dir(),$tableName.".sql");
        $cmd = $this->dumpCommand()
            . ' '   . escapeshellarg($dbName)
            . ' '   . escapeshellarg($tableName)
            . ' > ' . escapeshellarg($tempfile)
            . ' 2> ' . escapeshellarg($tempfile . '.err');
        exec($cmd, $output, $exitCode);
        
        if ($exitCode > 0) {
            $ret['details'] = 'mysqldump failed. Ran as ' . $cmd;
            if (file_exists($tempfile . '.err')) {
                $ret['details'] .= ': ' . file_get_contents($tempfile . '.err');
                unlink($tempfile . '.err');
            }

            return $ret;
        }

        $ret['success'] = true;

        return $this->sendDumpToLanes($ret, $dbName, $tempfile, $includeOffline);
    }

    protected function sendDumpToLanes($ret, $dbName, $tempfile, $includeOffline)
    {
        $laneNumber = 1;
        foreach ($this->config->get('LANES') as $lane) {
            if (!$includeOffline && isset($lane['offline']) && $lane['offline']) {
                continue;
            }
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

    protected function dumpCommand()
    {
        $port = 3306;
        $host = $this->config->get('SERVER');
        if (strpos($host, ':') > 0) {
            list($host, $port) = explode(':', $host, 2);
        }

        $cmd = 'mysqldump'
            . ' -u ' . escapeshellarg($this->config->get('SERVER_USER'))
            . (empty($this->config->get('SERVER_PW')) ? '' : ' -p' . escapeshellarg($this->config->get('SERVER_PW')))
            . ' -h ' . escapeshellarg($host)
            . ' -P ' . escapeshellarg($port);

        return $cmd;
    }

    protected function laneConnect($lane, $dbName, $dumpfile)
    {
        $lane_host = $lane['host'];
        $lane_port = 3306;
        if (strpos($lane['host'], ':') > 0) {
            list($lane_host, $lane_port) = explode(':', $lane['host'], 2);
        }
        $laneCmd = 'mysql --connect-timeout 15 '
            . ' -u ' . escapeshellarg($lane['user'])
            . (empty($lane['pw']) ? '' : ' -p' . escapeshellarg($lane['pw']))
            . ' -h ' . escapeshellarg($lane_host)
            . ' -P ' . escapeshellarg($lane_port)
            . ' ' . escapeshellarg($lane['op'])
            . ' < ' . escapeshellarg($dumpfile)
            . ' 2>&1';

        return $laneCmd;
    }
}

