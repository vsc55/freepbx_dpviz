<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationDaynight extends BaseDestinations
{
    public const PRIORITY = 2000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-daynight,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of app-daynight,<number>,<number>

        $daynightnum   = $matches[1];
        $daynightother = $matches[2];
        $daynight      = $route['daynight'][$daynightnum];

        #feature code exist?
        $code = '';
        if (isset($route['featurecodes']['*28' . $daynightnum])) {
            #custom feature code?
            if ($route['featurecodes']['*28' . $daynightnum]['customcode'] != '') {
                $featurenum = $route['featurecodes']['*28' . $daynightnum]['customcode'];
            } else {
                $featurenum = $route['featurecodes']['*28' . $daynightnum]['defaultcode'];
            }
            #is it enabled?
            $code = sprintf(_("\\nToggle (%s): %s"), ($route['featurecodes']['*28' . $daynightnum]['enabled'] == '1') ? _("enabled") : _("disabled"), $featurenum);
        }

        #check current status and set path to active
        $current_daynight = null;
        $this->processAsteriskLines(
            $this->asteriskRunCmd(sprintf('database show DAYNIGHT/C%s', $daynightnum), false),
            function ($line) use (&$current_daynight) {
                [, $value]        = explode(':', $line, 2);
                $value            = str_replace(' ', '', trim($value));
                $current_daynight = $value;
                return false; // stop after first
            },
            function ($line) {
                return strpos($line, ':') !== false;
            }
        );

        $dactive = "";
        $nactive = "";
        if ($current_daynight == 'DAY') {
            $dactive = _("(Active)");
        } else {
            $nactive = _("(Active)");
        }

        foreach ($daynight as $d) {
            switch ($d['dmode']) {
                case 'day':
                    $this->findNextDestination(
                        $route,
                        $node,
                        $d['dest'],
                        sprintf(_(" Day Mode %s"), $dactive)
                    );
                    break;

                case 'night':
                    $this->findNextDestination(
                        $route,
                        $node,
                        $d['dest'],
                        sprintf(_(" Night Mode %s"), $nactive)
                    );
                    break;

                case 'fc_description':
                    $this->updateNodeAttribute($node, [
                        'label' => sprintf(_("Call Flow: %s%s"), $d['dest'], $code),
                    ]);
                    break;
            }
        }

        $this->updateNodeAttribute($node, [
            'URL'       => htmlentities('/admin/config.php?display=daynight&view=form&itemid=' . $daynightnum . '&extdisplay=' . $daynightnum),
            'target'    => '_blank',
            'fillcolor' => self::PASTELS[14],
            'style'     => 'filled',
        ]);
    }
}
