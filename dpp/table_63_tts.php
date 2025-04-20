<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableTts extends baseTables
{
    public function __construct($dpp)
    {
        parent::__construct($dpp, "tts", true);
        $this->key_id   = "id";
        $this->key_name = "tts";
    }
}