<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDpp.php';

use \FreePBX\modules\Dpviz\dpp\baseDpp;

abstract class baseDestinations extends baseDpp
{
    protected $regex = null;

    public function isSetDestination() : bool
    {
        if (empty($this->regex))
        {
            return false;
        }
        return true;
    }

    public function getDestinationRegEx() : ?string
    {
        if (! $this->isSetDestination())
        {
            return null;
        }
        return $this->regex;
    }

    public function followDestinations(&$route, &$node, $destination, $matches)
    {
        if (method_exists($this, 'callback_followDestinations'))
        {
            // return call_user_func([$this, 'callback_followDestinations'], $route, $node, $destination, $matches);
            $callback = [$this, 'callback_followDestinations'];
            $args     = [&$route, &$node, $destination, $matches];
            return call_user_func_array($callback, $args);
        }
        else
        {
            $this->log('error', sprintf(_('No callback function found for followDestinations in "%s"'), get_class($this)));
            return false;
        }
    }
}
