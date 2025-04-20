<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableMiscdests extends baseTables
{
    // case 'miscdests':
    //     foreach($results as $miscdest)
    //     {
    //         $id = $miscdest['id'];
    //         $dproute['miscdest'][$id] = $miscdest;
    //         $this->dpplog(9, "miscdest dest: $id");
    //     }
    // break;

    public function __construct($dpp)
    {
        parent::__construct($dpp, "miscdests", true);
        $this->key_id   = "id";
        $this->key_name = "miscdest";
    }
}