<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationDirectory extends baseDestinations
{
    public const PRIORITY = 3000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^directory,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $directorynum   = $matches[1];
        $directoryother = $matches[2];
        $directory      = $route['directory'][$directorynum];

        $label          = sprintf(_("Directory: %s"), $directory['dirname']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('directory', $directorynum), //'/admin/config.php?display=directory&view=form&id='.$directorynum
            'target'    => '_blank',
            'fillcolor' => self::pastels[9],
            'shape'     => 'folder',
            'style'     => 'filled'
        ]);

        if ($directory['invalid_destination'] != '')
        {
            $this->findNextDestination($route, $node, $directory['invalid_destination'], _(" Invalid Input"));
        }
    }
}
