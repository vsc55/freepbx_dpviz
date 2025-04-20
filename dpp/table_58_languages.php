<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableLanguages extends baseTables
{
    public function __construct($dpp)
    {
        parent::__construct($dpp, "languages", true);
        $this->key_id   = "language_id";
        $this->key_name = "languages";
    }
}