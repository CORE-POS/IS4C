<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RestGetOnly extends JsonEndPoint
{
    // BasicModel instance representing underlying table
    protected $model;

    protected $filter = array();

    protected $idColumn = 'id';

    // maximum results to return
    protected $limit = 100;

    public function __construct($model)
    {
        $this->model;
    }

    protected function get()
    {
        $input = $this->readInput();
        if (isset($input['id'])) {
            $ids = is_array($input['id']) ? $input['id'] : array($input['id']);
            $results[] = array();
            $col = $this->idColumn;
            foreach ($ids as $id) {
                $this->model->$col($id);
                if ($model->load()) {
                    $results[] = json_decode($this->model->toJSON(), true);
                }
            }
            header('HTTP/1.0 200 Success');
            return $results;

        } elseif (isset($input['filter'])) {
            if (!is_array($input['filter'])) {
                header('HTTP/1.0 400 Bad Request');
                return array('error' => 'Filter must be an object');
            }
            $columns = $this->model->getColumns();
            foreach ($input['filter'] as $col => $val) {
                if (!isset($columns[$col])) {
                    header('HTTP/1.0 400 Bad Request');
                    return array('error' => 'Invalid filter on ' . $col);
                }
                $this->model->$col($val);
            }
            $results = array();
            foreach ($this->model->find() as $obj) {
                $results[] = json_decode($obj->toJSON(), true);
            }
            header('HTTP/1.0 200 Success');
            return $results;
        }

        header('HTTP/1.0 400 Bad Request');
        return array('error' => 'Specify single id, id list, or filter(s)');
    }
}

