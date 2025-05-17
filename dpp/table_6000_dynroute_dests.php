<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableDynrouteDests extends BaseTables
{
    public const PRIORITY = 6000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "dynroute_dests", true);
        $this->key_id   = "dynroute_id";
        $this->key_name = "dynroute";

        $this->deppendencies = [
            'TableDynroute' => 'dynroute'
        ];
    }

    public function callbackLoad()
    {
        foreach ($this->getTableData() as $dynroute_dests) {
            if (!$this->checkItemLoad($dynroute_dests)) {
                continue;
            }
            $id    = $dynroute_dests[$this->key_id];
            $selid = $dynroute_dests['selection'];
            if ($this->skipIfEmptyAny([$id => $this->key_id, $selid => 'selection'])) {
                continue;
            }

            $this->route[$this->key_name][$id]['routes'][$selid] = $dynroute_dests;
            $this->logRoute($id, false, '{action}  >>  {table} route  >  id [{id}]    selid [{selid}]', ['{selid}' => $selid], 9);
        }
        return true;
    }
}
