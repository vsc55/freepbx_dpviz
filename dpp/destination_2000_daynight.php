<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationDaynight extends baseDestinations
{
    public const PRIORITY = 2000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-daynight,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $daynightnum   = $matches[1];
        $daynightother = $matches[2];
        $daynight 	   = $route['daynight'][$daynightnum];
    
        #feature code exist?
        if ( isset($route['featurecodes']['*28'.$daynightnum]) )
        {
            #custom feature code?
            if ($route['featurecodes']['*28'.$daynightnum]['customcode'] != '')
            {
                $featurenum = $route['featurecodes']['*28'.$daynightnum]['customcode'];
            }
            else
            {
                $featurenum = $route['featurecodes']['*28'.$daynightnum]['defaultcode'];
            }
            #is it enabled?
            if ($route['featurecodes']['*28'.$daynightnum]['enabled']=='1')
            {
                $code = '\\nToggle (enabled): '.$featurenum;
            }
            else
            {
                $code = '\\nToggle (disabled): '.$featurenum;
            }
        }
        else
        {
            $code = '';
        }
            
        #check current status and set path to active
        $C ='/usr/sbin/asterisk -rx "database show DAYNIGHT/C'.$daynightnum.'" | cut -d \':\' -f2 | tr -d \' \' | head -1';
        exec($C, $current_daynight);
        $dactive = $nactive = "";
        if ($current_daynight[0]=='DAY')
        {
            $dactive = "(Active)";
        }
        else
        {
            $nactive = "(Active)";
        }
    
        foreach ($daynight as $d)
        {
            if ($d['dmode']=='day')
            {
                $route['parent_edge_label'] = ' Day Mode '.$dactive;
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $d['dest'], '');
            }
            elseif ($d['dmode']=='night')
            {
                $route['parent_edge_label'] = ' Night Mode '.$nactive;
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $d['dest'],'');
            }
            elseif ($d['dmode']=="fc_description")
            {
                $node->attribute('label', "Call Flow: ".$this->dpp->sanitizeLabels($d['dest']) .$code);
            }
        }
        
        $daynight = $route['daynight'][$daynightnum];
        $node->attribute('URL', htmlentities('/admin/config.php?display=daynight&view=form&itemid='.$daynightnum.'&extdisplay='.$daynightnum));
        $node->attribute('target', '_blank');
        $node->attribute('fillcolor', self::pastels[14]);
        $node->attribute('style', 'filled');
    }
}