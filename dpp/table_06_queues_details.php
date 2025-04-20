<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableQueuesDetails extends baseTables
{
    # Queue members (static)
    
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queues_details");
        $this->key_id   = "id";
        $this->key_name = "queues";
    }

    public function callback_load()
    {
        foreach($this->getTableData() as $qd)
		{
            $id = $qd[$this->key_id];

            if ($qd['keyword'] == 'member')
            {
                $member = $qd['data'];
                if (preg_match("/Local\/(\d+)/", $member, $matches))
                {
                    $enum = $matches[1];
                    $this->route[$this->key_name][$id]['members']['static'][] = $enum;
                }
            }
            else
            {
                $this->route[$this->key_name][$id]['data'][$qd['keyword']] = $qd['data'];
            }
		}
        return true;
    }
}