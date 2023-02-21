<?php

namespace Agrism\Intecsys;

/**
 * @property $qoh
 */
abstract class Entity
{
    static protected ?EntityManager $_defaultEntityManager = null;

    protected ?array $_data = null;

    protected ?EntityManager $_em = null;
    protected ?string $_entityName = null;
    protected ?string $_id = null;

    public function init(): void {}

    abstract public function getMembers(): array;

    abstract public function getPrimary(): string;

    //setter for properies and items in the underlying data array
    public function __set(string $variableName, mixed $value): void
    {
        if (array_key_exists($variableName, array_change_key_case($this->getMembers()))) {
            $newData = $this->_data;
            $newData[$variableName] = $value;
            $this->_update($newData);
            $this->_data = $newData;
        } else {
            if (property_exists($this, $variableName)) {
                $this->$variableName = $value;
            } else {
                throw new Exception("Set failed. Class " . get_class($this) .
                    " does not have a member named " . $variableName . ".");
            }
        }
    }

    //getter for properies and items in the underlying data array
    public function __get(mixed $variableName): mixed
    {
        if($variableName === 'qoh'){
            return $this->_data['qoh'];
        }

        if (array_key_exists($variableName, array_change_key_case($this->getMembers()))) {
            $data = $this->read();
            return $data[$variableName];
        } else {
            if (property_exists($this, $variableName)) {
                return $this->$variableName;
            } else {
                throw new Exception("Get failed. Class " . get_class($this) .
                    " does not have a member named " . $variableName . ".");
            }
        }
    }

    static public function setDefaultEntityManager(EntityManager $em): void
    {
        self::$_defaultEntityManager = $em;
    }

    //Factory function for making entities.
    static public function getEntity(string $entityName, array $data, ?EntityManager $entityManager = null): Entity
    {
        $em = $entityManager === null ? self::$_defaultEntityManager : $entityManager;
        $entity = $em->create($entityName, $data);
        $entity->init();
        return $entity;
    }

    static public function getDefaultEntityManager()
    {
        return self::$_defaultEntityManager;
    }

    public function create(string $entityName, array $data)
    {
        $entity = self::getEntity($entityName, $data);
        return $entity;
    }

    public function read(): array
    {
        return $this->_data;
    }

    public function update(array $newData): void
    {
        $this->_em->update($this, $newData);
        $this->_data = $newData;
    }

    public function delete(): void
    {
        $this->_em->delete($this);
    }
}