<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableKvstoreFreepbxModulesCalendar extends baseTables
{
    public const PRIORITY = 7000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "kvstore_FreePBX_modules_Calendar", true);
        $this->key_id   = "key";
        $this->key_name = "calendar";
    }

    public function callback_load(&$dproute)
    {
        foreach($this->getTableData() as $calendar)
        {
            switch ($calendar['id'])
            {
                case 'calendars':
                case 'groups':
                    $id = $calendar['key'];
                    $dproute[$this->key_name][$id] = json_decode($calendar['val'], true);
                    $this->log(9, sprintf("calendars=%s", $id));
                    break;

                default:
                    $this->log(1, sprintf("Unknown calendar type: {%s}", $calendar['id']));
            }
        }
        return true;
    }
}
