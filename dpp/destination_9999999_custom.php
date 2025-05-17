<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationCustom extends BaseDestinations
{
    public const PRIORITY = 9999999;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/.*/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        if (!empty($route['customapps'])) {
            #custom destinations
            $custDest = null;
            foreach ($route['customapps'] as $entry) {
                if (sprintf("%s,%s", $entry['target'], $this->lang) === $destination) {
                    $custDest = $entry;
                    break;
                }
            }
        }
        #end of Custom Destinations

        if (!empty($custDest)) {
            $custId     = $custDest['destid'];
            $custReturn = ($custDest['destret'] == 1) ? _("Yes") : _("No");
            $label      = sprintf(_("Cust Dest: %s\\nTarget: %s\\nReturn: %s\\n"), $entry['description'], $entry['target'], $custReturn);

            $this->updateNodeAttribute($node, [
                'label'     => $label,
                'tooltip'   => $label,
                'URL'       => htmlentities('/admin/config.php?display=customdests&view=form&destid=' . $custId),
                'target'    => '_blank',
                'shape'     => 'component',
                'fillcolor' => self::PASTELS[27],
                'style'     => 'filled',
            ]);

            if ($custDest['destret']) {
                $this->findNextDestination($route, $node, $custDest['dest'], _(' Return'));
            }
        } else {
            $this->log(1, sprintf(_("Unknown destination type: %s"), $destination));

            // Call debug_backtrace to get the backtrace
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $seen = [];
            $maxFileLen = 0;

            foreach ($backtrace as $trace) {
                $file = $trace['file'] ?? '[internal]';
                $line = $trace['line'] ?? 0;
                $len = strlen("$file:$line");
                if ($len > $maxFileLen) {
                    $maxFileLen = $len;
                }
            }

            foreach ($backtrace as $i => $trace) {
                $file = $trace['file'] ?? '[internal]';
                $line = $trace['line'] ?? 0;
                $function = $trace['function'] ?? '[unknown]';

                $key = "$file:$line:$function";
                if (isset($seen[$key])) {
                    $seen[$key]++;
                    continue;
                } else {
                    $seen[$key] = 1;
                }

                $fileInfo = sprintf("%-{$maxFileLen}s", "$file:$line");
                $this->log(1, sprintf("[#%02d] %s  →  %s()", $i, $fileInfo, $function));
            }

            foreach ($seen as $key => $count) {
                if ($count > 1) {
                    $this->log(1, sprintf("↪ Repeated %d times: %s", $count, $key));
                }
            }

            $this->updateNodeAttribute($node, [
                'label'     => sprintf(_("Custom: %s"), $destination),
                'fillcolor' => self::PASTELS[12],
                'style'    => 'filled',
            ]);
        }
    }
}
