<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDynrouteDests extends baseTables
{
    // case 'dynroute_dests':
    //     foreach ($results as $dynroute_dests)
    //     {
    //         $id = $dynroute_dests['dynroute_id'];
    //         $selid = $dynroute_dests['selection'];
    //         $this->dpplog(9, "dynroute_dests: dynroute=$id match=$selid");
    //         $dproute['dynroute'][$id]['routes'][$selid] = $dynroute_dests;
    //     }
    // break;

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
            $id    = $dynroute_dests['dynroute_id'];
            $selid = $dynroute_dests['selection'];
            $this->log(9, "dynroute_dests: dynroute=$id match=$selid");
            $dproute['dynroute'][$id]['routes'][$selid] = $dynroute_dests;
		}
    }
}