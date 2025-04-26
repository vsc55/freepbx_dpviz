<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDynroute extends baseTables
{
	public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "dynroute", true);
        $this->key_id   = "id";
        $this->key_name = "dynroute";
    }
}