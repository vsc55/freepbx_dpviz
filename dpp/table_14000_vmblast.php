<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableVmblast extends BaseTables
{
    public const PRIORITY = 14000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "vmblast", true);
        $this->key_id   = "grpnum";
        $this->key_name = "vmblast";
    }
}
