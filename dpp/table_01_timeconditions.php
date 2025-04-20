<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableTimeconditions extends baseTables
{
    // # Time Conditions
    // $sql = sprintf("SELECT * FROM %s", "timeconditions");
    // foreach($results as $tc)
    // {
    // 	$id = $tc['timeconditions_id'];
    // 	$dproute['timeconditions'][$id] = $tc;
    // }

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "timeconditions");
        $this->key_id   = "timeconditions_id";
        $this->key_name = "timeconditions";
    }
}