<?php
/**
 * @backupGlobals disabled
 */
class SQLManagerTest extends PHPUnit_Framework_TestCase
{
	public function testMethods(){
		global $CORE_LOCAL;

		$sql = Database::pDataConnect();

		/* test create connection */
		$this->assertInstanceOf('SQLManager',$sql);
		$this->assertObjectHasAttribute('connections',$sql);
		$this->assertInternalType('array',$sql->connections);
		$this->assertArrayHasKey($CORE_LOCAL->get('pDatabase'),$sql->connections);
		$con = $sql->connections[$CORE_LOCAL->get('pDatabase')];
		// mysql gives resource; PDO gives object
		$constraint = $this->logicalOr(
			$this->isType('resource',$con),
			$this->isType('object',$con)
		);
		$this->assertThat($con, $constraint);
		
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

		$type = $sql->field_type($result,0);
		$constraint = $this->logicalOr(
			$this->equalTo('int'),
			$this->equalTo('longlong')
		);
		$this->assertThat($type, $constraint);
		//$this->assertEquals('int',$type);

		/* test various fetch methods */
		$array = $sql->fetch_array($result);
		$this->assertNotEquals(False,$array);
		$this->assertArrayHasKey(0,$array);
		$this->assertArrayHasKey('one',$array);
		$this->assertEquals(1,$array[0]);
		$this->assertEquals(1,$array['one']);

		/* repeat test query for next fetch */
		$result = $sql->query("SELECT 1 as one");
		$this->assertNotEquals(False,$result);

		$array = $sql->fetch_row($result);
		$this->assertNotEquals(False,$array);
		$this->assertArrayHasKey(0,$array);
		$this->assertArrayHasKey('one',$array);
		$this->assertEquals(1,$array[0]);
		$this->assertEquals(1,$array['one']);

		$now = $sql->now();
		$this->assertInternalType('string',$now);
		$this->assertNotEquals('',$now);

		$datediff = $sql->datediff('d1','d2');
		$this->assertInternalType('string',$datediff);
		$this->assertNotEquals('',$datediff);

		$dow = $sql->dayofweek('col1');
		$this->assertInternalType('string',$dow);
		$this->assertNotEquals(False,$dow);

		$convert = $sql->convert("'1'",'INT');
		$this->assertInternalType('string',$convert);
		$this->assertNotEquals('',$convert);

		$concat = $sql->concat('col1','col2','');
		$this->assertInternalType('string',$concat);
		$this->assertNotEquals('',$concat);

		$sep = $sql->sep();
		$this->assertInternalType('string',$sep);
		$this->assertNotEquals('',$sep);

		$error = $sql->error();
		$this->assertInternalType('string',$error);
		$this->assertEquals('',$error);

		/* bad query on purpose */
		$fail = $sql->query("DO NOT SELECT 1");
		$this->assertEquals(False,$fail);

		$error = $sql->error();
		$this->assertInternalType('string',$error);
		$this->assertNotEquals('',$error);

		/* prepared statements */
		$prep = $sql->prepare_statement("SELECT ? as col");
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
