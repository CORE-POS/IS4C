<?php

/**
 * @backupGlobals disabled
 */
class SQLManagerTest extends PHPUnit_Framework_TestCase
{
    public function testMethods()
    {
        $config = FannieConfig::factory();
        $OP_DB = $config->get('OP_DB');
        $sql = FannieDB::get($OP_DB);

        /* test create connection */
        $this->assertInstanceOf('SQLManager',$sql);
        $this->assertObjectHasAttribute('connections',$sql);
        $this->assertInternalType('array',$sql->connections);
        $this->assertArrayHasKey($OP_DB ,$sql->connections);
        $this->assertInstanceOf('ADOConnection',$sql->connections[$OP_DB]);
        
        /* test query */
        $result = $sql->query("SELECT 1 as one");
        $this->assertNotEquals(False,$result);

        $escape = $sql->escape('some str');
        $this->assertInternalType('string',$escape);

        $rows = $sql->num_rows($result);
        $this->assertNotEquals(False,$rows);
        $this->assertEquals(1,$rows);

        $fields = $sql->num_fields($result);
        $this->assertNotEquals(False,$fields);
        $this->assertEquals(1,$fields);

        // field type naming not consistent accross db drivers
        //$type = $sql->field_type($result,0);
        //$this->assertEquals('int',$type);

        $name = $sql->field_name($result,0);
        $this->assertEquals('one',$name);

        $aff = $sql->affected_rows();
        $this->assertNotEquals(False,$aff);
        $this->assertEquals(1,$aff);

        /* test various fetch methods */
        $array = $sql->fetch_array($result);
        $this->assertNotEquals(False,$array);
        $this->assertArrayHasKey(0,$array);
        $this->assertArrayHasKey('one',$array);
        $this->assertEquals(1,$array[0]);
        $this->assertEquals(1,$array['one']);

        /** PDO does not support seek */
        //$seek = $sql->data_seek($result,0);
        //$this->assertNotEquals(false, $seek);
        $result = $sql->query("SELECT 1 as one");
        $this->assertNotEquals(False,$result);

        $array = $sql->fetch_row($result);
        $this->assertNotEquals(False,$array);
        $this->assertArrayHasKey(0,$array);
        $this->assertArrayHasKey('one',$array);
        $this->assertEquals(1,$array[0]);
        $this->assertEquals(1,$array['one']);

        /** PDO does not support seek */
        $result = $sql->query("SELECT 1 as one");
        $this->assertNotEquals(False,$result);

        $obj = $sql->fetch_object($result);
        $this->assertNotEquals(False,$obj);
        $this->assertInstanceof('ADOFetchObj',$obj);
        $this->assertObjectHasAttribute('one',$obj);
        $this->assertEquals(1,$obj->one);

        /** PDO does not support seek */
        $result = $sql->query("SELECT 1 as one");
        $this->assertNotEquals(False,$result);

        $field = $sql->fetch_field($result,0);
        $this->assertNotEquals(False,$field);
        $this->assertInternalType('object',$field);
        $this->assertObjectHasAttribute('name',$field);
        $this->assertEquals(1,$field->max_length);

        $now = $sql->now();
        $this->assertInternalType('string',$now);
        $this->assertNotEquals('',$now);

        $datediff = $sql->datediff('d1','d2');
        $this->assertInternalType('string',$datediff);
        $this->assertNotEquals('',$datediff);

        $dateeq = $sql->date_equals('d1',date('Y-m-d'));
        $this->assertInternalType('string',$dateeq);
        $this->assertNotEquals('',$dateeq);

        $monthdiff = $sql->monthdiff('d1','d2');
        $this->assertInternalType('string',$monthdiff);
        $this->assertNotEquals('',$monthdiff);

        $seconddiff = $sql->seconddiff('d1','d2');
        $this->assertInternalType('string',$seconddiff);
        $this->assertNotEquals('',$seconddiff);

        $weekdiff = $sql->weekdiff('d1','d2');
        $this->assertInternalType('string',$weekdiff);
        $this->assertNotEquals('',$weekdiff);

        $dow = $sql->dayofweek('col1');
        $this->assertInternalType('string',$dow);
        $this->assertNotEquals(False,$dow);

        $ymd = $sql->dateymd('d1');
        $this->assertInternalType('string',$ymd);
        $this->assertNotEquals('',$ymd);

        $hour = $sql->hour('d1');
        $this->assertInternalType('string',$hour);
        $this->assertNotEquals('',$hour);

        $convert = $sql->convert("'1'",'INT');
        $this->assertInternalType('string',$convert);
        $this->assertNotEquals('',$convert);

        $locate = $sql->locate("'1'",'col_name');
        $this->assertInternalType('string',$locate);
        $this->assertNotEquals('',$locate);

        $concat = $sql->concat('col1','col2','');
        $this->assertInternalType('string',$concat);
        $this->assertNotEquals('',$concat);

        $currency = $sql->currency();
        $this->assertInternalType('string',$currency);
        $this->assertNotEquals('',$currency);

        $limit = $sql->add_select_limit("SELECT 1",1);
        $this->assertInternalType('string',$limit);
        $this->assertNotEquals('',$limit);

        $sep = $sql->sep();
        $this->assertInternalType('string',$sep);
        $this->assertNotEquals('',$sep);

        $error = $sql->error();
        $this->assertInternalType('string',$error);
        $this->assertEquals('',$error);

        /* bad query on purpose */
        ob_start();
        $fail = $sql->query("DO NOT SELECT 1");
        ob_end_clean();
        $this->assertEquals(False,$fail);

        $error = $sql->error();
        $this->assertInternalType('string',$error);
        $this->assertNotEquals('',$error);

        /* prepared statements */
        $prep = $sql->prepare_statement("SELECT ? AS testCol");
        $this->assertNotEquals(False,$prep);
        $exec = $sql->exec_statement($prep,array(2));
        $this->assertNotEquals(False,$exec);
        $row = $sql->fetch_row($exec);
        $this->assertNotEquals(False,$row);
        $this->assertInternalType('array',$row);
        $this->assertArrayHasKey(0,$row);
        $this->assertEquals(2,$row[0]);
    }
}
