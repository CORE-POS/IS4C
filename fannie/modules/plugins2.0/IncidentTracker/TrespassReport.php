<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class TrespassReport extends FannieReportPage
{
    protected $new_tablesorter = true;
    protected $report_headers = array('Incident#', 'Name', 'Trespass Start', 'Trespass Ends');
    protected $header = 'Trespass Report';
    protected $title = 'Trespass Report';

    public function report_description_content()
    {
        $ret = FormLib::storePicker('store', false, "window.location='TrespassReport.php?store='+this.value;");

        return array('<div class="form-group">' . $ret['html'] . '</div>');
    }

    public function fetch_report_data()
    {
        $store = FormLib::get('store', 0);
        if ($store === 0) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }

        $timeframe = $this->connection->curDate() . " BETWEEN trespassStart AND trespassEnd ";
        if (FormLib::get('expired')) {
            $timeframe = $this->connection->curDate() . " > trespassEnd ";
        }
        $prep = $this->connection->prepare("
            SELECT incidentID,
                personName,
                trespassStart,
                trespassEnd
            FROM " . FannieDB::fqn('Incidents', 'plugin:IncidentDB') . "
            WHERE trespass=1
                AND " . $timeframe . "
                AND storeID=?");
        $res = $this->connection->execute($prep, array($store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                sprintf('<a href="AlertIncident.php?id=%d">#%d</a>', $row['incidentID'], $row['incidentID']),
                $row['personName'],
                $row['trespassStart'],
                $row['trespassEnd'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

