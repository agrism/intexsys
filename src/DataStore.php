<?php

namespace Agrism\Intexsys;

use Exception;

//A super-simple replacement class for a real database, just so we have a place for storing results.
class DataStore
{
    protected ?string $_storePath = null;
    /**
     * @var array<string,array<string,array<string|mixed>>>|null
     */
    protected ?array $_dataStore = null;

    /**
     * @throws Exception
     */
    public function __construct(string $storePath)
    {
        $this->_storePath = $storePath;
        if (!file_exists($storePath)) {
            if (!touch($storePath)) {
                throw new Exception("Could not create data store file $storePath. Details:" . getLastError());
            }
            if (!chmod($storePath, 0777)) {
                throw new Exception("Could not set read/write on data store file $storePath. " .
                    "Details:" . getLastError());
            }
        }
        if (!is_readable($storePath) || !is_writable($storePath)) {
            throw new Exception("Data store file $storePath must be readable/writable. Details:" . getlastError());
        }
        $rawData = file_get_contents($storePath);

        if ($rawData === false) {
            throw new Exception("Read of data store file $storePath failed.  Details:" . getLastError());
        }

        if (strlen($rawData) > 0) {
            $this->_dataStore = unserialize($rawData);
        } else {
            $this->_dataStore = null;
        }
    }

    //update the store with information

    /**
     * @param array<string|mixed> $data
     */
    public function set(string $item, string $primary, array $data): void
    {
        $this->_dataStore[$item][$primary] = $data;
    }

    //get information

    /**
     * @return array<string|mixed>|null
     */
    public function get(string $item, string $primary): ?array
    {
        return $this->_dataStore[$item][$primary] ?? null;
    }

    //delete an item.
    public function delete(string $item, string $primary): void
    {
        if (isset($this->_dataStore[$item][$primary])) {
            unset($this->_dataStore[$item][$primary]);
        }
    }

    //save everything

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $result = file_put_contents($this->_storePath, serialize($this->_dataStore));
        if ($result == null) {
            throw new Exception("Write of data store file $this->_storePath failed.  Details:" . getLastError());
        }
    }

    //Which types of items do we have stored

    /**
     * @return array<int,string>
     */
    public function getItemTypes(): array
    {
        if (is_null($this->_dataStore)) {
            return [];
        }
        return array_keys($this->_dataStore);
    }

    //get keys for an item-type, so we can loop over.

    /**
     * @return array<int,string>
     */
    public function getItemKeys(string $itemType): array
    {
        return array_keys($this->_dataStore[$itemType]);
    }
}
