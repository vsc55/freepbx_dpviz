<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableQueueprio extends baseTables
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queueprio", true);
        $this->key_id   = "queueprio_id";
        $this->key_name = "queueprio";
    }
}