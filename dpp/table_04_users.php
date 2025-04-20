<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableUsers extends baseTables
{
    # Users
    
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "users");
        $this->key_id   = "extension";
        $this->key_name = "extensions";
    }

    public function callback_load()
    {
        foreach($this->getTableData() as $user)
		{
			$id 	 = $user[$this->key_id];
			// $u[$id]  = $user;

            $Q       = sprintf('grep -E \'^%s[[:space:]]*[=>]+\' /etc/asterisk/voicemail.conf | cut -d \',\' -f3', $id);
            $Qresult = array();
			exec($Q, $Qresult);

			$this->route[$this->key_name][$id]= $user;
			$this->route[$this->key_name][$id]['email'] = !empty($Qresult[0]) ? $Qresult[0] : _('unassigned');
		}
        return true;
    }
}