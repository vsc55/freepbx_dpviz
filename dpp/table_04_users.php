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

    public function callback_load(&$dproute)
    {
        foreach($this->getTableData() as $user)
		{
			$id 	 = $user[$this->key_id];
			// $u[$id]  = $user;

            $email = 'grep -E \'^'.$id.'[[:space:]]*[=>]+\' /etc/asterisk/voicemail.conf | cut -d \',\' -f3';
		    exec($email, $emailResult);

			$dproute[$this->key_name][$id]= $user;
			$dproute[$this->key_name][$id]['email'] = !empty($emailResult[0]) ? $emailResult[0] : _('unassigned');
		}
        return true;
    }
}