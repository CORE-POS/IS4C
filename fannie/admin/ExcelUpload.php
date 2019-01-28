<?php
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class ExcelUpload extends \COREPOS\Fannie\API\FannieUploadPage {

    protected $header = 'Generic File Upload';
    protected $title = 'Generic File Upload';
    public $description = '[Excel Upload] takes a spreadsheet and creates a corresponding database table. Servers no purpose unless you\'re going to write additional SQL manually';

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC',
            'default' => 99999,
            'required' => false,
        ),
    );

    private $result_count = 0;
    private $result_error = false;

    private function createTable($dbc, $headers, $upcCol)
    {
        $genericUpload = FannieDB::fqn('GenericUpload', 'op');
        if ($dbc->tableExists($genericUpload)) {
            $dbc->query('DROP TABLE ' . $genericUpload);
        }

        $query = 'CREATE TABLE ' . $genericUpload . ' (';
        for ($i=0; $i<count($headers); $i++) {
            $val = $headers[$i];
            if ($upcCol !== false && $i == $upcCol) {
                $query .= 'upc VARCHAR(255),';
                continue;
            } elseif ($upcCol !== false && trim(strtolower($val)) == 'upc') {
                $i = '';
            }
            $query .= ($val === '' ? 'col'.rand(0,999999) : $dbc->identifierEscape($val)) . ' VARCHAR(255),'; 
        }
        $query = substr($query, 0, strlen($query)-1);
        if ($upcCol !== false) {
            $query .= ', INDEX(upc)';
        }
        $query .= ')';
        $created = $dbc->query($query);

        return $created ? true : false;
    }

    private function rewriteUpc($curUpc)
    {
        foreach (array('-', ' ') as $sep) {
            if (strstr($curUpc, $sep)) {
                $curUpc = str_replace($sep, '', $curUpc);
                if (strlen($curUpc) == 12) {
                    $curUpc = substr($curUpc, 0, 11);
                }
            }
        }

        return $curUpc;
    }

    function process_file($linedata, $indexes)
    {
        $headers = $linedata[0]; 
        $headers = array_map(function($i){ return str_replace(' ', '', $i);}, $headers);
        $dbc = $this->connection;
        $upcCol = $this->getColumnIndex('upc');
        $genericUpload = FannieDB::fqn('GenericUpload', 'op');
        $created = $this->createTable($dbc, $headers, $upcCol);
        if ($created === false) {
            $this->result_error = 'Could not create table';
            return false;
        }
        $query = 'INSERT INTO ' . $genericUpload . ' VALUES (' . str_repeat('?,', count($headers));
        $query = substr($query, 0, strlen($query)-1) . ')';
        $prep = $dbc->prepare($query);
        $dbc->startTransaction();
        for ($i=1; $i<count($linedata); $i++) {
            if ($upcCol !== false) {
                $curUpc = isset($linedata[$i][$upcCol]) ? $linedata[$i][$upcCol] : '';
                if (empty($curUpc)) {
                    continue;
                }
                $curUpc = $this->rewriteUpc($curUpc);
                $linedata[$i][$upcCol] = BarcodeLib::padUPC($curUpc);
            }
            while (count($linedata[$i]) < count($headers)) {
                $linedata[$i][] = '';
            }
            if (count($linedata[$i]) > count($headers)) {
                $linedata[$i] = array_slice($linedata[$i], 0, count($headers));
            }
            $inserted = $dbc->execute($prep, $linedata[$i]);
            $this->result_count += $inserted ? 1 : 0;
        }
        $dbc->commitTransaction();

        return true;
    }

    public function results_content()
    {
        if ($this->result_error) {
            return '<div class="alert alert-danger">' . $this->result_error . '</div>';
        } elseif ($this->result_count == 0) {
            return '<div class="alert alert-warning">Imported zero records</div>';
        }
        return '<div class="alert alert-success">Imported ' . $this->result_count . ' records</div>';
    }

    public function unitTest($phpunit)
    {
        $data = array(
            array('upc', 'bar', 'baz'),
            array(1, 2, 3),
            array('1-12345-67890-1', 3.5),
        );
        $this->preview_selections['upc'] = 0;
        $phpunit->assertEquals(true, $this->process_file($data, array()));
    }
}

FannieDispatch::conditionalExec();

