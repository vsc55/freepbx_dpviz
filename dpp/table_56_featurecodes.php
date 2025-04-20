<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableFeaturecodes extends baseTables
{
    // case 'featurecodes':
    //     foreach($results as $featurecodes)
    //     {
    //         $id = $featurecodes['defaultcode'];
    //         $dproute['featurecodes'][$id] = $featurecodes;
    //         $this->dpplog(9, "featurecodes=$id");
    //     }
    // break;
    
    public function __construct($dpp)
    {
        parent::__construct($dpp, "featurecodes", true);
        $this->key_id   = "defaultcode";
        $this->key_name = "featurecodes";
    }
}