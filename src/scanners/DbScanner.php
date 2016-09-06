<?php

namespace lajax\projectscanner\scanners;

use Yii;
use yii\base\Object;
use yii\helpers\Console;

/**
 * Detecting existing language elements in database.
 * The connection ids of the scanned databases and the table/field names can be defined in the configuration file of translateManager
 * examples:
 *
 * ~~~
 * 'tables' => [
 *  [
 *      'connection' => 'db',
 *      'table' => 'language',
 *      'columns' => ['name', 'name_ascii'],
 *      'category' => 'tableName',
 *  ],
 *  [
 *      'connection' => 'db',
 *      'table' => 'tag',
 *      'columns' => ['name'],
 *      'category' => 'tableName',
 *  ],
 *  [
 *      'connection' => 'db',
 *      'table' => 'category',
 *      'columns' => ['name', 'description']
 *  ]
 * ]
 * ~~~
 *
 *
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class DbScanner extends Object implements ScannerInterface
{

    /**
     * @var array array containing the table ids to process.
     */
    public $tables;

    /**
     * @var \lajax\projectscanner\Scanner component containing the detected language elements
     */
    public $scanner;

    /**
     * @var string Default database category.
     */
    public $category = 'database';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->tables && is_array($this->tables)) {
            foreach ($this->tables as $tables) {
                if (empty($tables['connection'])) {
                    throw new InvalidConfigException('Incomplete database  configuration: connection');
                } else if (empty($tables['table'])) {
                    throw new InvalidConfigException('Incomplete database  configuration: table');
                } else if (empty($tables['columns'])) {
                    throw new InvalidConfigException('Incomplete database  configuration: columns');
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {

        $this->scanner->stdout('Detect DatabaseTable - BEGIN', Console::FG_GREY);

        if (is_array($this->tables)) {
            foreach ($this->tables as $tables) {
                $this->scanTable($tables);
            }
        }

        $this->scanner->stdout('Detect DatabaseTable - END', Console::FG_GREY);
    }

    /**
     * Scanning database table
     * @param array $tables
     */
    protected function scanTable($tables)
    {
        $this->scanner->stdout('Extracting mesages from ' . $tables['table'] . '.' . implode(',', $tables['columns']), Console::FG_GREEN);
        $query = new \yii\db\Query();
        $data = $query->select($tables['columns'])
                ->from($tables['table'])
                ->createCommand(Yii::$app->{$tables['connection']})
                ->queryAll();
        $category = $this->getCategory($tables);
        foreach ($data as $columns) {
            $columns = array_map('trim', $columns);
            foreach ($columns as $column) {
                $this->scanner->addLanguageItem($category, $column);
            }
        }
    }

    /**
     * Returns the language category.
     * @param array $tables
     * @return string
     */
    protected function getCategory($tables)
    {
        if (isset($tables['category'])) {
            if ($tables['category'] === 'tableName') {
                $category = $this->normalizeTablename($tables['table']);
            } else {
                $category = $tables['category'];
            }
        } else {
            if ($this->category === 'tableName') {
                $category = $this->normalizeTablename($tables['table']);
            } else {
                $category = $this->category;
            }
        }

        return $category;
    }

    /**
     * Returns the normalized database table name.
     * @param string $tableName database table name.
     * @return string
     */
    protected function normalizeTablename($tableName)
    {
        return str_replace(['{', '%', '}'], '', $tableName);
    }

}
