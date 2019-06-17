<?php

namespace mirocow\dropzone;

use League\Flysystem\FilesystemInterface;
use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Class DropzoneBehavior
 * @package mirocow\dropzone
 */
class DropzoneBehavior extends Behavior
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $attribute;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var DropzoneStorageManagerInterface
     */
    protected $manager;

    /**
     * DropzoneBehavior constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param DropzoneStorageManagerInterface $manager
     * @param array $config
     */
    public function __construct(
        FilesystemInterface $filesystem,
        DropzoneStorageManagerInterface $manager,
        array $config = []
    ) {
        $this->filesystem = $filesystem;
        $this->manager = $manager;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @param Event $event
     */
    public function afterFind(Event $event)
    {
        /** @var StorageInterface $model */
        $model = $event->sender;

        $where = [
            'model' => $model->getModel(),
            'recordId' => $model->getRecordId(),
            'attribute' => $this->attribute,
        ];

        /** @var StorageDto $dto */
        $dto = Yii::createObject(FindService::class, [$where])->all();

        $model->{$this->attribute} = $dto;
    }

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        /** @var StorageInterface $sender */
        $sender = $event->sender;
        $list = $this->manager->pull($this->key);

        foreach ($list as ['file' => $row]) {

            $cachePath = $this->manager->getCachePath($row);
            $name = $this->filesystem->getHashGrid($cachePath);
            $storagePath = $this->manager->getStoragePath($name);

            $resource = $this->filesystem->readStream($cachePath);
            if ($this->filesystem->writeStream($storagePath, $resource)) {
                $adapter = Yii::createObject(FilesystemAdapter::class, [$this->manager->getStorageDirectory(), $name]);
                $file = Yii::createObject(File::class, [$adapter]);

                Yii::createObject(CreateService::class, [$sender, $this->attribute, $file])->execute();

                if ($this->manager->remove($this->key, $row) && $this->filesystem->has($cachePath)) {
                    $this->filesystem->delete($cachePath);
                }
            }
        }

        if(!empty($sender->hints)) {
            foreach ($sender->hints as $id => $hint) {
                $where = [
                    'id' => $id,
                ];

                $update = [
                    'hint' => $hint,
                ];

                Yii::createObject(UpdateService::class, [$where, $update])->execute();
            }
        }
    }

    /**
     * @param Event $event
     */
    public function beforeDelete(Event $event)
    {
        /** @var StorageInterface|ActiveRecord $model */
        $model = $event->sender;

        $this->delete($model, $event);
    }

    /**
     * @param Event $event
     */
    public function afterDelete(Event $event)
    {
        // todo
    }

    protected function delete(StorageInterface $model, Event $event)
    {
        $where = [
            'model' => $model->getModel(),
            'recordId' => $model->getRecordId(),
            'attribute' => $this->attribute,
        ];

        /** @var StorageDto $dto */
        $dto = Yii::createObject(FindService::class, [$where])->all();

        foreach ($dto as $item) {
            if ($item instanceof StorageDto) {
                $path = $this->manager->getStoragePath($item->getSrc());

                if ($this->filesystem->has($path)) {
                    $this->filesystem->delete($path);
                }

                Yii::createObject(DeleteService::class, [$model, $this->attribute])->execute();
            }
        }

        Event::trigger(StorageBehaviorInterface::class, StorageBehaviorInterface::EVENT_BEFORE_DELETE, $event);
    }
}
