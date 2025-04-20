<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationVmblastMembers extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^vmblast\-mem,(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $member 	  = $matches[1];
        $vmblastname  = $route['extensions'][$member]['name'];
        $vmblastemail = $route['extensions'][$member]['email'];
        $vmblastemail = str_replace("|",",\\n",$vmblastemail);

        $node->attribute('label', 'Ext '.$member.' '.$this->dpp->sanitizeLabels($vmblastname).'\\n'.$this->dpp->sanitizeLabels($vmblastemail));
        $node->attribute('tooltip', $node->getAttribute('label'));
        $node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$member));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'rect');
        $node->attribute('fillcolor', self::pastels['16']);
        $node->attribute('style', 'filled');
    }
}