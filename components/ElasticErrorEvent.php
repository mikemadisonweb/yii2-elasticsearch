<?php

namespace mikemadisonweb\elasticsearch\components;

use yii\base\Event as BaseEvent;

class ElasticErrorEvent extends BaseEvent
{
    const BULK_ERRORS = 'bulk_errors';

    public $indexName;
    public $documentId;
    public $error;
    public $client;
}
