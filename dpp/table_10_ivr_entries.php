<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableIvrEntries extends baseTables
{
  
// # IVR entries
// $sql = sprintf("SELECT * FROM %s", "ivr_entries");
// $sth = $this->db->prepare($sql);
// $sth->execute();
// $results = $sth->fetchAll(\PDO::FETCH_ASSOC);
// foreach($results as $ent)
// {
//     $id    = $ent['ivr_id'];
//     $selid = $ent['selection'];
//     $this->dpplog(9, "entry:  ivr=$id   selid=$selid");
//     $dproute['ivrs'][$id]['entries'][$selid] = $ent;
// }


    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "ivr_entries");
    }

    public function callback_load()
    {
        foreach($this->getTableData() as $ent)
		{
			$id    = $ent['ivr_id'];
			$selid = $ent['selection'];
			$this->log(9, sprintf("entry:  ivr=%s   selid=%s", $id, $selid));
			$this->route['ivrs'][$id]['entries'][$selid] = $ent;
		}
    }
}