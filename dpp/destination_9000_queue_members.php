<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationQueueMembers extends baseDestinations
{
    # Queue members (static and dynamic)
    public const PRIORITY = 9000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^qmember(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $qextension = $matches[1];

        if (isset($route['extensions'][$qextension]['name']))
        {
            $label = sprintf(_("Ext %s\\n%s"), $qextension, $route['extensions'][$qextension]['name']);
        }
        else
        {
            $label = $qextension;
        }

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => is_numeric($label) ? '__SKIP_NO_CHANGE__' : $this->genUrlConfig('extensions', $qextension, null), //'/admin/config.php?display=extensions&extdisplay='.$qextension
            'target'    => is_numeric($label) ? '__SKIP_NO_CHANGE__' : '_blank',
            'fillcolor' => $route['parent_edge_data_status'] == 'static' ? self::pastels[20] : self::pastels[8],
            'style'     => 'filled',
        ]);
    }
}
