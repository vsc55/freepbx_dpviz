<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableUsers extends baseTables
{
    # Users
    public const PRIORITY = 1000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "users");
        $this->key_id   = "extension";
        $this->key_name = "extensions";
    }

    public function callback_load(&$dproute)
    {
        //TODO: change metod to getSetting() in Dpviz class
        $fmfmOption  = \FreePBX::Dpviz()->getSetting('fmfm');

        foreach($this->getTableData() as $user)
        {
            $id 	     = $user[$this->key_id];
            $email       = sprintf('grep -E \'^%s[[:space:]]*[=>]+\' /etc/asterisk/voicemail.conf | cut -d \',\' -f3', $id);
            $emailResult = [];
            exec($email, $emailResult);

            $dproute[$this->key_name][$id]= $user;
            $dproute[$this->key_name][$id]['email'] = !empty($emailResult[0]) ? $emailResult[0] : _('unassigned');
        }

        if ($fmfmOption)
        {
            $D = '/usr/sbin/asterisk -rx "database show AMPUSER" | grep \'followme\' | cut -d \'/\' -f3,5';
            exec($D, $fmfm);
            foreach ($fmfm as $line)
            {
                // Split into key and value
                list($left, $value) = explode(':', $line, 2);
                $left  = trim($left);
                $value = trim($value);

                // Split the left part into extension and subkey
                list($ext, $subkey) = explode('/', $left, 2);
                $dproute[$this->key_name][$ext]['fmfm'][$subkey] = $value;
            }
        }

        return true;
    }
}
