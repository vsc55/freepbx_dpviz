<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableUsers extends baseTables
{
    # Users
    public const PRIORITY = 1000;

    private $voicemail = null;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "users");
        $this->key_id   = "extension";
        $this->key_name = "extensions";

        $this->voicemail = \FreePBX::Voicemail();
    }

    public function callback_load(&$dproute)
    {
        $fmfmOption = $this->getSetting('fmfm');

        foreach($this->getTableData() as $user)
        {
            $id 	 = $user[$this->key_id];
            $mailbox = $this->voicemail->getMailbox($id);
            $email   = $mailbox['email'] ?: _('unassigned');

            $dproute[$this->key_name][$id] = $user;
            $dproute[$this->key_name][$id]['email'] = $email;
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
}
