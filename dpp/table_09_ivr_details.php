<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableIvrDetails extends baseTables
{
    //   # IVRs
    //   $sql = sprintf("SELECT * FROM %s", "ivr_details");
    //   foreach($results as $ivr)
    //   {
    //       $id = $ivr['id'];
    //       $dproute['ivrs'][$id] = $ivr;
    //   }

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp, "ivr_details");
        $this->key_id   = "id";
        $this->key_name = "ivrs";
    }
}