<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationQueueMembers extends baseDestinations
{
    # Queue members (static and dynamic)
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^qmember(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $qextension = $matches[1];
        $qlabel = isset($route['extensions'][$qextension]['name']) ? 'Ext '.$qextension.'\\n'.$route['extensions'][$qextension]['name'] : $qextension;
        $node->attribute('label', $this->dpp->sanitizeLabels($qlabel));
        $node->attribute('tooltip', $node->getAttribute('label'));
        if (!is_numeric($qlabel))
        {
            $node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$qextension));
            $node->attribute('target', '_blank');
        }
        
        if ($route['parent_edge_label'] == ' Static')
        {
            $node->attribute('fillcolor', self::pastels[20]);
        }
        else
        {
            $node->attribute('fillcolor', self::pastels[8]);
        }
        $node->attribute('style', 'filled');
    }
}