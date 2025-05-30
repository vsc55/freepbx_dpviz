<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableLanguages extends BaseTables
{
    public const PRIORITY = 8000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "languages", true);
        $this->key_id   = "language_id";
        $this->key_name = "languages";
    }
}
