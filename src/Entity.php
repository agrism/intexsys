<?php

namespace Agrism\Intexsys;

use Exception;

/**
 * @property int $qoh
 */
abstract class Entity
{
    static protected ?EntityManager $_defaultEntityManager = null;

    /**
     * @var array<string|mixed>|null
     */
    protected ?array $_data = null;

    protected ?EntityManager $_em = null;
    protected ?string $_entityName = null;
    protected ?int $_id = null;
    /**
     * @return array<string|mixed>
     */
    abstract public function getMembers(): array;

    abstract public function getPrimary(): string;

    //setter for properties and items in the underlying data array

    /**
     * @throws Exception
     */
    public function __set(string $variableName, mixed $value): void
    {
        if (array_key_exists($variableName, array_change_key_case($this->getMembers()))) {
            $newData = $this->_data;
            $newData[$variableName] = $value;
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

    //getter for properties and items in the underlying data array

    /**
     * @throws Exception
     */
    public function __get(mixed $variableName): mixed
    {
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

    /**
     * @param array<string|mixed> $data
     */
    static public function getEntity(string $entityName, array $data, ?EntityManager $entityManager = null): Entity
    {
        $em = $entityManager === null ? self::$_defaultEntityManager : $entityManager;
        return $em->create($entityName, $data);
    }

    static public function getDefaultEntityManager(): ?EntityManager
    {
        return self::$_defaultEntityManager;
    }

    public function getId() : ?int
    {
        return  $this->_id;
    }

    /**
     * @param array<string|mixed> $data
     */
    public function create(string $entityName, array $data): Entity
    {
        return self::getEntity($entityName, $data);
    }

    /**
     * @return  array<string|mixed>
     */
    public function read(): array
    {
        return $this->_data;
    }

    /**
     * @param array<string|mixed> $newData
     */
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