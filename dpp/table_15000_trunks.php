<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableTrunks extends BaseTables
{
    # Trunks
    public const PRIORITY = 15000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "trunks");
        $this->key_id   = "trunkid";
        $this->key_name = "trunk";
    }
}
