<?php

namespace mirocow\dropzone;

use mirocow\dropzone\assets\DropZoneAsset;
use Yii;
use yii\base\View;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Request;

/**
 * Class DropZoneWidget
 * @see https://www.dropzonejs.com/#installation
 * @package mirocow\dropzone
 */
class DropZoneWidget extends Widget
{
    public $model;
    public $attribute;
    public $name;
    public $url;
    public $message;
    public $htmlOptions = [];
    public $clientOptions = [];
    public $clientEvents = [];
    public $storedFiles = [];
    public $sortable = false;
    public $sortableOptions = [];
    public $messageOptions = [];

    /**
     * @var bool
     */
    public $autoDiscover = false;

    protected $dropzoneName = 'dropzone';

    public function init()
    {
        parent::init();

        Html::addCssClass($this->htmlOptions, 'dropzone');
        Html::addCssClass($this->messageOptions, 'dz-message');
        $this->dropzoneName = 'dropzone_' . $this->id;

        if (Yii::$app->getRequest()->enableCsrfValidation) {
            $this->clientOptions['headers'][Request::CSRF_HEADER] = Yii::$app->getRequest()->getCsrfToken();
            $this->clientOptions['params'][Yii::$app->getRequest()->csrfParam] = Yii::$app->getRequest()->getCsrfToken();
        }
    }

    private function registerAssets()
    {
        DropZoneAsset::register($this->getView());
        $this->getView()->registerJs('Dropzone.options.myAwesomeDropzone = '.($this->autoDiscover === false ? 'false' : 'true').';');
        $this->getView()->registerJs('Dropzone.autoDiscover = '.($this->autoDiscover === false ? 'false' : 'true').';');
    }

    protected function addFiles($files = [])
    {
        if (empty($files) === false) {
            $this->view->registerJs('var files = ' . Json::encode($files) . ';') ;
            $this->view->registerJs('for (var i=0; i<files.length; i++) {
                ' . $this->dropzoneName . '.emit("addedfile", files[i]);
                ' . $this->dropzoneName . '.emit("thumbnail", files[i], files[i]["thumbnail"]);
                ' . $this->dropzoneName . '.emit("complete", files[i]);
            }');
        }
    }

    protected function decrementMaxFiles($num)
    {
        if ($num > 0) {
            $this->getView()->registerJs(
                'if (' . $this->dropzoneName . '.options.maxFiles > 0) { '
                . $this->dropzoneName . '.options.maxFiles = '
                . $this->dropzoneName . '.options.maxFiles - ' . $num . ';'
                . ' }'
            );
        }
    }

    protected function createDropzone()
    {
        $clientOptions = Json::encode($this->clientOptions);
        $this->getView()->registerJs($this->dropzoneName . ' = new Dropzone("#' . $this->id . '", ' . $clientOptions . ');');
    }

    public function run()
    {
        if (empty($this->name) && (!empty($this->model) && !empty($this->attribute))) {
            $this->name = Html::getInputName($this->model, $this->attribute);
        }

        if (empty($this->url)) {
            $this->url = Url::toRoute(['site/upload']);
        }

        $clientOptions = [
            'url' => $this->url,
            'paramName' => $this->name,
            'params' => [],
        ];
        $this->clientOptions = ArrayHelper::merge($this->clientOptions, $clientOptions);

        if (!empty($this->message)) {
            $message = Html::tag('div', $this->message, $this->messageOptions);
        } else {
            $message = '';
        }

        $this->htmlOptions['id'] = $this->id;
        echo Html::tag('div', $message, $this->htmlOptions);

        $this->registerAssets();

        $this->createDropzone();

        foreach ($this->clientEvents as $event => $handler) {
            $handler = new \yii\web\JsExpression($handler);
            $this->getView()->registerJs(
                $this->dropzoneName . ".on('{$event}', {$handler})"
            );
        }

        $this->addFiles($this->storedFiles);
        $this->decrementMaxFiles(count($this->storedFiles));

        if ($this->sortable) {
            $sortableOptions = Json::encode($this->sortableOptions);
            $this->getView()->registerJs("jQuery('#{$this->id}').sortable(" . $sortableOptions . ");");
        }
    }
}