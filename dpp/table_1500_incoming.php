<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableIncoming extends baseTables
{
    # Inbound Routes
    public const PRIORITY = 1500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "incoming");
        $this->key_id   = "extension";
        $this->key_name = "incoming";
    }

    public function callback_load(&$dproute)
    {
        foreach($this->getTableData() as $incoming)
        {
            $id = empty($incoming[$this->key_id]) ? 'ANY' : $incoming[$this->key_id];
            $dproute[$this->key_name][$id] = $incoming;
        }
        return true;
    }
}
