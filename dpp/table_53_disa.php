<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDisa extends baseTables
{
    // case 'disa':
    //     foreach($results as $disa)
    //     {
    //         $id = $disa['disa_id'];
    //         $dproute['disa'][$id] = $disa;
    //         $this->dpplog(9, "disa=$id");
    //     }
    // break;
    
    public function __construct($dpp)
    {
        parent::__construct($dpp, "disa", true);
        $this->key_id   = "disa_id";
        $this->key_name = "disa";
    }
}