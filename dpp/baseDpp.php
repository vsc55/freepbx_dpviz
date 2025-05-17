<?php

namespace FreePBX\modules\Dpviz\dpp;

abstract class BaseDpp
{
    protected $dpp   = null;
    protected $dpviz = null;

    protected $deppendencies = [];

    // Set some colors
    public const PASTELS = [
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

        $this->deppendencies = [];
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
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if ($lineFilter !== null && !$lineFilter($line)) {
                continue;
            }
            if ($lineHandler($line) === false) {
                break;
            }
        }
    }

    /**
     * Relay the log function to the dpp class
     */
    protected function log(int $level, string $message)
    {
        $this->dpp->log($level, $message);
    }

    public function genUrlConfig($display, $extdisplay, $view = 'form')
    {
        $url_view = is_null($view) ? '' : sprintf("&view=%s", $view);
        return htmlentities(sprintf('/admin/config.php?display=%s%s&extdisplay=%s', $display, $url_view, $extdisplay));
    }

    protected function skipIfEmptyAny(array $vars, ?string $msg = null, ?array $args = null, int $level = 5): bool
    {
        $hasEmpty = false;
        $args     = $args ?? [];

        if (is_null($msg) || empty($msg)) {
            $msg = _("Skip, no exist '%s'");
        }

        foreach ($vars as $value => $label) {
            if (is_null($value) || $value === '') {
                // Find the index of the label in the args array
                // and replace it with the actual label
                // or add it to the beginning of the args array
                $fullArgs   = $args;
                $labelIndex = array_search('{label}', $args);
                if ($labelIndex !== false) {
                    $fullArgs[$labelIndex] = $label;
                } else {
                    array_unshift($fullArgs, $label);
                }

                $this->log($level, $this->safeFormatFill($msg, $fullArgs));
                $hasEmpty = true;
            }
        }
        return $hasEmpty;
    }

    protected function safeFormatFill(string $msg, array $args, ?string $default_unknown = null): string
    {
        if (is_null($default_unknown)) {
            $default_unknown = _("Unknown");
        }

        $expected = substr_count($msg, '%s');
        $missing  = $expected - count($args);

        if ($missing > 0) {
            $args = array_merge($args, array_fill(0, $missing, $default_unknown));
        }

        return vsprintf($msg, $args);
    }

    public function countDeppendency(): int
    {
        return count($this->getDependencies());
    }

    public function needDependencies(): bool
    {
        return $this->countDeppendency() > 0;
    }

    public function getDependencies(): array
    {
        $deppendencies = $this->deppendencies ?? [];
        return array_keys($deppendencies);
    }

    public function checkDeppendency(array $deppendencies): bool
    {
        return empty(array_diff($this->getDependencies(), $deppendencies));
    }
}
