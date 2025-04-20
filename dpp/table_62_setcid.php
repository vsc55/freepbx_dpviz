<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableSetcid extends baseTables
{
    // case 'setcid':
    //     foreach($results as $cid)
    //     {
    //         $id = $cid['cid_id'];
    //         $dproute['setcid'][$id] = $cid;
    //     }
    // break;

    public function __construct($dpp)
    {
        parent::__construct($dpp, "setcid", true);
        $this->key_id   = "cid_id";
        $this->key_name = "setcid";
    }
}