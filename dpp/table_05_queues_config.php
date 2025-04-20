<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableQueuesConfig extends baseTables
{
    // # Queues
    // $sql = sprintf("SELECT * FROM %s", "queues_config");
    // foreach($results as $q)
    // {
    // 	$id = $q['extension'];
    // 	$dproute['queues'][$id] = $q;
    // 	$dproute['queues'][$id]['members']['static']  = array();
    // 	$dproute['queues'][$id]['members']['dynamic'] = array();
    // }
    
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queues_config");
        $this->key_id   = "extension";
        $this->key_name = "queues";
    }

    public function callback_load()
    {
        foreach($this->getTableData() as $q)
		{
			$id = $q['extension'];
            $this->route['queues'][$id] = $q;
            $this->route['queues'][$id]['members']['static']  = array(); // table_06_queues_static.php
            $this->route['queues'][$id]['members']['dynamic'] = array(); // table_07_queues_dynamic.php
		}
    }
}