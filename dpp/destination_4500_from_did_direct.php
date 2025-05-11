<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationFromDidDirect extends baseDestinations
{
    public const PRIORITY = 4500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^from-did-direct,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $extnum      = $matches[1];
        $extother    = $matches[2];
        $fmfmOption  = $this->getSetting('fmfm');
        $extOptional = $this->getSetting('ext_optional');

        if (isset($route['extensions'][$extnum]))
        {
            $extension = $route['extensions'][$extnum];
            $extname   = $extension['name'];
            $extemail  = $extension['email'];
            $extemail  = str_replace("|",",\\n",$extemail);

            $fmfmLabel = '';
            if (isset($extension['fmfm']))
            {
                if ($extension['fmfm']['ddial'] == 'DIRECT')
                {
                    $fmfmLabel = sprintf(_("\\nFMFM Enabled\\nInitial Ring Time=%s\\nRing Time=%s\\nConfirm Calls=%s"), $this->dpp->secondsToTimes($extension['fmfm']['prering']), $this->dpp->secondsToTimes($extension['fmfm']['grptime']), $extension['fmfm']['grpconf']);
                }
                else
                {
                    $fmfmLabel = _("\\nFMFM Disabled");
                }
            }

            $label        = $this->sanitizeLabels(sprintf(_("Extension: %s %s\\n%s"), $extnum, $extname, $extemail));
            $labeltooltip = $this->sanitizeLabels(sprintf('%s%s', $label, $fmfmLabel));

            $node->attribute('label', $label);
            $node->attribute('tooltip', $labeltooltip);
            $node->attribute('URL', $this->genUrlConfig('extensions', $extnum, null)); //'/admin/config.php?display=extensions&extdisplay='.$extnum
            $node->attribute('target', '_blank');

            if (isset($extension['fmfm']) && $fmfmOption)
            {
                if ($extension['fmfm']['ddial'] == 'DIRECT')
                {
                    $grplist = preg_split("/-/", $extension['fmfm']['grplist']);
                    foreach ($grplist as $g)
                    {
                        $follow = sprintf('from-did-direct,%s,1', str_replace('#', '', trim($g)));
                        $this->findNextDestination($route, $node, $follow, sprintf(_(" FMFM (%s)"), $this->dpp->secondsToTimes($extension['fmfm']['prering'])));

                        // $route['parent_node']       = $node;
                        // $route['parent_edge_label'] = sprintf(_(" FMFM (%s)"), $this->dpp->secondsToTimes($extension['fmfm']['prering']));

                        // $this->dpp->followDestinations($route, $this->applyLanguage($follow), '');
                    }

                    if (isset($extension['fmfm']['postdest']) && $extension['fmfm']['postdest']!='ext-local,'.$extnum.',dest')
                    {
                        $this->findNextDestination($route, $node, $extension['fmfm']['postdest'], _(" FMFM No Answer"));
                        // $route['parent_node']       = $node;
                        // $route['parent_edge_label'] = _(" FMFM No Answer");

                        // $this->dpp->followDestinations($route, $this->applyLanguage($extension['fmfm']['postdest']), '');
                    }
                }
            }
        }
        else
        {
            //phone numbers or remote extensions
            $node->attribute('label', $extnum);
            $node->attribute('tooltip', $node->getAttribute('label'));
        }

        $node->attribute('label', $this->dpp->sanitizeLabels($label));
        $node->attribute('shape', 'rect');
        $node->attribute('fillcolor', self::pastels[15]);
        $node->attribute('style', 'filled');

        //Optional Destinations
        if ($extOptional && (!empty($extension['noanswer_dest']) || !empty($extension['busy_dest']) || !empty($extension['chanunavail_dest'])) )
        {
            if ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['noanswer_dest'] === $extension['chanunavail_dest'])
            {
                // All three are equal
                $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer, Busy, Not Reachable"));
                // $route['parent_node']       = $node;
                // $route['parent_edge_label'] = _(" No Answer, Busy, Not Reachable");

                // $this->dpp->followDestinations($route, $this->applyLanguage($extension['noanswer_dest']), '');
            }
            elseif ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['chanunavail_dest'] !== $extension['noanswer_dest'])
            {
                if (!empty($extension['noanswer_dest']))
                {
                    // No Answer and Busy are the same, but Not Reachable is different
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer & Busy"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" No Answer & Busy");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['noanswer_dest']), '');
                }

                //Not Reachable
                if (!empty($extension['chanunavail_dest']))
                {
                    $this->findNextDestination($route, $node, $extension['chanunavail_dest'], _(" Not Reachable"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" Not Reachable");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['chanunavail_dest']), '');
                }
            }
            elseif ($extension['noanswer_dest'] === $extension['chanunavail_dest'] && $extension['busy_dest'] !== $extension['noanswer_dest'])
            {
                if (!empty($extension['noanswer_dest']))
                {
                    // No Answer and Not Reachable are the same
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer & Not Reachable"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" No Answer & Not Reachable");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['noanswer_dest']), '');
                }

                //Busy
                if (!empty($extension['busy_dest']))
                {
                    $this->findNextDestination($route, $node, $extension['busy_dest'], _(" Busy"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" Busy");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['busy_dest']), '');
                }
            }
            elseif ($extension['busy_dest'] === $extension['chanunavail_dest'] && $extension['noanswer_dest'] !== $extension['busy_dest'])
            {
                if (!empty($extension['busy_dest']))
                {
                    // Busy and Not Reachable are the same
                    $this->findNextDestination($route, $node, $extension['busy_dest'], _(" Busy & Not Reachable"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" Busy & Not Reachable");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['busy_dest']), '');
                }

                //No Answer
                if (!empty($extension['noanswer_dest']))
                {
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" No Answer");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['noanswer_dest']), '');
                }
            }
            else
            {
                // All are different
                if (!empty($extension['noanswer_dest']))
                {
                    $this->findNextDestination($route, $node, $extension['noanswer_dest'], _(" No Answer"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" No Answer");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['noanswer_dest']), '');
                }
                if (!empty($extension['busy_dest']))
                {
                    $this->findNextDestination($route, $node, $extension['busy_dest'], _(" Busy"));
                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" Busy");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['busy_dest']), '');
                }
                if (!empty($extension['chanunavail_dest']))
                {
                    $this->findNextDestination($route, $node, $extension['chanunavail_dest'], _(" Not Reachable"));

                    // $route['parent_node']       = $node;
                    // $route['parent_edge_label'] = _(" Not Reachable");

                    // $this->dpp->followDestinations($route, $this->applyLanguage($extension['chanunavail_dest']), '');
                }
            }
        }
    }
}
