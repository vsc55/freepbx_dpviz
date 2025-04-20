<?php
namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/baseTables.php';

class TableTts extends baseTables
{
    // case 'tts':
    //     foreach($results as $tts)
    //     {
    //         $id = $tts['id'];
    //         $dproute['tts'][$id] = $tts;
    //     }
    // break;

    public function __construct($dpp)
    {
        parent::__construct($dpp, "tts", true);
        $this->key_id   = "id";
        $this->key_name = "tts";
    }
}