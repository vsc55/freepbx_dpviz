<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationQueueMembers extends BaseDestinations
{
    # Queue members (static and dynamic)
    public const PRIORITY = 9000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^qmember(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of qmember<number>

        $qextension = $matches[1];

        if (isset($route['extensions'][$qextension]['name'])) {
            $label = sprintf(_("Ext %s\\n%s"), $qextension, $route['extensions'][$qextension]['name']);
        } else {
            $label = $qextension;
        }

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => is_numeric($label) ? '__SKIP_NO_CHANGE__' : $this->genUrlConfig('extensions', $qextension, null), //'/admin/config.php?display=extensions&extdisplay='.$qextension
            'target'    => is_numeric($label) ? '__SKIP_NO_CHANGE__' : '_blank',
            'fillcolor' => $route['parent_edge_data_status'] == 'static' ? self::PASTELS[20] : self::PASTELS[8],
            'style'     => 'filled',
        ]);
    }
}
