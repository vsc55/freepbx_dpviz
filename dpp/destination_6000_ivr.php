<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationIvr extends baseDestinations
{
    public const PRIORITY = 6000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ivr-(\d+),([a-z]+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $inum   = $matches[1];
        $iflag  = $matches[2];
        $iother = $matches[3];

        $ivr        = $route['ivrs'][$inum];
        $recID      = $ivr['announcement'] ?? '';
        $ivrName    = $ivr['name'] ?? '';
        $ivrRecName = _("None");

        if (isset($route['recordings'][$recID]))
        {
			$recording   = $route['recordings'][$recID];
			$ivrRecName  = $recording['displayname'];
			$recordingId = $recording['id'];
		}

        $ivrDestination        = $ivr['invalid_destination'] ?? '';
        $ivrDestinationTimeout = $ivr['timeout_destination'] ?? '';

        #feature code exist?
        if ( isset($route['featurecodes']['*29'.$recID]) )
        {
            #custom feature code?
            if ($route['featurecodes']['*29'.$recID]['customcode']!='')
            {
                $featurenum = $route['featurecodes']['*29'.$recID]['customcode'];
            }
            else
            {
                $featurenum = $route['featurecodes']['*29'.$recID]['defaultcode'];
            }
            #is it enabled?
            $rec_active = ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') ? _("yes"): _("no");
            $rec_status = $featurenum;
        }
        else
        {
            $rec_status = _("disabled");
            $rec_active = _("no");
        }

        $label = sprintf(_("IVR: %s\\nAnnouncement: %s\\nRecord (%s): %s\\n"), $ivrName, $ivrRecName, $rec_active, $rec_status);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => htmlentities('/admin/config.php?display=ivr&action=edit&id='.$inum),
            'target'    => '_blank',
            'shape'     => 'component',
            'fillcolor' => 'gold',
            'style'     => 'filled',
        ]);

        # The destinations we need to follow are the invalid_destination,
        # timeout_destination, and the selection targets
        if (isset($route['recordings'][$recID]))
        {
            $this->findNextDestination($route, $node,
                sprintf('play-system-recording,%s,1', $recordingId),
                _(" Recording")
            );
		}

        #now go through the selections
        if (!empty($ivr['entries']))
        {
            ksort($ivr['entries']);
            foreach ($ivr['entries'] as $selid => $ent)
            {
                $this->findNextDestination($route, $node, $ent['dest'],
                    sprintf(_(" Selection %s"), $ent['selection'])
                );
            }
        }

        #are the invalid and timeout destinations the same?
        if ($ivrDestination == $ivrDestinationTimeout)
        {
            if (!empty($ivrDestination))
            {
                $this->findNextDestination($route, $node, $ivrDestination,
                    sprintf(_(" Invalid Input, Timeout (%s secs)"), $ivr['timeout_time'])
                );
            }
        }
        else
        {
            if ($ivrDestination != '')
            {
                $this->findNextDestination($route, $node, $ivrDestination, _(" Invalid Input"));
            }
            if ($ivrDestinationTimeout != '')
            {
                $this->findNextDestination($route, $node, $ivrDestinationTimeout,
                    sprintf(_(" Timeout (%s secs)"), $ivr['timeout_time'])
                );
            }
        }
    }
}
