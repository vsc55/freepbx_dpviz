<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableQueuesConfig extends BaseTables
{
    # Queues
    public const PRIORITY = 10000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queues_config", true);
        $this->key_id   = "extension";
        $this->key_name = "queues";
    }

    public function callbackLoad(&$dproute)
    {
        foreach ($this->getTableData() as $result) {
            if (!$this->checkItemLoad($result)) {
                continue;
            }
            $id  = $result[$this->key_id];
            if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                continue;
            }
            $item = $result;
            $item['members']['static']  = array();
            $item['members']['dynamic'] = array();
            $this->setRoute($id, $item);




            // $id = $result[$this->key_id];

            // $dproute[$this->key_name][$id] = $result;
            // $dproute[$this->key_name][$id]['members']['static']  = array();
            // $dproute[$this->key_name][$id]['members']['dynamic'] = array();
        }
        return true;
    }
}
