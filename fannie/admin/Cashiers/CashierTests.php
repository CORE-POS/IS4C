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

        $form->_method = 'post';
        $form->lname = 'test';
        $form->fname = 'cashier';
        $form->fes = 20;
        $form->birthdate = date('Y-m-d');
        $page->setForm($form);
        $post = $this->runRESTfulPage($page, $form);

        $this->connection->selectDB($this->config->get('OP_DB'));
        $emp = new EmployeesModel($this->connection);
        $emp->FirstName($this->fname);
        $emp->LastName($this->lname);
        $emp->frontendsecurity($this->fes);
        $emp->backendsecurity($this->fes);
        $emp->birthdate($this->birthdate);
        $phpunit->assertNotEquals(0, count($emp->find()));
    }
}

