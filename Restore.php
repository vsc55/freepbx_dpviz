<?php

namespace FreePBX\modules\Dpviz;

use FreePBX\modules\Backup as Base;

class Restore extends Base\RestoreBase
{
    public function runRestore()
    {
        $configs = $this->getConfigs();

        if (isset($configs['kvstore']) && is_array($configs['kvstore']) && !empty($configs['kvstore'])) {
            $this->importKVStore($configs['kvstore']);
        } else {
            $this->log(_("Skipping KVStore import, no data found!"), "WARNING");
        }
    }
}
