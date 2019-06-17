<?php

namespace mirocow\dropzone\assets;

use yii\web\AssetBundle;

/**
 * Class DropZoneAsset
 * @package mirocow\dropzone\assets
 */
class DropZoneAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@bower/dropzone/dist';

    /**
     * @var array
     */
    public $js = [
        YII_ENV_DEV ? 'dropzone.js' : 'min/dropzone.min.js',
    ];

    /**
     * @var array
     */
    public $css = [
        YII_ENV_DEV ? 'basic.css' : 'min/basic.min.css',
    ];

}
