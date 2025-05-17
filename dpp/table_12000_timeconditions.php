<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableTimeconditions extends BaseTables
{
    # Time Conditions
    public const PRIORITY = 12000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "timeconditions");
        $this->key_id   = "timeconditions_id";
        $this->key_name = "timeconditions";
    }
}
