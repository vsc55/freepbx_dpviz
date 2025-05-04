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

        //TODO: $dynmembers= isset($options[0]['dynmembers']) ? $options[0]['dynmembers'] : '0';
        //TODO: change metod to getSetting() in Dpviz class
        $dynmembers = \FreePBX::Dpviz()->getSetting('dynmembers');

        # Queue members (dynamic) //options
        if ($dynmembers && !empty($dproute[$this->key_name]))
        {
            foreach ($dproute[$this->key_name] as $id => $details)
            {
                $qp_raw = \FreePBX::Dpviz()->asterisk_runcmd(sprintf('database show QPENALTY %s', $id), false);
                foreach ($qp_raw as $line)
                {
                    $line = trim($line);
                    if (strpos($line, '/') !== 0) {
                        continue; // skip lines not starting with '/'
                    }
                    if (strpos($line, '/agents/') === false) {
                        continue; // only keep lines with '/agents/'
                    }

                    [$key, $value] = explode(':', $line, 2);
                    $parts         = explode('/', trim($key));

                    if (!isset($parts[4]))
                    {
                        continue; // ensure fifth field exists
                    }
                    $ext = trim($parts[4]); // fifth part (index 4)

                    $dproute[$this->key_name][$id]['members']['dynamic'][] = $ext;
                }
            }
        }
        return true;
    }
}
