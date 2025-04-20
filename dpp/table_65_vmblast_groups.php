<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableVmblastGroups extends baseTables
{
    // case 'vmblast_groups':
    //     foreach($results as $vmblastsGrp)
    //     {
    //         $id = $vmblastsGrp['grpnum'];
    //         $this->dpplog(9, "vmblast:  vmblast=$id");
    //         $dproute['vmblasts'][$id]['members'][] = $vmblastsGrp['ext'];
    //     }
    // break;
    
    public function __construct($dpp)
    {
        parent::__construct($dpp, "vmblast_groups", true);
        $this->key_id   = "grpnum";
        $this->key_name = "vmblasts";
    }

    public function callback_load(&$dproute)
    {
        foreach($this->getTableData() as $vmblastsGrp)
		{
            $id = $vmblastsGrp['grpnum'];
            $this->log(9, "vmblast:  vmblast=$id");
            $dproute['vmblasts'][$id]['members'][] = $vmblastsGrp['ext'];
		}
    }
}