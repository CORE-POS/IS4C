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
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertEquals(false, $get);
    }

    public function testAddLine($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->type = 'receiptHeader';
        $form->id = array();
        $form->line = array();
        $form->newLine = 'TEST NEW LINE';
        $post = $this->runRESTfulPage($page, $form);

        $model = new CustomReceiptModel($this->connection);
        $model->type($form->type);
        $model->text($form->newLine);
        $phpunit->assertNotEquals(0, count($model->find()));
    }

    public function testEditLine($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->id = array(0);
        $form->line = array('TEST CHANGED LINE');
        $post = $this->runRESTfulPage($page, $form);

        $model = new CustomReceiptModel($this->connection);
        $model->type($form->type);
        $model->text($form->line[0]);
        $phpunit->assertNotEquals(0, count($model->find()));
    }

    public function testDeleteLine($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->id = array(0);
        $form->line = array('TEST CHANGED LINE');
        $form->del = array(0);
        $post = $this->runRESTfulPage($page, $form);

        $model = new CustomReceiptModel($this->connection);
        $model->type($form->type);
        $model->text($form->line[0]);
        $phpunit->assertEquals(0, count($model->find()));
    }
}

