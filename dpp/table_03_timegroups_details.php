<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableTimegroupsDetails extends baseTables
{
    // # Time Groups Details
    // $sql = sprintf("SELECT * FROM %s", "timegroups_details");
    // foreach($results as $tgd)
    // {
    // 	$id = $tgd['timegroupid'];
    // 	if (! isset($dproute['timegroups'][$id]))
    // 	{
    // 		$this->dpplog(1, "timegroups_details id found for unknown timegroup, id=$id");
    // 	}
    // 	else
    // 	{
    // 		if (!isset($dproute['timegroups'][$id]['time']))
    // 		{
    // 			$dproute['timegroups'][$id]['time'] = '';
    // 		}
    // 		$exploded = explode("|", $tgd['time']);
    // 		$time 	  = ($exploded[0] !== '*') ? $exploded[0] : '';
    // 		$dow 	  = ($exploded[1] !== '*') ? sprintf("%s, ", ucwords($exploded[1], '-')) : '';
    // 		$date 	  = ($exploded[2] !== '*') ? sprintf("%s ", $exploded[2]) : '';
    // 		$month 	  = ($exploded[3] !== '*') ? sprintf("%s ", ucfirst($exploded[3])) : '';

    // 		$dproute['timegroups'][$id]['time'] .= sprintf("%s%s%s%s\\l", $dow, $month, $date, $time);
    // 	}
    // }

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "timegroups_details");
        $this->key_id   = "timegroupid";
        $this->key_name = "timegroups";
    }

    public function callback_load()
    {
        # Time Groups Details
        foreach($this->getTableData() as $tgd)
		{
			$id = $tgd['timegroupid'];
			if (! isset($this->route['timegroups'][$id]))
			{
				$this->log(1, sprintf("timegroups_details id found for unknown timegroup, id=%s", $id));
                continue;
			}
			
            if (!isset($this->route['timegroups'][$id]['time']))
            {
                $this->route['timegroups'][$id]['time'] = '';
            }
            $exploded = explode("|", $tgd['time']);
            $time 	  = ($exploded[0] !== '*') ? $exploded[0] : '';
            $dow 	  = ($exploded[1] !== '*') ? sprintf("%s, ", ucwords($exploded[1], '-')) : '';
            $date 	  = ($exploded[2] !== '*') ? sprintf("%s ", $exploded[2]) : '';
            $month 	  = ($exploded[3] !== '*') ? sprintf("%s ", ucfirst($exploded[3])) : '';

            $this->route['timegroups'][$id]['time'] .= sprintf("%s%s%s%s\\l", $dow, $month, $date, $time);
		}
    }
}