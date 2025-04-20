<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDynroute extends baseTables
{
	// case 'dynroute':
    //     foreach ($results as $dynroute)
    //     {
    //         $id = $dynroute['id'];
    //         $dproute['dynroute'][$id] = $dynroute;
    //         $this->dpplog(9, "dynroute=$id");
    //     }
    // break;
    
    public function __construct($dpp)
    {
        parent::__construct($dpp, "dynroute", true);
        $this->key_id   = "id";
        $this->key_name = "dynroute";
    }
}