<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationVoicemail extends BaseDestinations
{
    public const PRIORITY = 12000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-local,vm([b,i,s,u])(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-local,vm<b|i|s|u><number>,<number>

        $vmtype  = $matches[1];
        $vmnum   = $matches[2];
        $vmother = $matches[3];

        $vm_array = array(
            'b' => _("(Busy Message)"),
            'i' => _("(Instructions Only)"),
            's' => _("(No Message)"),
            'u' => _("(Unavailable Message)")
        );
        $vmname   = $route['extensions'][$vmnum]['name'];
        $vmemail  = $route['extensions'][$vmnum]['email'];
        $vmemail  = str_replace("|", ",\\n", $vmemail);

        $label    = sprintf(_("Voicemail: %s %s %s\\n%s"), $vmnum, $vmname, $vm_array[$vmtype], $vmemail);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => htmlentities('/admin/config.php?display=voicemail&view=form&id=' . $vmnum),
            'target'    => '_blank',
            'shape'     => 'folder',
            'fillcolor' => self::PASTELS[11],
            'style'     => 'filled',
        ]);
    }
}
