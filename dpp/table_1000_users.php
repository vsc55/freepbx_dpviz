<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableUsers extends BaseTables
{
    # Users
    public const PRIORITY = 1000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "users");
        $this->key_id   = "extension";
        $this->key_name = "extensions";
    }

    public function callbackLoad()
    {
        $fmfmOption = $this->getSetting('fmfm');

        foreach ($this->getTableData() as $user) {
            if (!$this->checkItemLoad($user)) {
                continue;
            }
            $id = $user[$this->key_id];
            if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                continue;
            }

            $item = $user;
            $item['email'] = $this->getVoicemailEmail($id);
            $this->setRoute($id, $item, false, true, '{action}  >>  {table} user  >  id [{id}]    email [{email}]', ['{email}' => $item['email']], 9);
        }

        $this->processAsteriskLines(
            $this->asteriskRunCmd('database show AMPUSER', false),
            function ($line) {
                [$key, $value] = explode(':', $line, 2);
                $parts         = explode('/', trim($key));

                if (!isset($parts[2], $parts[4])) {
                    return; // skip invalid
                }

                $ext    = trim($parts[2]);
                $subkey = trim($parts[4]);

                $isNew = !isset($this->route[$this->key_name][$ext]);
                $this->route[$this->key_name][$ext]['fmfm'][$subkey] = trim($value);
                $this->logRoute(
                    $ext,
                    $isNew,
                    '{action}  >>  {table} fmfm  >  id [{id}]    fmfm [{subkey}] = [{value}]',
                    [
                        '{subkey}' => $subkey,
                        '{value}' => trim($value)
                    ],
                    9
                );
            },
            function ($line) {
                return strpos($line, '/') === 0 && strpos($line, '/followme/') !== false;
            }
        );

        return true;
    }

    private function getVoicemailEmail($id)
    {
        $unassigned = "";
        if (! is_numeric($id)) {
            return $unassigned;
        }

        $voicemail = \FreePBX::create()->Voicemail;
        $mailbox   = $voicemail->getMailbox($id);
        return $mailbox['email'] ?: $unassigned;
    }
}
