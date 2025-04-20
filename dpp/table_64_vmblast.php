<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableVmblast extends baseTables
{
    // case 'vmblast':
    //     foreach($results as $vmblasts)
    //     {
    //         $id = $vmblasts['grpnum'];
    //         $this->dpplog(9, "vmblast:  vmblast=$id");
    //         $dproute['vmblasts'][$id] = $vmblasts;
    //     }
    // break;
    
    public function __construct($dpp)
    {
        parent::__construct($dpp, "vmblast", true);
        $this->key_id   = "grpnum";
        $this->key_name = "vmblast";
    }
}