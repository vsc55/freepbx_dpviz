<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableAnnouncement extends BaseTables
{
    public const PRIORITY = 3500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "announcement", true);
        $this->key_id      = "announcement_id";
        $this->key_name    = "announcements";
    }

    public function callbackLoad()
    {
        foreach ($this->getTableData() as $an) {
            if (!$this->checkItemLoad($an)) {
                continue;
            }

            $id = $an[$this->key_id];
            if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                continue;
            }

            $item = $an;
            $item['dest'] = $an['post_dest'];
            $this->setRoute($id, $item, false, true, '{action}  >>  {table} dest  >  an [{id}]    dest [{dest}]', ['{dest}' => $item['dest']], 9);
        }
        return true;
    }
}
