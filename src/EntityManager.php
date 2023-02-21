<?php

namespace Agrism\Intecsys;

use SplSubject;
use SplObserver;

//This class managed in-memory entities and commmunicates with the storage class (DataStore in our case).
class EntityManager implements SplSubject
{
    protected $_entities = [];

    protected $_entityIdToPrimary = [];

    protected $_entityPrimaryToId = [];

    protected $_entitySaveList = [];

    protected $_nextId = null;

    protected $_dataStore = null;

    public ?Entity $currentEntity = null;

    public function __construct($storePath)
    {
        $this->_dataStore = new DataStore($storePath);

        $this->_nextId = 1;

        $itemTypes = $this->_dataStore->getItemTypes();

        foreach ($itemTypes as $itemType)
        {
            $itemKeys = $this->_dataStore->getItemKeys($itemType);
            foreach ($itemKeys as $itemKey) {
                $this->_entities[] = $this->create($itemType, $this->_dataStore->get($itemType, $itemKey), true);
            }
        }
    }

    /**
     * @var SplObserver[]
     */
    private array $obesrvers = [];
    public function attach(SplObserver $observer): void
    {
        $this->obesrvers[serialize($observer)] = $observer;
    }

    public function detach(SplObserver $observer): void
    {
        unset($this->obesrvers[serialize($observer)]);
    }

    public function notify(): void
    {
        foreach ($this->obesrvers as $obesrver){
            /** @var SplObserver $obesrver */
            $obesrver->update($this);
        }
    }

    //create an entity
    public function create(string $entityName,array $data, bool $fromStore = false)
    {
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

    //update
    public function update(Entity $entity, array $newData)
    {
        if ($newData === $entity->_data) {
            //Nothing to do
            return $entity;
        }

        $this->_entitySaveList[] = $entity->_id;
        $oldPrimary = $entity->{$entity->getPrimary()};
        $newPrimary = $newData[$entity->getPrimary()];
        if ($oldPrimary != $newPrimary)
        {
            $this->_dataStore->delete(get_class($entity),$oldPrimary);
            unset($this->_entityPrimaryToId[$oldPrimary]);
            $this->_entityIdToPrimary[$entity->$id] = $newPrimary;
            $this->_entityPrimaryToId[$newPrimary] = $entity->$id;
        }
        $entity->_data = $newData;
        $this->setCurrentEntity($entity)->notify();
        $this->clearCurrentEntitry();
        return $entity;
    }

    //Delete
    public function delete(Entity $entity)
    {
        $id = $entity->_id;
        $entity->_id = null;
        $entity->_data = null;
        $entity->_em = null;
        $this->_entities[$id] = null;
        $primary = $entity->{$entity->getPrimary()};
        $this->_dataStore->delete(get_class($entity),$primary);
        unset($this->_entityIdToPrimary[$id]);
        unset($this->_entityPrimaryToId[$primary]);
        return null;
    }

    public function findByPrimary(Entity $entity, $primary)
    {
        if (isset($this->_entityPrimaryToId[$primary])) {
            $id = $this->_entityPrimaryToId[$primary];
            return $this->_entities[$id];
        } else {
            return null;
        }
    }

    //Update the datastore to update itself and save.
    public function updateStore() {
        foreach($this->_entitySaveList as $id) {
            $entity = $this->_entities[$id];
            $this->_dataStore->set(get_class($entity),$entity->{$entity->getPrimary()},$entity->_data);
        }
        $this->_dataStore->save();
    }

    private function setCurrentEntity(Entity $entity): self
    {
        $this->currentEntity = $entity;
        return $this;
    }

    private function clearCurrentEntitry(): void
    {
        $this->currentEntity = null;
    }
}
