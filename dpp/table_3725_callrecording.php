<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableCallrecording extends BaseTables
{
    public const PRIORITY = 3725;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "callrecording", true);
        $this->key_id      = "callrecording_id";
        $this->key_name    = "callrecording";
    }
}
