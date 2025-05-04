<?php
namespace FreePBX\modules\Dpviz\dpp;

abstract class baseDpp
{
    protected $dpp   = null;
    protected $dpviz = null;

    // Set some colors
    Const pastels = [
        "#7979FF", "#86BCFF", "#8ADCFF", "#3DE4FC", "#5FFEF7", "#33FDC0",
        "#ed9581", "#81a6a2", "#bae1e7", "#eb94e2", "#f8d580", "#979291",
        "#92b8ef", "#ad8086", "#F7A8A8", "#C5A3FF", "#FFC3A0", "#FFD6E0",
        "#FFB3DE", "#D4A5A5", "#A5D4D4", "#F5C6EC", "#B5EAD7", "#C7CEEA",
        "#E0BBE4", "#FFDFD3", "#FEC8D8", "#D1E8E2", "#E8D1E1", "#EAD5DC",
        "#F9E79F", "#D6EAF8"
    ];

    public function __construct(object &$dpp)
    {
        $this->dpp   = &$dpp;
        $this->dpviz = &$dpp->dpviz;
    }

    public function getSetting($name)
    {
        return $this->dpviz->getSetting($name);
    }

    public function asteriskRunCmd($cmd, $raw = false)
    {
        return $this->dpviz->asteriskRunCmd($cmd, $raw);
    }

    protected function processAsteriskLines(array $lines, callable $lineHandler, ?callable $lineFilter = null)
    {
        foreach ($lines as $line)
        {
            $line = trim($line);
            if ($line === '')
            {
                continue;
            }
            if ($lineFilter !== null && !$lineFilter($line))
            {
                continue;
            }
            if ($lineHandler($line) === false)
            {
                break;
            }
        }
    }

    /**
     * Relay the log function to the dpp class
     */
    protected function log($level, $message)
    {
        $this->dpp->log($level, $message);
    }

    public function genUrlConfig($display, $extdisplay, $view = 'form')
    {
        $url_view = is_null($view) ? '' : sprintf("&view=%s", $view);
        return htmlentities(sprintf('/admin/config.php?display=%s%s&extdisplay=%s', $display, $url_view, $extdisplay));
    }
}
