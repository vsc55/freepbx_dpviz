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
            $label     = sprintf(_('Extension: %s %s\\n%s'), $extnum, $extname, $extemail);

            if ($fmfmOption)
            {
                if (isset($extension['fmfm']) && $extension['fmfm']['ddial'] == 'DIRECT')
                {
                    $fmfmLabel = sprintf(_("FMFM Enabled\\nInitial Ring Time=%s\\nRing Time=%s\\nConfirm Calls=%s"), $this->dpp->secondsToTimes($extension['fmfm']['prering']), $this->dpp->secondsToTimes($extension['fmfm']['grptime']), $extension['fmfm']['grpconf']);
                }
                else
                {
                   $fmfmLabel = _("FMFM Disabled");
                }
            }
            else
            {
                $fmfmLabel = '';
            }
            $labeltooltip = sprintf(_('%s\\n%s'), $label, $fmfmLabel);

            $node->attribute('label', $this->dpp->sanitizeLabels($label));
            $node->attribute('tooltip',$this->dpp->sanitizeLabels($labeltooltip));
            $node->attribute('URL', $this->genUrlConfig('extensions', $extnum, null)); //'/admin/config.php?display=extensions&extdisplay='.$extnum
            $node->attribute('target', '_blank');

            if (isset($extension['fmfm']))
            {
                if ($extension['fmfm']['ddial'] == 'DIRECT')
                {
                    $grplist = preg_split("/-/", $extension['fmfm']['grplist']);
                    foreach ($grplist as $g)
                    {
                        $follow = sprintf('from-did-direct,%s,1', str_replace('#', '', trim($g)));

                        $route['parent_node']       = $node;
                        $route['parent_edge_label'] = sprintf(_(' FMFM (%s)'), $this->dpp->secondsToTimes($extension['fmfm']['prering']));

                        $this->dpp->followDestinations($route, $follow, '');
                    }
                }
            }
        }
        else
        {
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
                $route['parent_node']       = $node;
                $route['parent_edge_label'] = _(' No Answer, Busy, Not Reachable');

                $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
            }
            elseif ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['chanunavail_dest'] !== $extension['noanswer_dest'])
            {
                if (!empty($extension['noanswer_dest']))
                {
                    // No Answer and Busy are the same, but Not Reachable is different
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' No Answer & Busy');

                    $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
                }

                //Not Reachable
                if (!empty($extension['chanunavail_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Not Reachable');

                    $this->dpp->followDestinations($route, $extension['chanunavail_dest'], '');
                }
            }
            elseif ($extension['noanswer_dest'] === $extension['chanunavail_dest'] && $extension['busy_dest'] !== $extension['noanswer_dest'])
            {
                if (!empty($extension['noanswer_dest']))
                {
                    // No Answer and Not Reachable are the same
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' No Answer & Not Reachable');

                    $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
                }

                //Busy
                if (!empty($extension['busy_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Busy');

                    $this->dpp->followDestinations($route, $extension['busy_dest'], '');
                }
            }
            elseif ($extension['busy_dest'] === $extension['chanunavail_dest'] && $extension['noanswer_dest'] !== $extension['busy_dest'])
            {
                if (!empty($extension['busy_dest']))
                {
                    // Busy and Not Reachable are the same
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Busy & Not Reachable');

                    $this->dpp->followDestinations($route, $extension['busy_dest'], '');
                }

                //No Answer
                if (!empty($extension['noanswer_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' No Answer');

                    $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
                }
            }
            else
            {
                // All are different
                if (!empty($extension['noanswer_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' No Answer');

                    $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
                }
                if (!empty($extension['busy_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Busy');

                    $this->dpp->followDestinations($route, $extension['busy_dest'], '');
                }
                if (!empty($extension['chanunavail_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Not Reachable');

                    $this->dpp->followDestinations($route, $extension['chanunavail_dest'],'');
                }
            }
        }
    }
}
