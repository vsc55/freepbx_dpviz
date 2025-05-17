<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableKvstoreFreepbxModulesCustomappsreg extends BaseTables
{
    public const PRIORITY = 7500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "kvstore_FreePBX_modules_Customappsreg", true);
        $this->key_id   = "key";
        $this->key_name = "customapps";
    }

    public function callbackLoad(&$dproute)
    {
        foreach ($this->getTableData() as $customappsreg) {
            if (!$this->checkItemLoad($customappsreg)) {
                continue;
            }
            if (is_numeric($customappsreg[$this->key_id])) {
                $id  = $customappsreg[$this->key_id];
                if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                    continue;
                }
                $item = json_decode($customappsreg['val'], true);
                $this->setRoute($id, $item);

                // $id  = $customappsreg[$this->key_id];
                // $val = json_decode($customappsreg['val'], true);
                // $dproute[$this->key_name][$id] = $val;
                // $this->log(9, sprintf("customapps=%s", $id));
            }
        }
        return true;
    }
}
