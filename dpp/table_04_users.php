<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableUsers extends baseTables
{
    // # Users
    // $sql = sprintf("SELECT * FROM %s", "users");
    // foreach($results as $users)
    // {
    // 	$Qresult = array();
    // 	$id 	 = $users['extension'];
    // 	$u[$id]  = $users;
    // 	$dproute['extensions'][$id]= $users;
    // 	$Q='grep -E \'^'.$id.'[[:space:]]*[=>]+\' /etc/asterisk/voicemail.conf | cut -d \',\' -f3';
    // 	exec($Q, $Qresult);
    // 	$dproute['extensions'][$id]['email'] = !empty($Qresult[0]) ? $Qresult[0] : 'unassigned';
    // }

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "users");
    }

    public function callback_load()
    {
        foreach($this->getTableData() as $user)
		{
			$Qresult = array();
			$id 	 = $user['extension'];
			$u[$id]  = $user;

            $Q='grep -E \'^'.$id.'[[:space:]]*[=>]+\' /etc/asterisk/voicemail.conf | cut -d \',\' -f3';
			exec($Q, $Qresult);

			$this->route['extensions'][$id]= $user;
			$this->route['extensions'][$id]['email'] = !empty($Qresult[0]) ? $Qresult[0] : 'unassigned';
		}
    }
}