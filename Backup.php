<?php
namespace FreePBX\modules\Dpviz;
use FreePBX\modules\Backup as Base;

class Backup Extends Base\BackupBase
{
	public function runBackup($id, $transaction)
	{
		$configs = [
			'tables'  => $this->dumpTables(),
			'kvstore' => $this->dumpKVStore()
		];
		$this->addConfigs($configs);
	}
}