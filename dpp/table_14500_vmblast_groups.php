<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableVmblastGroups extends BaseTables
{
    public const PRIORITY = 14500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "vmblast_groups", true);
        $this->key_id   = "grpnum";
        $this->key_name = "vmblasts";

        $this->deppendencies = [
            'TableVmblast' => 'vmblast',
        ];
    }

    public function callbackLoad(&$dproute)
    {
        foreach ($this->getTableData() as $vmblastsGrp) {
            if (!$this->checkItemLoad($vmblastsGrp)) {
                continue;
            }

            $id = $vmblastsGrp[$this->key_id];
            if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                continue;
            }

            $this->route[$this->key_name][$id]['members'][] = $vmblastsGrp['ext'];
            $this->logRoute($id, false);

            // $dproute[$this->key_name][$id]['members'][] = $vmblastsGrp['ext'];
            // $this->log(9, sprintf("vmblast:  vmblast=%s", $id));
        }
        return true;
    }
}
