<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDirectoryDetails extends baseTables
{
    public function __construct($dpp)
    {
        parent::__construct($dpp, "directory_details", true);
        $this->key_id   = "id";
        $this->key_name = "directory";
    }
}