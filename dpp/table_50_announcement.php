<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableAnnouncement extends baseTables
{
    // case 'announcement':
    //     foreach($results as $an)
    //     {
    //         $id = $an['announcement_id'];
    //         $dproute['announcements'][$id] = $an;
    //         $dest = $an['post_dest'];
    //         $this->dpplog(9, "announcement dest:  an=$id   dest=$dest");
    //         $dproute['announcements'][$id]['dest'] = $dest;
    //     }
    // break;

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
            $id   = $an['announcement_id'];
            $dest = $an['post_dest'];
            $this->log(9, sprintf("announcement dest:  an=%s   dest=%s", $id, $dest));

            $this->route['announcements'][$id] = $an;
            $this->route['announcements'][$id]['dest'] = $dest;
		}
    }
}