<?php

namespace hiqdev\yii2\cart;

use \yii\base\Widget;

interface RelatedPositionInterface
{
    /**
     * @param $type
     * @param array $params
     * @see \Yii::createObject()
     */
    public function configure($type, array $params = []): RelatedPositionInterface;

    public function render(): string;
}
