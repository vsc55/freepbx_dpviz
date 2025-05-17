<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableRecordings extends BaseTables
{
    # Recordings
    public const PRIORITY = 3000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "recordings");
        $this->key_id   = "id";
        $this->key_name = "recordings";
    }
}
