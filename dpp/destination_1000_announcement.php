<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationAnnouncement extends baseDestinations
{
    public const PRIORITY = 1000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-announcement-(\d+),s,(\d+),(.+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of app-announcement-<num>,s,<num>,<lang>

        $annum   = $matches[1];
        $another = $matches[2];
        $anlang  = $matches[3];

        $an    = $route['announcements'][$annum];
        $recID = $an['recording_id'];

        if (isset($route['recordings'][$recID]))
        {
			$recording    = $route['recordings'][$recID];
			$announcement = $recording['displayname'];
			$recordingId  = $recording['id'];
		}
        else
        {
			$announcement = _("None");
		}

        #feature code exist?
        if ( isset($route['featurecodes']['*29'.$recID]) )
        {
            #custom feature code?
            if ($route['featurecodes']['*29'.$an['recording_id']]['customcode'] != '')
            {
                $featurenum = $route['featurecodes']['*29'.$an['recording_id']]['customcode'];
            }
            else
            {
                $featurenum = $route['featurecodes']['*29'.$an['recording_id']]['defaultcode'];
            }
            $rec_active = ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') ? _("yes"): _("no");
            $rec_status = $featurenum;
        }
        else
        {
            $rec_status = _("disabled");
            $rec_active = _("no");
        }

        $label = sprintf(_("Announcements: %s\\nRecording: %s\\nRecord (%s): %s"), $an['description'], $announcement, $rec_active, $rec_status);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => $this->genUrlConfig('announcement', $annum), //'/admin/config.php?display=announcement&view=form&extdisplay='.$annum
            'target'    => '_blank',
            'shape'     => 'note',
            'fillcolor' => 'oldlace',
            'style'     => 'filled'
        ]);

        # The destinations we need to follow are the no-answer destination
        # (postdest) and the members of the group.

        if ($an['post_dest'] != '')
        {
            $this->findNextDestination($route, $node, $an['post_dest'], _(" Continue"));
        }

        if (isset($route['recordings'][$recID]))
        {
            // The parameter $appyLang is set to false because the destination is already in the correct format
            $this->findNextDestination($route, $node,
                $this->applyLanguage(
                    sprintf('play-system-recording,%s,1', $recordingId),
                    $anlang
                ),
                _(" Recording"),
                false
            );
		}
    }
}
