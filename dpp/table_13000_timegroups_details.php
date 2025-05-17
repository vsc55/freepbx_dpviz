<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableTimegroupsDetails extends BaseTables
{
    # Time Groups Details
    public const PRIORITY = 13000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "timegroups_details");
        $this->key_id   = "timegroupid";
        $this->key_name = "timegroups";

        $this->deppendencies = [
            'TableTimegroupsGroups' => 'timegroups',
        ];
    }

    public function callbackLoad()
    {
        foreach ($this->getTableData() as $tgd) {
            if (!$this->checkItemLoad($tgd)) {
                continue;
            }

            $id = $tgd[$this->key_id];
            if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                continue;
            }

            if (! isset($this->route[$this->key_name][$id])) {
                $this->log(1, sprintf("timegroups_details id found for unknown timegroup, id=%s", $id));
                continue;
            }

            $this->route[$this->key_name][$id]['time'] ??= "";

            $exploded = explode("|", $tgd['time']);
            $time     = ($exploded[0] !== "*") ? $exploded[0] : "";
            if ($exploded[1] !== "*") {
                $dow_parts = explode("-", $exploded[1]);
                foreach ($dow_parts as &$part) {
                    $part = ucfirst($part);
                }
                $dow = implode("-", $dow_parts) . ", ";
            } else {
                $dow = "";
            }
            $date     = ($exploded[2] !== "*") ? sprintf("%s ", $exploded[2]) : "";
            $month    = ($exploded[3] !== "*") ? sprintf("%s ", ucfirst($exploded[3])) : "";

            $this->route[$this->key_name][$id]['time'] .= sprintf("%s%s%s%s\\n", $dow, $month, $date, $time);
            $this->logRoute(
                $id,
                false,
                '{action}  >>  {table} time  >  id [{id}]    dow [{dow}]    month [{month}]    date [{date}]    time [{time}]',
                [
                    '{dow}' => $dow,
                    '{month}' => $month,
                    '{date}' => $date,
                    '{time}' => $time,
                ],
                9
            );
        }
        return true;
    }
}
