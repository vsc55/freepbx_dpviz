<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableDirectoryDetails extends baseTables
{
    // case 'directory_details':
    //     foreach($results as $directory) {
    //         $id = $directory['id'];
    //         $dproute['directory'][$id] = $directory;
    //         $this->dpplog(9, "directory=$id");
    //     }
    // break;

    public function __construct($dpp)
    {
        parent::__construct($dpp, "directory_details", true);
        $this->key_id   = "id";
        $this->key_name = "directory";
    }
}