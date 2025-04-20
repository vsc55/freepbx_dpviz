<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableKvstoreFreepbxModulesCustomappsreg extends baseTables
{
    // case 'kvstore_FreePBX_modules_Customappsreg':
    //     foreach($results as $Customappsreg)
    //     {
    //         if (is_numeric($Customappsreg['key']))
    //         {
    //             $id = $Customappsreg['key'];
    //             $val=json_decode($Customappsreg['val'],true);
    //             $dproute['customapps'][$id] = $val;
    //             $this->dpplog(9, "customapps=$id");
    //         }
    //     }
    // break;
    
    public function __construct($dpp)
    {
        parent::__construct($dpp, "kvstore_FreePBX_modules_Customappsreg", true);
        $this->key_id   = "key";
        $this->key_name = "customapps";
    }

    public function callback_load(&$dproute)
    {
        foreach($this->getTableData() as $customappsreg)
		{
            if (is_numeric($customappsreg['key']))
            {
                $id = $customappsreg['key'];
                $val=json_decode($customappsreg['val'], true);
                $dproute['customapps'][$id] = $val;
                $this->log(9, "customapps=$id");
            }
		}
    }
}