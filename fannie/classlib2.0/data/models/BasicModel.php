<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../FannieAPI.php');
}

/**
  @class BasicModel
*/
class BasicModel extends COREPOS\common\BasicModel
{

    /**
      When updating server-side tables, apply
      the same updates to lane-side tables.
      Default is false.
    */
    protected $normalize_lanes = false;

    /**
      Status variable. Besides normalize() itself
      some hook functions may need to know if the
      current update is on the server vs on the lane.
    */
    protected $currently_normalizing_lane = false;

    /** check for potential changes **/
    const NORMALIZE_MODE_CHECK = 1;
    /** apply changes **/
    const NORMALIZE_MODE_APPLY = 2;

    /**
      Constructor
      @param $con a SQLManager object
    */
    public function __construct($con)
    {
        parent::__construct($con);
        $this->config = FannieConfig::factory();
    }

    public function pushToLanes()
    {
        // load complete record
        if (!$this->load()) {
            return false;
        }

        $current = $this->connection;
        // save to each lane
        foreach ($this->config->get('LANES', array()) as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane['op']] === false) {
                continue;
            }
            $this->connection = $sql;
            $this->save();
        }
        $this->connection = $current;

        return true;
    }

    public function deleteFromLanes()
    {
        // load complete record
        if (!$this->load()) {
            return false;
        }

        $current = $this->connection;
        // save to each lane
        foreach ($this->config->get('LANES', array()) as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane['op']] === false) {
                continue;
            }
            $this->connection = $sql;
            $this->delete();
        }
        $this->connection = $current;

        return true;
    }

    protected function normalizeLanes($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        // map server db name to lane db name
        $lane_db = false;
        if ($db_name == $this->config->get('OP_DB')) {
            $lane_db = 'op';
        } else if ($db_name == $this->config->get('TRANS_DB')) {
            $lane_db = 'trans';
        }

        if ($lane_db === false) {
            return false;
        }

        $this->currently_normalizing_lane = true;

        $current = $this->connection;
        $save_fq = $this->fq_name;
        // call normalize() on each lane
        foreach ($this->config->get('LANES', array()) as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane[$lane_db],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane[$lane_db]] === false) {
                continue;
            }
            $this->connection = $sql;
            
            $this->fq_name = $lane[$lane_db] . $sql->sep() . $this->name;
            $this->normalize($db_name, $mode);
        }
        $this->connection = $current;
        $this->fq_name = $save_fq;

        $this->currently_normalizing_lane = false;

        return true;
    }

    /**
      Search available classes to load applicable
      hook objects into this instance
    */
    protected function loadHooks()
    {
       $this->hooks = array();
       if (class_exists('FannieAPI')) {
           $hook_classes = FannieAPI::listModules('BasicModelHook');
           $others = FannieAPI::listModules('\COREPOS\Fannie\API\data\hooks\BasicModelHook');
           foreach ($others as $o) {
               if (!in_array($o, $hook_classes)) {
                   $hook_classes[] = $o;
               }
           }
           foreach($hook_classes as $class) {
                if (!class_exists($class)) continue;
                $hook_obj = new $class();
                if ($hook_obj->operatesOnTable($this->name)) {
                    $this->hooks[] = $hook_obj;
                }
           }
       }
       // placeholder value to signify this has actually run
       $this->hooks[] = '_loaded';
    }

    public function getHooks()
    {
        return $this->hooks;
    }

    public function setHooks(array $hooks)
    {
        $this->hooks = $hooks;
    }

    /**
      Interface method
      Should eventually inherit from \COREPOS\common\BasicModel
    */
    public function getModels()
    {
        /**
          Experiment using lambdas. I was curious
          if I could do recursion without having
          a named function.
        */
        $search = function($path) use (&$search) {
            if (is_file($path) && substr($path,'-4')=='.php') {
                include_once($path);
                $class = basename(substr($path,0,strlen($path)-4));
                if (class_exists($class) && is_subclass_of($class, 'BasicModel')) {
                    return array($class);
                }
            } elseif(is_dir($path)) {
                $dh = opendir($path);
                $ret = array();    
                while( ($file=readdir($dh)) !== False) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $ret = array_merge($ret, $search($path.'/'.$file));
                }
                return $ret;
            }
            return array();
        };
        $models = $search(dirname(__FILE__));

        return $models;
    }

    /**
      Interface method
      Should eventually inherit from \COREPOS\common\BasicModel
    */
    public function setConnectionByName($db_name)
    {
        $this->connection = FannieDB::get($db_name);
    }

    /**
      Interface method
      Should eventually inherit from \COREPOS\common\BasicModel
    */
    protected function afterNormalize($db_name, $mode)
    {
        if ($this->normalize_lanes && !$this->currently_normalizing_lane) {
            $this->normalizeLanes($db_name, $mode);
        }
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    $obj = new BasicModel(null);
    return $obj->cli($argc, $argv);

}

