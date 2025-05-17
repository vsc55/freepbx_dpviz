<?php

namespace FreePBX\modules\Dpviz\dpp\table;

require_once __DIR__ . '/BaseDpp.php';

use FreePBX\modules\Dpviz\dpp\BaseDpp;

abstract class BaseTables extends BaseDpp
{
    protected $route = null;

    protected $tableName = '';
    protected $optional  = false;
    protected $callBack  = 'callbackLoad';

    protected $key_id   = "id";
    protected $key_name = "";

    public const PRIORITY = 0;

    /**
     * Constructor
     *
     * @param object $dpp       The dpp object
     * @param string $tableName The name of the table
     * @param bool   $optional  Whether the table is optional or not
     */
    public function __construct(object &$dpp, string $tableName = '', bool $optional = false)
    {
        parent::__construct($dpp);

        $this->optional  = $optional;
        $this->tableName = $tableName;
        $this->route     = &$dpp->dproutes;
    }

    /**
     * Get is the table is optional or not
     * @return bool true if the table is optional, false otherwise
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * Check if the table exists in the database
     * @return bool true if the table exists, false otherwise
     */
    protected function isExistTable(): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->dpp->fetchAll($sql, [$this->getTableName()]);
        return !empty($result);
    }

    /**
     * Get the table name
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get the table data from the database
    */
    protected function getTableData(): array
    {
        if (!$this->isExistTable()) {
            return [];
        }

        $sql    = sprintf("SELECT * FROM %s", $this->getTableName());
        $return = $this->dpp->fetchAll($sql);
        return is_array($return) ? $return : [];
    }

    public function load(): ?bool
    {
        // callback function to load the table
        if (!$this->isExistTable()) {
            $this->log(9, sprintf("Skip, table '%s' not exist!", $this->getTableName()));
            return false;
        }

        // call the callback function to load the table
        if (method_exists($this, $this->callBack)) {
            $this->log(9, sprintf("Call function '%s' in '%s' table", $this->callBack, $this->getTableName()));

            $callback = [$this, $this->callBack];
            $args     = [&$this->route];

            return call_user_func_array($callback, $args);
        }

        if (empty($this->key_name) || empty($this->key_id)) {
            $this->log(9, sprintf("Skip, table '%s' has no key_name or key_id!", $this->getTableName()));
            return false;
        }

        foreach ($this->getTableData() as $item) {
            if (!$this->checkItemLoad($item)) {
                continue;
            }
            $id = $this->getId($item);
            if ($this->skipIfEmptyAny([$id => $this->key_id])) {
                continue;
            }
            $this->setRoute($id, $item);
        }
        return true;
    }

    protected function getId($item, $default = null)
    {
        return $item[$this->key_id] ?? $default;
    }

    protected function isNewRoute($id): bool
    {
        return !isset($this->route[$this->key_name][$id]);
    }

    protected function setRoute($id, &$item, bool $force = false, bool $log = true, ?string $msg = null, array $args = [], int $level = 9): bool
    {
        $isNew = $this->isNewRoute($id);
        $this->route[$this->key_name][$id] = $isNew || $force ? $item : array_merge($this->route[$this->key_name][$id], $item);

        if ($log) {
            $this->logRoute($id, $isNew, $msg, $args, $level);
        }
        return true;
    }

    protected function checkItemLoad(&$item): bool
    {
        if (!is_array($item) || !isset($item[$this->key_id])) {
            $this->log(5, sprintf("Skip item, not an array or no key_id '%s'", $this->key_id));
            return false;
        }
        return true;
    }

    protected function logRoute($id, bool $isNew, ?string $msg = null, array $args = [], int $level = 9): bool
    {
        $msg         = $msg ?? "{action}  >>  {table}  >  key [{key}]    id [{id}]";
        $defaultArgs = [
            '{action}' => $isNew ? _("New") : _("Update"),
            '{key}'    => $this->key_name,
            '{id}'     => $id,
            '{table}'  => $this->getTableName(),
        ];
        // Add the default arguments to the args array
        // and merge them with the existing args
        // This will ensure that the default arguments are always present
        // and the existing args are preserved
        $finalArgs   = array_merge($defaultArgs, $args);

        preg_match_all('/\{[a-z_]+\}/i', $msg, $matches);
        $placeholdersInOrder = $matches[0]; // e.g. ['{table}', '{id}', '{action}']
        $orderedArgs = [];
        foreach ($placeholdersInOrder as $tag) {
            if (array_key_exists($tag, $finalArgs)) {
                $msg = str_replace($tag, '%s', $msg);
                $orderedArgs[] = $finalArgs[$tag];
            }
        }

        // $orderedArgs = [];
        // foreach ($finalArgs as $tag => $value) {
        //     if (str_contains($msg, $tag)) {
        //         $msg = str_replace($tag, '%s', $msg);
        //         $orderedArgs[] = $value;
        //     }
        // }

        $this->log($level, $this->safeFormatFill($msg, $orderedArgs));
        return true;
    }
}
