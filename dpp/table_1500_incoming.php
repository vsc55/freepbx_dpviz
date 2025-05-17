<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseTables.php';

class TableIncoming extends BaseTables
{
    # Inbound Routes
    public const PRIORITY = 1500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "incoming");
        $this->key_id   = "extension";
        $this->key_name = "incoming";
    }

    public function callbackLoad()
    {
        foreach ($this->getTableData() as $incoming) {
            if (!$this->checkItemLoad($incoming)) {
                continue;
            }
            $id = $this->getId($incoming);
            $id = empty($id) ? 'ANY' : $id;
            $this->setRoute($id, $incoming);
        }
        return true;
    }
}
