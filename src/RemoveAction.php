<?php

namespace mirocow\dropzone;

use Yii;
use yii\base\Action;

/**
 * Class RemoveAction
 * @package mirocow\dropzone
 */
class RemoveAction extends Action
{
    public $uploadDir = '@webroot/upload';

    public function run($fileName)
    {
        return (int)unlink(Yii::getAlias($this->uploadDir) . '/' . $fileName);
    }
}