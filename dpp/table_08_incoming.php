<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableIncoming extends baseTables
{
    // # Inbound Routes
    // $sql = sprintf("SELECT * FROM %s", "incoming");
    // foreach($results as $incoming)
    // {
    //     $id = $incoming['extension'];
    //     $dproute['incoming'][$id] = $incoming;
    // }	

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "incoming");
        $this->key_id   = "extension";
        $this->key_name = "incoming";
    }
}