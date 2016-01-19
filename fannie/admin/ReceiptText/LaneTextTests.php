<?php

class LaneTextTests extends \COREPOS\Fannie\API\test\TestWrapper
{
    public function testHtml($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'get';
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertNotEquals(0, strlen($get));

        $form->type = 'receiptHeader';
        ob_start();
        $get = $this->runRESTfulPage($page, $form);
        ob_end_clean();
        $phpunit->assertEquals(false, $get);
    }

    public function testAddLine($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->type = 'receiptHeader';
        $form->newLine = 'TEST NEW LINE';
        ob_start();
        $post = $this->runRESTfulPage($page, $form);
        ob_end_clean();

        $model = new CustomReceiptModel($this->connection);
        $model->type($form->type);
        $model->text($form->newLine);
        $phpunit->assertNotEquals(0, count($model->find()));
    }

    public function testEditLine($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->type = 'receiptHeader';
        $form->id = array(0);
        $form->line = array('TEST CHANGED LINE');
        ob_start();
        $post = $this->runRESTfulPage($page, $form);
        ob_end_clean();

        $model = new CustomReceiptModel($this->connection);
        $model->type($form->type);
        $model->text($form->line[0]);
        $phpunit->assertNotEquals(0, count($model->find()));
    }

    public function testDeleteLine($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->type = 'receiptHeader';
        $form->id = array(0);
        $form->line = array('TEST CHANGED LINE');
        $form->del = array(0);
        ob_start();
        $post = $this->runRESTfulPage($page, $form);
        ob_end_clean();

        $model = new CustomReceiptModel($this->connection);
        $model->type($form->type);
        $model->text($form->line[0]);
        $phpunit->assertEquals(0, count($model->find()));
    }
}

