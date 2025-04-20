<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableMeetme extends baseTables
{
    // case 'meetme':
    //     foreach($results as $meetme)
    //     {
    //         $id = $meetme['exten'];
    //         $dproute['meetme'][$id] = $meetme;
    //         $this->dpplog(9, "meetme dest:  conf=$id");
    //     }
    // break;

    public function __construct($dpp)
    {
        parent::__construct($dpp, "meetme", true);
        $this->key_id   = "exten";
        $this->key_name = "meetme";
    }
}