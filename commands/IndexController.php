<?php

namespace mikemadisonweb\elasticsearch\components\commands;

use yii\base\Module;
use yii\helpers\BaseConsole;
use yii\console\Controller;

/**
 * Manipulations with Elasticsearch indices based on configuration
 * @package mikemadisonweb\elasticsearch\components\commands
 */
class IndexController extends Controller
{
    public $interactive = true;

    /**
     * @var BaseConsole
     */
    protected $console;
    protected $options = [
        'i' => 'interactive',
    ];

    /**
     * IndexController constructor.
     * @param string $id
     * @param Module $module
     * @param array $config
     */
    public function __construct($id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->console = new BaseConsole();
    }

    /**
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), array_values($this->options));
    }

    /**
     * Create one or more indexes on server (enter comma separated index names)
     * @param string $indexNames
     */
    public function actionCreate($indexNames = '')
    {
        $search = \Yii::$app->elasticsearch;
        $confirm = $this->confirm('Warning! Existing indexes will be dropped and recreated, are you sure you want to proceed?');
        if ($confirm || !$this->interactive) {
            if ($indexNames) {
                $indexNames = explode(',', $indexNames);
                foreach ($indexNames as $indexName) {
                    $search->createIndexByName($indexName);
                    $this->stdout($this->ansiFormat("Index $indexName successfully created.\n", BaseConsole::FG_GREEN));
                }
            } else {
                $search->createAllIndexes();
                $this->stdout($this->ansiFormat("All configured indexes successfully created.\n", BaseConsole::FG_GREEN));
            }
        } else {
            $this->stdout($this->ansiFormat("Canceled by user.\n", BaseConsole::FG_RED));
        }
    }

    /**
     * Drop one or more indexes on server (enter comma separated index names)
     * @param string $indexNames
     */
    public function actionDrop($indexNames = '')
    {
        $search = \Yii::$app->elasticsearch;
        $confirm = $this->confirm('Warning! Indexes will be dropped and all data inside them will be lost, are you sure you want to proceed?');
        if ($confirm || !$this->interactive) {
            if ($indexNames) {
                $indexNames = explode(',', $indexNames);
                foreach ($indexNames as $indexName) {
                    $search->dropIndexByName($indexName);
                    $this->stdout($this->ansiFormat("Index $indexName was dropped.\n", BaseConsole::FG_GREEN));
                }
            } else {
                $search->dropAllIndexes();
                $this->stdout($this->ansiFormat("All configured indexes was dropped.\n", BaseConsole::FG_GREEN));
            }
        } else {
            $this->stdout($this->ansiFormat("Canceled by user.\n", BaseConsole::FG_RED));
        }
    }
}
