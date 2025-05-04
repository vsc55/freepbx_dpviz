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
        $fmfmOption = $this->getSetting('fmfm');

        foreach($this->getTableData() as $user)
        {
            $id = $user[$this->key_id];

            $dproute[$this->key_name][$id] = $user;
            $dproute[$this->key_name][$id]['email'] = $this->getVoicemailEmail($id);
        }

        if ($fmfmOption)
        {
            $this->processAsteriskLines(
                $this->asteriskRunCmd('database show AMPUSER', false),
                function($line) use (&$dproute)
                {
                    [$key, $value] = explode(':', $line, 2);
                    $parts         = explode('/', trim($key));

                    if (!isset($parts[2], $parts[4])) {
                        return; // skip invalid
                    }

                    $ext    = trim($parts[2]);
                    $subkey = trim($parts[4]);

                    $dproute[$this->key_name][$ext]['fmfm'][$subkey] = trim($value);
                },
                function($line)
                {
                    return strpos($line, '/') === 0 && strpos($line, '/followme/') !== false;
                }
            );
        }

        return true;
    }

    private function getVoicemailEmail($id)
    {
        $unassigned = _('unassigned');
        if (! is_numeric($id))
        {
            return $unassigned;
        }
        $mailbox = \FreePBX::Voicemail()->getMailbox($id);
        return $mailbox['email'] ?: $unassigned;
    }
}
