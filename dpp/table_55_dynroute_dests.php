<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDynrouteDests extends baseTables
{
    public function __construct($dpp)
    {
        parent::__construct($dpp, "dynroute_dests", true);
        $this->key_id   = "dynroute_id";
        $this->key_name = "dynroute";
    }

    public function callback_load(&$dproute)
    {
        foreach($this->getTableData() as $dynroute_dests)
		{
            $id    = $dynroute_dests[$this->key_id];
            $selid = $dynroute_dests['selection'];
            
            $dproute[$this->key_name][$id]['routes'][$selid] = $dynroute_dests;

            $this->log(9, "dynroute_dests: dynroute=$id match=$selid");
		}
        return true;
    }
}