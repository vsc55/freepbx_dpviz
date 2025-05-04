<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableQueuesDetails extends baseTables
{
    public const PRIORITY = 10500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queues_details", true);
        $this->key_id   = "id";
        $this->key_name = "queues";
    }

    public function callback_load(&$dproute)
    {
        $dynmembers = $this->getSetting('dynmembers'); // default to 0

        foreach($this->getTableData() as $qd)
        {
            $id = $qd[$this->key_id];

            if ($qd['keyword'] == 'member')
            {
                $member = $qd['data'];
                if (preg_match("/Local\/(\d+).*?,(\d+)/", $member, $matches))
                {
                    $enum = $matches[1];
                    $pen  = $matches[2];
                    $dproute[$this->key_name][$id]['members']['static'][] = $enum;
                }
            }
            else
            {
                $dproute[$this->key_name][$id]['data'][$qd['keyword']] = $qd['data'];
            }
        }

        # Queue members (dynamic) //options
        if ($dynmembers && !empty($dproute[$this->key_name]))
        {
            foreach ($dproute[$this->key_name] as $id => $details)
            {
                $this->processAsteriskLines(
                    $this->asteriskRunCmd(sprintf('database show QPENALTY %s', $id), false),
                    function($line) use (&$dproute, $id)
                    {
                        [$key, $value] = explode(':', $line, 2);
                        $parts         = explode('/', trim($key));

                        if (!isset($parts[4]))
                        {
                            return; // skip invalid
                        }

                        $ext = trim($parts[4]); // fifth part (index 4)

                        $dproute[$this->key_name][$id]['members']['dynamic'][] = $ext;
                    },
                    function($line)
                    {
                        return strpos($line, '/') === 0 && strpos($line, '/agents/') !== false;
                    }
                );
            }
        }
        return true;
    }
}
