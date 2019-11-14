<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('COREPOS\\pos\\lib\\PrintHandlers\\ESCPOSPrintHandler')) {
    include(__DIR__ . '/../../../../pos/is4c-nf/lib/PrintHandlers/ESCPOSPrintHandler.php');
}
if (!class_exists('COREPOS\\pos\\lib\\PrintHandlers\\ESCNetRawHandler')) {
    include(__DIR__ . '/../../../../pos/is4c-nf/lib/PrintHandlers/ESCNetRawHandler.php');
}

class OrderTabletPage extends FannieRESTfulPage
{
    protected $title = '';
    protected $header = '';

    public function preprocess()
    {
        $this->addRoute('get<items>', 'get<done>', 'get<cancel>');
        if (!isset($this->session->otItems) || !is_array($this->session->otItems)) {
            $this->session->otItems = array();
        }

        return parent::preprocess();
    }

    /**
     * Just clear session data to start over
     */
    protected function get_cancel_handler()
    {
        $this->session->otItems = array();

        return 'OrderTabletPage.php';
    }

    /**
     * Finish transaction.
     *
     * Generates receipt and sends to printer, twice, via
     * network.
     *
     * Adds items to dtransactions, copies them to suspended,
     * and flips the dtransactions records to trans_status=X
     */
    protected function get_done_handler()
    {
        $emp = $this->config->get('EMP_NO');
        $reg = $this->config->get('REGISTER_NO');
        $trans = DTrans::getTransNo($this->connection, $emp, $reg);
        $orderNumber = $emp . '-' . $reg . '-' . $trans;

        $ph = new COREPOS\pos\lib\PrintHandlers\ESCPOSPrintHandler();
        $receipt = "\n"
            . $ph->textStyle(true, false, true)
            . 'Order #' . $orderNumber . "\n"
            . date('n j, Y g:i:a') . "\n"
            . $ph->textStyle(true)
            . "\n\n";
        foreach ($this->session->otItems as $item) {
            $receipt .= str_pad($item['price'], 4) . ' ';
            $receipt .= $item['name'] . "\n";
            DTrans::addItem($this->connection, $trans, array(
                'emp_no' => $emp,
                'register_no' => $reg,
                'trans_type' => 'I',
                'upc' => $item['upc'],
                'description' => $item['name'],
                'quantity' => 1,
                'ItemQtty' => 1,
                'unitPrice' => $item['price'],
                'total' => $item['price'],
                'regPrice' => $item['price'],
                'cost' => $item['cost'],
                'department' => $item['dept'],
                'tax' => $item['tax'],
                'foodstamp' => $item['fs'],
                'discountable' => $item['disc'],
            ));
        }
        $receipt .= str_repeat("\n", 8);
        $receipt .= $ph->cutPaper();

        $this->connection = FannieDB::get($this->config->get('TRANS_DB'));
        $cols = '';
        foreach ($this->connection->matchingColumns('dtransactions', 'suspended') as $c) {
            $cols .= $this->connection->identifierEscape($c) . ',';
        }
        $cols = substr($cols, 0, strlen($cols) - 1);
        $suspP = $this->connection->prepare("
            INSERT INTO suspended ({$cols})
            SELECT {$cols} FROM dtransactions
            WHERE datetime >= " . $this->connection->curdate() . "
                AND emp_no=?
                AND register_no=?
                AND trans_no=?");
        $this->connection->execute($suspP, array($emp, $reg, $trans));
        $endP = $this->connection->prepare("UPDATE dtransactions SET trans_status='X'
            WHERE datetime >= " . $this->connection->curdate() . "
                AND emp_no=?
                AND register_no=?
                AND trans_no=?");
        $this->connection->execute($endP, array($emp, $reg, $trans));
                
        $net = new COREPOS\pos\lib\PrintHandlers\ESCNetRawHandler();
        $net->setTarget('127.0.0.1:9100');
        $net->writeLine($receipt);
        $net->writeLine($receipt);

        $this->session->otItems = array();

        return 'OrderTabletPage.php';
    }

    protected function delete_id_handler()
    {
        $keep = array();
        foreach ($this->session->otItems as $id => $item) {
            if ($id != $this->id) {
                $keep[] = $item;
            }
        }
        $this->session->otItems = $keep;

        return 'OrderTabletPage.php';
    }

    /**
     * Append new item to in-session storage
     * and return to the main page (get_view())
     */
    protected function post_id_handler()
    {
        $items = $this->session->otItems;
        $itemP = $this->connection->prepare("SELECT description, cost, tax, foodstamp, discount, department FROM products WHERE upc=?");
        for ($i=0; $i<count($this->id); $i++) {
            list($upc,$price) = explode('::', $this->id[$i]);
            $qty = FormLib::get($upc);
            if ($qty) {
                $item = $this->connection->getRow($itemP, array($upc));
                for ($j=0; $j<$qty; $j++) {
                    $items[] = array(
                        'upc' => $upc,
                        'price' => $price,
                        'name' => $item['description'],
                        'tax' => $item['tax'],
                        'fs' => $item['foodstamp'],
                        'cost' => $item['cost'],
                        'disc' => $item['discount'],
                        'dept' => $item['department'],
                    );
                }
            }
        }
        $this->session->otItems = $items;

        return 'OrderTabletPage.php';
    }

    /**
     * Get list of items.
     * Simply cross-references table OrderTabletItems to
     * decided which items to list
     */
    protected function get_items_view()
    {
        $res = $this->connection->query("
SELECT i.upc, p.description, p.normal_price, p.special_price, p.discounttype, d.dept_name
FROM OrderTabletItems AS i
    " . DTrans::joinProducts('i', 'p', 'INNER') . "
    INNER JOIN departments AS d ON p.department=d.dept_no
ORDER BY d.dept_name, p.description");
        $items = '';
        $dept = false;
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['dept_name'] != $dept) {
                $items .= '<h3>' . $row['dept_name'] . '</h3>';
                $dept = $row['dept_name'];
            }
            $price = $row['discounttype'] == 1 ? $row['special_price'] : $row['normal_price'];
            $items .= sprintf('<div class="row"><div class="col-sm-6">%.2f %s</div>
                <div class="col-sm-6">
                    <input type="hidden" name="id[]" value="%s::%.2f" />
                    <div class="col-sm-6">
                        <button type="button" onclick="incDec(\'%s\', 1);" class="btn btn-success">+</button> 
                        <button type="button" onclick="incDec(\'%s\', -1);" class="btn btn-danger">-</button> 
                    </div>
                    <div class="col-sm-6">
                        <input type="number" id="inp%s" name="%s" value="0" class="form-control" />
                    </div>
                </div></div>',
                $price, $row['description'],
                $row['upc'], $price,
                $row['upc'],
                $row['upc'],
                $row['upc'], $row['upc']);
        }

        return <<<HTML
<form method="post" action="OrderTabletPage.php">
<div class="row">
    <div class="col-sm-5">
        {$items}
    </div>
    <div class="col-sm-5">
        <button type="submit" class="btn btn-success btn-lg">Add Items</button>
        <br /><br />
        <a href="OrderTabletPage.php" class="btn btn-default btn-lg">Go Back</a>
    </div>
</div>
</form>
HTML;
    }

    /**
     * Print two columns
     * Left - items in the order
     * Right - Command buttons
     */
    protected function get_view()
    {
        $items = '';
        $total = 0;
        foreach ($this->session->otItems as $id => $item) {
            $items .= sprintf('<div class="item-row" onclick="selectItem(event);" data-id="%d">
                <span class="pull-left">%s</span>
                <span class="pull-right">%.2f</span>
                </div><br />',
                $id, $item['name'], $item['price']);
            $total += $item['price'];
        }
        $items .= sprintf('<br /><div class="item-row">
            <span class="pull-left">Subtotal</span>
            <span class="pull-right">%.2f</span>
            </div>', $total
        );
            
        return <<<HTML
<div class="row">
    <div class="col-sm-5" style="font-size: 140%";>
        <h3>Items</h3>
        {$items}
    </div>
    <div class="col-sm-5">
        <a href="OrderTabletPage.php?items=1" class="btn btn-default btn-lg">Add Item</a>
        <br />
        <br />
        <a href="OrderTabletPage.php" id="rmLink" class="btn btn-default btn-lg">Remove Item</a>
        <br />
        <br />
        <a href="OrderTabletPage.php?done=1" class="btn btn-success btn-lg">Done</a>
        <br />
        <br />
        <a href="OrderTabletPage.php?cancel=1" class="btn btn-danger btn-lg">Cancel</a>
    </div>
</div>
HTML;
    }

    protected function javascriptContent()
    {
        return <<<JAVASCRIPT
function selectItem(ev) {
    $('div.item-row').removeClass('alert-danger');
    $(ev.target).parent().addClass('alert-danger');
    var id = $(ev.target).parent().attr('data-id');
    if (id) {
        $('#rmLink').attr('href', 'OrderTabletPage.php?_method=delete&id=' + id);
    }
}
function incDec(id, inc) {
    var cur =$('#inp' + id).val();
    cur = cur * 1;
    cur += inc;
    if (cur <= 0) {
        cur = 0;
    }
    $('#inp'+id).val(cur);
};
JAVASCRIPT;
    }
}

FannieDispatch::conditionalExec();

