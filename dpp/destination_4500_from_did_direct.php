<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationFromDidDirect extends BaseDestinations
{
    public const PRIORITY = 4500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^from-did-direct,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of from-did-direct,<number>,<number>

        $extnum      = $matches[1];
        $extother    = $matches[2];
        $fmfmOption  = $this->getSetting('fmfm');
        $extOptional = $this->getSetting('ext_optional');

        if (isset($route['extensions'][$extnum])) {
            $extension = $route['extensions'][$extnum];
            $extname   = $extension['name'];
            $extemail  = $extension['email'];
            $extemail  = str_replace("|", ",\\n", $extemail);

            $fmfmLabel = '';
            if (isset($extension['fmfm'])) {
                if ($extension['fmfm']['ddial'] == 'DIRECT') {
                    $fmfmLabel = sprintf(_("\\nFMFM Enabled\\nInitial Ring Time=%s\\nRing Time=%s\\nConfirm Calls=%s"), $this->dpp->secondsToTimes($extension['fmfm']['prering']), $this->dpp->secondsToTimes($extension['fmfm']['grptime']), $extension['fmfm']['grpconf']);
                } else {
                    $fmfmLabel = _("\\nFMFM Disabled");
                }
            }

            $label        = sprintf(_("Extension: %s %s\\n%s"), $extnum, $extname, $extemail);
            $labeltooltip = sprintf('%s%s', $label, $fmfmLabel);

            $this->updateNodeAttribute($node, [
                'label'     => $label,
                'tooltip'   => $labeltooltip,
                'URL'       => $this->genUrlConfig('extensions', $extnum, null), //'/admin/config.php?display=extensions&extdisplay='.$extnum
                'target'    => '_blank',
            ]);

            if (isset($extension['fmfm']) && $fmfmOption) {
                if ($extension['fmfm']['ddial'] == 'DIRECT') {
                    $grplist = preg_split("/-/", $extension['fmfm']['grplist']);
                    foreach ($grplist as $g) {
                        $this->findNextDestination(
                            $route,
                            $node,
                            sprintf('from-did-direct,%s,1', str_replace('#', '', trim($g))),
                            sprintf(_(" FMFM (%s)"), $this->dpp->secondsToTimes($extension['fmfm']['prering']))
                        );
                    }

                    if (isset($extension['fmfm']['postdest']) && $extension['fmfm']['postdest'] != 'ext-local,' . $extnum . ',dest') {
                        $this->findNextDestination(
                            $route,
                            $node,
                            $extension['fmfm']['postdest'],
                            _(" FMFM No Answer")
                        );
                    }
                }
            }
        } else {
            //phone numbers or remote extensions
            $this->updateNodeAttribute($node, [
                'label'   => $extnum,
                'tooltip' => $extnum,
            ]);
        }

        $this->updateNodeAttribute($node, [
            'shape'     => 'rect',
            'fillcolor' => self::PASTELS[15],
            'style'     => 'filled',
        ]);

        //Optional Destinations
        if ($extOptional && (!empty($extension['noanswer_dest']) || !empty($extension['busy_dest']) || !empty($extension['chanunavail_dest']))) {
            if ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['noanswer_dest'] === $extension['chanunavail_dest']) {
                // All three are equal
                $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer, Busy, Not Reachable"));
            } elseif ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['chanunavail_dest'] !== $extension['noanswer_dest']) {
                if (!empty($extension['noanswer_dest'])) {
                    // No Answer and Busy are the same, but Not Reachable is different
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer & Busy"));
                }

                //Not Reachable
                if (!empty($extension['chanunavail_dest'])) {
                    $this->findNextDestination($route, $node, $extension['chanunavail_dest'], _(" Not Reachable"));
                }
            } elseif ($extension['noanswer_dest'] === $extension['chanunavail_dest'] && $extension['busy_dest'] !== $extension['noanswer_dest']) {
                if (!empty($extension['noanswer_dest'])) {
                    // No Answer and Not Reachable are the same
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer & Not Reachable"));
                }

                //Busy
                if (!empty($extension['busy_dest'])) {
                    $this->findNextDestination($route, $node, $extension['busy_dest'], _(" Busy"));
                }
            } elseif ($extension['busy_dest'] === $extension['chanunavail_dest'] && $extension['noanswer_dest'] !== $extension['busy_dest']) {
                if (!empty($extension['busy_dest'])) {
                    // Busy and Not Reachable are the same
                    $this->findNextDestination($route, $node, $extension['busy_dest'], _(" Busy & Not Reachable"));
                }

                //No Answer
                if (!empty($extension['noanswer_dest'])) {
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer"));
                }
            } else {
                // All are different
                if (!empty($extension['noanswer_dest'])) {
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer"));
                }
                if (!empty($extension['busy_dest'])) {
                    $this->findNextDestination($route, $node, $extension['busy_dest'], _(" Busy"));
                }
                if (!empty($extension['chanunavail_dest'])) {
                    $this->findNextDestination($route, $node, $extension['chanunavail_dest'], _(" Not Reachable"));
                }
            }
        }
    }
}
