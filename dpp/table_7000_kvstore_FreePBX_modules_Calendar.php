<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableKvstoreFreepbxModulesCalendar extends BaseTables
{
    public const PRIORITY = 7000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "kvstore_FreePBX_modules_Calendar", true);
        $this->key_id   = "key";
        $this->key_name = "calendar";
    }

    public function callbackLoad()
    {
        foreach ($this->getTableData() as $calendar) {
            if (!$this->checkItemLoad($calendar)) {
                continue;
            }
            switch ($calendar['id']) {
                case 'calendars':
                case 'groups':
                    $id = $calendar[$this->key_id];
                    if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                        continue;
                    }
                    $item = json_decode($calendar['val'], true);
                    $this->setRoute($id, $item);
                    break;

                default:
                    $this->log(1, sprintf("Unknown calendar type: {%s}", $calendar['id']));
                    break;
            }
        }
        return true;
    }
}
