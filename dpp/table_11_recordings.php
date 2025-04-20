<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableRecordings extends baseTables
{
    // # Recordings
    // $sql = sprintf("SELECT * FROM %s", "recordings");
    // foreach($results as $recordings)
    // {
    //     $id = $recordings['id'];
    //     $dproute['recordings'][$id] = $recordings;
    //     $this->dpplog(9, "recordings=$id");
    // }
    
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "recordings");
        $this->key_id   = "id";
        $this->key_name = "recordings";
    }
}