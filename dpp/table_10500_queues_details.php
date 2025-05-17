<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableQueuesDetails extends BaseTables
{
    public const PRIORITY = 10500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "queues_details", true);
        $this->key_id   = "id";
        $this->key_name = "queues";

        $this->deppendencies = [
            'TableQueuesConfig' => 'queues',
        ];
    }

    public function callbackLoad()
    {
        $dynmembers = $this->getSetting('dynmembers'); // default to 0

        foreach ($this->getTableData() as $qd) {
            if (!$this->checkItemLoad($qd)) {
                continue;
            }
            $id = $qd[$this->key_id];
            if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                continue;
            }

            if ($qd['keyword'] == 'member') {
                $member = $qd['data'];
                if (preg_match("/Local\/(\d+).*?,(\d+)/", $member, $matches)) {
                    $enum = $matches[1];
                    $pen  = $matches[2];
                    // $dproute[$this->key_name][$id]['members']['static'][] = $enum;
                    $this->route[$this->key_name][$id]['members']['static'][] = $enum;
                    $this->logRoute(
                        $id,
                        false,
                        '{action}  >>  {table} members static  >  id [{id}]    enum [{enum}]    pen [{pen}]',
                        [
                            '{enum}' => $enum,
                            '{pen}'  => $pen,
                        ],
                        9
                    );
                }
            } else {
                $this->route[$this->key_name][$id]['data'][$qd['keyword']] = $qd['data'];
                $this->logRoute($id, false, '{action}  >>  {table} members static  >  id [{id}]    keyword [{keyword}]', ['{keyword}' => $qd['keyword']], 9);
            }
        }

        # Queue members (dynamic) //options
        if ($dynmembers && !empty($this->route[$this->key_name])) {
            foreach ($this->route[$this->key_name] as $id => $details) {
                $this->processAsteriskLines(
                    $this->asteriskRunCmd(sprintf('database show QPENALTY %s', $id), false),
                    function ($line) use ($id) {
                        [$key, $value] = explode(':', $line, 2);
                        $parts         = explode('/', trim($key));

                        if (!isset($parts[4])) {
                            return; // skip invalid
                        }

                        $ext = trim($parts[4]); // fifth part (index 4)
                        $this->route[$this->key_name][$id]['members']['dynamic'][] = $ext;
                        $this->logRoute($id, false, '{action}  >>  {table} members dynamic  >  id [{id}]    ext [{ext}]', ['{ext}' => $ext], 9);
                    },
                    function ($line) {
                        return strpos($line, '/') === 0 && strpos($line, '/agents/') !== false;
                    }
                );
            }
        }
        return true;
    }
}
