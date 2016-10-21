<?php

/**
 * @backupGlobals disabled
 */
class SearchTest extends PHPUnit_Framework_TestCase
{
    public function testProductSearch()
    {
        $mods = AutoLoader::ListModules('COREPOS\\pos\\lib\\Search\\Products\\ProductSearch', true);

        foreach($mods as $class){
            $obj = new $class();

            $this->assertInstanceOf('COREPOS\\pos\\lib\\Search\\Products\\ProductSearch',$obj);

            $this->assertInternalType('integer',$obj->result_size);

            $search_terms = array('0000000004011', 'BANANA');

            foreach($search_terms as $term) {
                $results = $obj->search($term);
                $this->assertInternalType('array', $results);
                foreach($results as $result) {
                    $this->assertInternalType('array', $result);
                    $this->assertArrayHasKey('upc', $result);
                    $this->assertArrayHasKey('description', $result);
                    $this->assertArrayHasKey('normal_price', $result);
                    $this->assertArrayHasKey('scale', $result);
                }
            }
        }
    }

    public function testMemberSearch()
    {
        $mods = AutoLoader::ListModules('COREPOS\\pos\\lib\\MemberLookup', true);

        foreach($mods as $class){
            $obj = new $class();

            $this->assertInstanceOf('COREPOS\\pos\\lib\\MemberLookup',$obj);

            $this->assertInternalType('boolean', $obj->handle_numbers());
            $this->assertInternalType('boolean', $obj->handle_text());

            $search_terms = array('JOHNSON', 1, 2354, 'RO');
            foreach($search_terms as $term) {
                if (is_numeric($term) && !$obj->handle_numbers()) {
                    continue;
                } else if (!is_numeric($term) && !$obj->handle_text()) {
                    continue;
                }
                
                $result = array();
                if (is_numeric($term)) {
                    $result = $obj->lookup_by_number($term);
                } else {
                    $result = $obj->lookup_by_text($term);
                }

                $this->assertInternalType('array', $result);
                $this->assertArrayHasKey('url', $result);
                $this->assertArrayHasKey('results', $result);
                $this->assertThat($result['url'],
                    $this->logicalOr(
                        $this->isType('boolean',$result['url']),
                        $this->isType('string',$result['url'])
                    )
                );
                $this->assertInternalType('array', $result['results']);

                foreach($result['results'] as $key => $value) {
                    $this->assertInternalType('string', $key);
                    $this->assertInternalType('string', $value);
                    $this->assertRegExp('/\d+::\d+/', $key);
                }
            }
        }
    }
}
