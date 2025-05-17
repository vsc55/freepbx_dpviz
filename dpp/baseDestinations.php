<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDpp.php';

use FreePBX\modules\Dpviz\dpp\BaseDpp;

abstract class BaseDestinations extends BaseDpp
{
    protected $regex = null;
    protected $lang   = null;

    public function isSetDestination(): bool
    {
        if (empty($this->regex)) {
            return false;
        }
        return true;
    }

    public function getDestinationRegEx(): ?string
    {
        if (! $this->isSetDestination()) {
            return null;
        }
        return $this->regex;
    }

    // This function hooks into the DPP class
    public function sanitizeLabels($text)
    {
        return $this->dpp->sanitizeLabels($text);
    }

    public function setLanguage($lang, &$node = null)
    {
        if (is_null($lang)) {
            $lang = '';
        }
        if (is_object($node) && method_exists($node, 'attribute')) {
            $this->updateNodeAttribute($node, ['comment' => $lang]);
        }
        $this->lang = $lang;
    }

    public function getLanguage($node, $lang = '')
    {
        if (is_null($lang)) {
            $lang = '';
        }
        if (empty($node) || !is_object($node) || !method_exists($node, 'getAttribute')) {
            $this->lang = $lang;
        } else {
            $this->lang = $node->getAttribute('comment', $lang);
        }
        return $this->lang;
    }

    public function applyLanguage($value, $lang = null, $node = null)
    {
        if (empty($value)) {
            return $value;
        }
        if (is_null($node) && is_null($lang)) {
            return $value;
        }
        if (!is_null($node)) {
            $lang = $node->getAttribute('comment', $lang);
        } elseif (is_null($lang)) {
            $lang = $this->lang;
        }

        if (empty($lang)) {
            return $value;
        }
        return sprintf("%s,%s", $value, $lang);
    }

    public function followDestinations(&$route, &$node, $destination, $matches)
    {
        $lang = $this->getLanguage($node);

        if (method_exists($this, 'callbackFollowDestinations')) {
            $callback = [$this, 'callbackFollowDestinations'];
            $args     = [&$route, &$node, $destination, $matches];
            return call_user_func_array($callback, $args);
        } else {
            $this->log(1, sprintf(_("No callback function found for followDestinations in '%s'"), get_class($this)));
            return false;
        }
    }

    protected function findNextDestination(&$route, &$node, $destination, $label = '', $appyLang = true)
    {
        if (empty($route) || empty($node)) {
            return false;
        }

        $route['parent_node']       = $node;
        $route['parent_edge_label'] = $this->sanitizeLabels($label);

        if ($appyLang) {
            $destination = $this->applyLanguage($destination);
        }

        $this->dpp->followDestinations($route, $destination, '');
        return true;
    }

    protected function updateNodeAttribute(&$node, $params = [])
    {
        if (empty($node) || !is_object($node) || !method_exists($node, 'attribute') || !is_array($params)) {
            return false;
        }
        foreach ($params as $key => $value) {
            if (in_array($key, ['label', 'tooltip'])) {
                if ($value == '__SKIP_NO_CHANGE__') {
                    continue;
                }
                $value = $this->sanitizeLabels($value);
            }
            $node->attribute($key, $value);
        }
        return true;
    }
}
