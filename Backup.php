<?php

namespace FreePBX\modules\Dpviz;

use FreePBX\modules\Backup as Base;

class Backup extends Base\BackupBase
{
    public function runBackup($id, $transaction)
    {
        $configs = [
            'kvstore' => $this->dumpKVStore(),
        ];
        $this->addConfigs($configs);
    }
}
