<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableIvrEntries extends BaseTables
{
    # IVR entries
    public const PRIORITY = 2500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "ivr_entries");
        $this->key_id   = "ivr_id";
        $this->key_name = "ivrs";

        $this->deppendencies = [
            'TableIvrDetails' => 'ivrs',
        ];
    }

    public function callbackLoad(&$dproute)
    {
        foreach ($this->getTableData() as $ent) {
            if (!$this->checkItemLoad($ent)) {
                continue;
            }

            $id    = $ent[$this->key_id] ?? null;
            $selid = $ent['selection']   ?? null;

            if ($this->skipIfEmptyAny([$id => 'ivr_id', $selid => 'selection'])) {
                continue;
            }

            $isNew = !isset($this->route[$this->key_name][$id]['entries'][$selid]);
            $this->route[$this->key_name][$id]['entries'][$selid] = $ent;

            $this->logRoute($id, $isNew, '{action}  >>  {table} entry  >  ivr [{id}]    selid [{selid}]', ['{selid}' => $selid], 9);


            // $id    = $ent[$this->key_id];
            // $selid = $ent['selection'];
            // $dproute[$this->key_name][$id]['entries'][$selid] = $ent;
            // $this->log(9, sprintf("entry:  ivr=%s   selid=%s", $id, $selid));
        }
        return true;
    }
}
