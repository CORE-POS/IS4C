<?php

class CashierTests extends \COREPOS\Fannie\API\test\TestWrapper
{
    public function testAddCashier($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'get';
        $page->setForm($form);
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertNotEquals(0, strlen($get));

        $form->flash = base64_encode('test message');
        $page->setForm($form);
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertNotEquals(0, strlen($get));

        $form->_method = 'post';
        $form->lname = 'test';
        $form->fname = 'cashier';
        $form->fes = 20;
        $form->birthdate = date('Y-m-d');
        $page->setForm($form);
        $post = $this->runRESTfulPage($page, $form);

        $this->connection->selectDB($this->config->get('OP_DB'));
        $emp = new EmployeesModel($this->connection);
        $emp->FirstName($form->fname);
        $emp->LastName($form->lname);
        $emp->frontendsecurity($form->fes);
        $emp->backendsecurity($form->fes);
        $emp->birthdate($form->birthdate);
        $phpunit->assertNotEquals(0, count($emp->find()));
    }
}

