<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableQueuesConfig extends baseTables
{
    # Queues

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
			$id = $q[$this->key_id];
            $this->route[$this->key_name][$id] = $q;
            $this->route[$this->key_name][$id]['members']['static']  = array(); // table_06_queues_static.php
            $this->route[$this->key_name][$id]['members']['dynamic'] = array(); // table_07_queues_dynamic.php
		}
        return true;
    }
}