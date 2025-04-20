<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableQueuesDynamic extends baseTables
{
   	// # Queue members (dynamic) //options
    // if ($dynmembers && !empty($dproute['queues']))
    // {
    // 	foreach ($dproute['queues'] as $id=>$details)
    // 	{
    // 		$dynmem=array();
            
    // 		$D='/usr/sbin/asterisk -rx "database show QPENALTY '.$id.'" | grep \'/agents/\' | cut -d\'/\' -f5 | cut -d\':\' -f1';
    // 		exec($D, $dynmem);

    // 		foreach ($dynmem as $enum)
    // 		{
    // 			$dproute['queues'][$id]['members']['dynamic'][] = $enum;
    // 		}
    // 	}
    // }
    
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queues_dynamic");
    }

    public function callback_load()
    {
        global $dynmembers;
        # Queue members (dynamic) //options
		if ($dynmembers && !empty($this->route['queues']))
		{
			foreach ($this->route['queues'] as $id=>$details)
			{
				$dynmem=array();
				
				$D= sprintf('/usr/sbin/asterisk -rx "database show QPENALTY %s" | grep \'/agents/\' | cut -d\'/\' -f5 | cut -d\':\' -f1', $id);
				exec($D, $dynmem);

				foreach ($dynmem as $enum)
				{
					$this->route['queues'][$id]['members']['dynamic'][] = $enum;
				}
			}
		}
    }
}