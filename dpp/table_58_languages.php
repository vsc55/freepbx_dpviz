<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableLanguages extends baseTables
{
    // case 'languages':
    //     foreach($results as $languages)
    //     {
    //         $id = $languages['language_id'];
    //         $dproute['languages'][$id] = $languages;
    //         $this->dpplog(9, "languages=$id");
    //     }
    // break;

    public function __construct($dpp)
    {
        parent::__construct($dpp, "languages", true);
        $this->key_id   = "language_id";
        $this->key_name = "languages";
    }
}