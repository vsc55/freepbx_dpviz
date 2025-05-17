<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableIvrDetails extends BaseTables
{
    # IVRs
    public const PRIORITY = 2000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "ivr_details");
        $this->key_id   = "id";
        $this->key_name = "ivrs";
    }
}
