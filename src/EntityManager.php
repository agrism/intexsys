<?php

namespace Agrism\Intexsys;

use SplSubject;
use SplObserver;
use Exception;

//This class managed in-memory entities and communicates with the storage class (DataStore in our case).
class EntityManager implements SplSubject
{
    /**
     * @var array<mixed|Entity>
     */
    protected array $_entities = [];
    /**
     * @var array<string|int,string|int>
     */
    protected array $_entityIdToPrimary = [];
    /**
     * @var array<string|int,int|string>
     */
    protected array $_entityPrimaryToId = [];
    /**
     * @var int[]
     */
    protected array $_entitySaveList = [];

    protected ?int $_nextId = null;

    protected ?DataStore $_dataStore = null;

    public ?Entity $entityBeforeUpdate = null;
    public ?Entity $entityAfterUpdate = null;

    /**
     * @throws Exception
     */
    public function __construct(string $storePath)
    {
        $this->_dataStore = new DataStore($storePath);

        $this->_nextId = 1;

        $itemTypes = $this->_dataStore->getItemTypes();

        foreach ($itemTypes as $itemType) {
            $itemKeys = $this->_dataStore->getItemKeys($itemType);
            foreach ($itemKeys as $itemKey) {
                $this->_entities[] = $this->create($itemType, $this->_dataStore->get($itemType, $itemKey), true);
            }
        }
    }

    /**
     * @var SplObserver[]
     */
    private array $observers = [];

    public function attach(SplObserver $observer): void
    {
        $this->observers[sha1(serialize($observer))] = $observer;
    }

    public function detach(SplObserver $observer): void
    {
        unset($this->observers[sha1(serialize($observer))]);
    }

    public function notify(): void
    {
        foreach ($this->observers as $obesrver) {
            $obesrver->update($this);
        }
    }

    //create an entity

    /**
     * @param array<string|mixed> $data
     */
    public function create(string $entityName, array $data, bool $fromStore = false): Entity
    {
        /** @var Entity $entity */
        $entity = new $entityName;
        $entity->_data = $data;
        $entity->_em = Entity::getDefaultEntityManager();
        $id = $entity->_id = $this->_nextId++;
        $this->_entities[$id] = $entity;
        $primary = $data[$entity->getPrimary()];
        $this->_entityIdToPrimary[$id] = $primary;
        $this->_entityPrimaryToId[$primary] = $id;
        if ($fromStore !== true) {
            $this->_entitySaveList[] = $id;
        }

        return $entity;
    }

    /**
     * @param array<string|mixed> $newData
     */
    public function update(Entity $entity, array $newData): Entity
    {
        if ($newData === $entity->read()) {
            //Nothing to do
            return $entity;
        }

        $this->_entitySaveList[] = $entity->_id;
        $oldPrimary = $entity->{$entity->getPrimary()};
        $newPrimary = $newData[$entity->getPrimary()];
        if ($oldPrimary != $newPrimary) {
            $this->_dataStore->delete(get_class($entity), $oldPrimary);
            unset($this->_entityPrimaryToId[$oldPrimary]);
            $this->_entityIdToPrimary[$entity->getId()] = $newPrimary;
            $this->_entityPrimaryToId[$newPrimary] = $entity->getId();
        }

        $entityBeforeUpdate = clone $entity;
        $entity->_data = $newData;
        $this->setEntityBeforeUpdate($entityBeforeUpdate)->setEntityAfterUpdate($entity)->notify();
        $this->clearEntity();
        return $entity;
    }

    //Delete
    public function delete(Entity $entity): void
    {
        $id = $entity->_id;
        $entity->_id = null;
        $entity->_data = null;
        $entity->_em = null;
        $this->_entities[$id] = null;
        $primary = $entity->{$entity->getPrimary()};
        $this->_dataStore->delete(get_class($entity), $primary);
        unset($this->_entityIdToPrimary[$id]);
        unset($this->_entityPrimaryToId[$primary]);
    }

    public function findByPrimary(Entity $entity, string $primary): ?Entity
    {
        if (isset($this->_entityPrimaryToId[$primary])) {
            $id = $this->_entityPrimaryToId[$primary];
            return $this->_entities[$id];
        } else {
            return null;
        }
    }

    //Update the datastore to update itself and save.

    /**
     * @throws Exception
     */
    public function updateStore(): void
    {
        foreach ($this->_entitySaveList as $id) {
            /** @var Entity $entity */
            $entity = $this->_entities[$id];
            $this->_dataStore->set(get_class($entity), $entity->{$entity->getPrimary()}, $entity->_data);
        }
        $this->_dataStore->save();
    }

    private function setEntityBeforeUpdate(Entity $entity): self
    {
        $this->entityBeforeUpdate = $entity;
        return $this;
    }

    private function setEntityAfterUpdate(Entity $entity): self
    {
        $this->entityAfterUpdate = $entity;
        return $this;
    }

    private function clearEntity(): void
    {
        $this->entityBeforeUpdate = null;
        $this->entityAfterUpdate = null;
    }
}
