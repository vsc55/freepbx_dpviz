<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDisa extends baseTables
{
    public function __construct($dpp)
    {
        parent::__construct($dpp, "disa", true);
        $this->key_id   = "disa_id";
        $this->key_name = "disa";
    }
}