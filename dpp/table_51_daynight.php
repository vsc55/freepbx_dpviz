<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDaynight extends baseTables
{
    // case 'daynight':
    //     foreach($results as $daynight)
    //     {
    //         $id = $daynight['ext'];
    //         $dproute['daynight'][$id][] = $daynight;
    //         $this->dpplog(9, "daynight=$id");
    //     }
    // break;
    
    public function __construct($dpp)
    {
        parent::__construct($dpp, "daynight", true);
        $this->key_id   = "ext";
        $this->key_name = "daynight";
    }
}