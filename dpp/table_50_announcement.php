<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableAnnouncement extends baseTables
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "announcement", true);
        $this->key_id      = "announcement_id";
        $this->key_name    = "announcements";
    }

    public function callback_load()
    {
        foreach($this->getTableData() as $an)
		{
            $id   = $an[$this->key_id];
            $dest = $an['post_dest'];
            $this->route[$this->key_name][$id] = $an;
            $this->route[$this->key_name][$id]['dest'] = $dest;

            $this->log(9, sprintf("announcement dest:  an=%s   dest=%s", $id, $dest));
		}
        return true;
    }
}