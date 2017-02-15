<?php
define('NUM_BIG_BLOCK_DEPOT_BLOCKS_POS', 0x2c);
define('SMALL_BLOCK_DEPOT_BLOCK_POS', 0x3c);
define('ROOT_START_BLOCK_POS', 0x30);
define('BIG_BLOCK_SIZE', 0x200);
define('SMALL_BLOCK_SIZE', 0x40);
define('EXTENSION_BLOCK_POS', 0x44);
define('NUM_EXTENSION_BLOCK_POS', 0x48);
define('PROPERTY_STORAGE_BLOCK_SIZE', 0x80);
define('BIG_BLOCK_DEPOT_BLOCKS_POS', 0x4c);
define('SMALL_BLOCK_THRESHOLD', 0x1000);
// property storage offsets
define('SIZE_OF_NAME_POS', 0x40);
define('TYPE_POS', 0x42);
define('START_BLOCK_POS', 0x74);
define('SIZE_POS', 0x78);
define('IDENTIFIER_OLE', pack("CCCCCCCC",0xd0,0xcf,0x11,0xe0,0xa1,0xb1,0x1a,0xe1));

//echo 'ROOT_START_BLOCK_POS = '.ROOT_START_BLOCK_POS."\n";


class OLERead {
    var $data = '';
    
    function OLERead()
    {
    }

    public function GetInt4d($data, $pos)
    {
        $value = ord($data[$pos]) | (ord($data[$pos+1])	<< 8) | (ord($data[$pos+2]) << 16) | (ord($data[$pos+3]) << 24);
        if ($value>=4294967294)
        {
            $value=-2;
        }
        return $value;
    }

    function openValidFile($sFileName)
    {
    	// check if file exist and is readable (Darko Miljanovic)
    	if (!is_readable($sFileName)) {
    		$this->error = 1;
    		return false;
    	}
    	$this->data = file_get_contents($sFileName);
    	if (!$this->data) { 
    		$this->error = 1; 
    		return false; 
   		}
   		if (substr($this->data, 0, 8) != IDENTIFIER_OLE) {
    		$this->error = 1; 
    		return false; 
   		}

        return true;
    }

    private function readDepotBlocks()
    {
        $bigBlockDepotBlocks = array();
        $pos = BIG_BLOCK_DEPOT_BLOCKS_POS;
        $bbdBlocks = $this->numBigBlockDepotBlocks;
        
        if ($this->numExtensionBlocks != 0) {
            $bbdBlocks = (BIG_BLOCK_SIZE - BIG_BLOCK_DEPOT_BLOCKS_POS)/4; 
        }

        for ($i = 0; $i < $bbdBlocks; $i++) {
              $bigBlockDepotBlocks[$i] = $this->GetInt4d($this->data, $pos);
              $pos += 4;
        }
        
        for ($j = 0; $j < $this->numExtensionBlocks; $j++) {
            $pos = ($this->extensionBlock + 1) * BIG_BLOCK_SIZE;
            $blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, BIG_BLOCK_SIZE / 4 - 1);

            for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; $i++) {
                $bigBlockDepotBlocks[$i] = $this->GetInt4d($this->data, $pos);
                $pos += 4;
            }   

            $bbdBlocks += $blocksToRead;
            if ($bbdBlocks < $this->numBigBlockDepotBlocks) {
                $this->extensionBlock = $this->GetInt4d($this->data, $pos);
            }
        }

        return $bigBlockDepotBlocks;
    }

    private function readBigBlocks()
    {
        $pos = 0;
        $index = 0;
        $this->bigBlockChain = array();
        $bigBlockDepotBlocks = $this->readDepotBlocks();
        
        for ($i = 0; $i < $this->numBigBlockDepotBlocks; $i++) {
            $pos = ($bigBlockDepotBlocks[$i] + 1) * BIG_BLOCK_SIZE;
            for ($j = 0 ; $j < BIG_BLOCK_SIZE / 4; $j++) {
                $this->bigBlockChain[$index] = $this->GetInt4d($this->data, $pos);
                $pos += 4 ;
                $index++;
            }
        }
    }

    private function readSmallBlocks()
    {
        $pos = 0;
	    $index = 0;
	    $sbdBlock = $this->sbdStartBlock;
	    $this->smallBlockChain = array();
	
	    while ($sbdBlock != -2) {
	
	      $pos = ($sbdBlock + 1) * BIG_BLOCK_SIZE;
	
	      for ($j = 0; $j < BIG_BLOCK_SIZE / 4; $j++) {
	        $this->smallBlockChain[$index] = $this->GetInt4d($this->data, $pos);
	        $pos += 4;
	        $index++;
	      }
	
	      $sbdBlock = $this->bigBlockChain[$sbdBlock];
	    }
    }
    
    function read($sFileName)
    {
        if ($this->openValidFile($sFileName) === false) {
            return false;
        }
    	
        $this->numBigBlockDepotBlocks = $this->GetInt4d($this->data, NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);
        $this->sbdStartBlock = $this->GetInt4d($this->data, SMALL_BLOCK_DEPOT_BLOCK_POS);
        $this->rootStartBlock = $this->GetInt4d($this->data, ROOT_START_BLOCK_POS);
        $this->extensionBlock = $this->GetInt4d($this->data, EXTENSION_BLOCK_POS);
        $this->numExtensionBlocks = $this->GetInt4d($this->data, NUM_EXTENSION_BLOCK_POS);
        
        $this->readBigBlocks();
        $this->readSmallBlocks();

        $block = $this->rootStartBlock;
        $pos = 0;
        $this->entry = $this->__readData($block);
        
        $this->__readPropertySets();
    }
    
     function __readData($bl) {
        $block = $bl;
        $pos = 0;
        $data = '';
        
        while ($block != -2)  {
            $pos = ($block + 1) * BIG_BLOCK_SIZE;
            $data = $data.substr($this->data, $pos, BIG_BLOCK_SIZE);
            //echo "pos = $pos data=$data\n";	
            if (!isset($this->bigBlockChain[$block])) break;
	    $block = $this->bigBlockChain[$block];
        }
		return $data;
     }
        
    function __readPropertySets()
    {
        $offset = 0;
        while ($offset < strlen($this->entry)) {
            $data = substr($this->entry, $offset, PROPERTY_STORAGE_BLOCK_SIZE);
            $props = $this->propertyToArray($data);
            $this->props[] = $props;

            if (($props['name'] == "Workbook") || ($props['name'] == "Book")) {
                $this->wrkbook = count($this->props) - 1;
            }

            if ($props['name'] == "Root Entry") {
                $this->rootentry = count($this->props) - 1;
            }
            
            $offset += PROPERTY_STORAGE_BLOCK_SIZE;
        }
    }

    private function propertyToArray($data)
    {
        $nameSize = ord($data[SIZE_OF_NAME_POS]) | (ord($data[SIZE_OF_NAME_POS+1]) << 8);
        $type = ord($data[TYPE_POS]);
        $startBlock = $this->GetInt4d($data, START_BLOCK_POS);
        $size = $this->GetInt4d($data, SIZE_POS);

        $name = '';
        for ($i = 0; $i < $nameSize ; $i++) {
            $name .= $data[$i];
        }
        $name = str_replace("\x00", "", $name);

        return array (
            'name' => $name, 
            'type' => $type,
            'startBlock' => $startBlock,
            'size' => $size,
        );
    }
   
    // added to elminate near-duplicate loops
    // in getWorkBook()
    private function getBlockStream($data, $startBlock, $blockChain, $blockSize)
    {
        $streamData = '';
        $pos = 0;
        while ($startBlock != -2) {
              $pos = $startBlock * $blockSize;
              if ($blockSize == BIG_BLOCK_SIZE) {
                  $pos = ($startBlock + 1) * $blockSize;
              }
              $streamData .= substr($data, $pos, $blockSize);
              $startBlock = $blockChain[$startBlock];
        }

        return $streamData;
    }

    private function getSmallBlock($rootdata, $block, $chain, $size)
    {
        $streamData = '';
        //$count = 0;
        $pos = 0;
        while ($block != -2) {
            $pos = $block * $size;
            $streamData .= substr($rootdata, $pos, $size);
            $block = $chain[$block];
        }
            
        return $streamData;
    }
    
    function getWorkBook()
    {
    	if ($this->props[$this->wrkbook]['size'] < SMALL_BLOCK_THRESHOLD) {
			$rootdata = $this->__readData($this->props[$this->rootentry]['startBlock']);
	        $block = $this->props[$this->wrkbook]['startBlock'];
            return $this->getSmallBlock($rootdata, $block, $this->smallBlockChain, SMALL_BLOCK_SIZE);
    	} else {
	        $numBlocks = $this->props[$this->wrkbook]['size'] / BIG_BLOCK_SIZE;
	        if ($this->props[$this->wrkbook]['size'] % BIG_BLOCK_SIZE != 0) {
	            $numBlocks++;
	        }
	        
	        if ($numBlocks == 0) return '';
	        
	        $block = $this->props[$this->wrkbook]['startBlock'];
            return $this->getBlockStream($this->data, $block, $this->bigBlockChain, BIG_BLOCK_SIZE);
    	}
    }
    
}

