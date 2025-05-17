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

    public function callbackLoad(&$dproute)
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


            // $id = $an[$this->key_id];
            // $dest = $an['post_dest'];
            // $dproute[$this->key_name][$id] = $an;
            // $dproute[$this->key_name][$id]['dest'] = $dest;

            // $this->log(9, sprintf("announcement dest:  an=%s   dest=%s", $id, $dest));
        }
        return true;
    }
}
