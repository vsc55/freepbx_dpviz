<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableQueuesDynamic extends baseTables
{
   	# Queue members (dynamic) //options
    
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queues_dynamic");
        $this->key_id   = "id";
        $this->key_name = "queues";
    }

    public function callback_load()
    {
        //TODO: $dynmembers= isset($options[0]['dynmembers']) ? $options[0]['dynmembers'] : '0';
        global $dynmembers;
        # Queue members (dynamic) //options
		if ($dynmembers && !empty($this->route[$this->key_name]))
		{
			foreach ($this->route[$this->key_name] as $id => $details)
			{
				$dynmem = array();
				$D      = sprintf('/usr/sbin/asterisk -rx "database show QPENALTY %s" | grep \'/agents/\' | cut -d\'/\' -f5 | cut -d\':\' -f1', $id);
				exec($D, $dynmem);

				foreach ($dynmem as $enum)
				{
					$this->route[$this->key_name][$id]['members']['dynamic'][] = $enum;
				}
			}
		}
        return true;
    }
}