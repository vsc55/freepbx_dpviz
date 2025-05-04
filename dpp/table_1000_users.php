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
            $ampuser = \FreePBX::Dpviz()->asterisk_runcmd('database show AMPUSER', false);

            foreach ($ampuser as $line)
            {
                $line = trim($line);
                if (strpos($line, '/') !== 0)
                {
                    // Skip lines that do not start with a slash '/'
                    continue;
                }
                if (strpos($line, 'followme') === false)
                {
                    // only process lines that contain 'followme'
                    continue;
                }

                // Example: /AMPUSER/6055/followme/strategy : ringallv2-prim
                [$key, $value] = explode(':', $line, 2);
                $parts = explode('/', trim($key));

                if (!isset($parts[2], $parts[4]))
                {
                    // Need at least ext and subkey
                    continue;
                }

                $ext    = trim($parts[2]); // third field
                $subkey = trim($parts[4]); // faifth field

                $dproute[$this->key_name][$ext]['fmfm'][$subkey] = trim($value);
            }
        }

        return true;
    }
}
