<?php

namespace Tests\Unit;

use Agrism\Intexsys\Entity;
use Agrism\Intexsys\EntityManager;
use Agrism\Intexsys\InventoryItem;
use Agrism\Intexsys\Observers\EntityUpdateObserver;
use PHPUnit\Framework\TestCase;

class EntityUpdateObserverTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testCountSuccess(array $unitMovements, int $countToCallNotifyMethod): void
    {
        $dataStorePath = "__test_data_store_file.data";

        $entityManager = new EntityManager($dataStorePath);

        Entity::setDefaultEntityManager($entityManager);

        $entityObserverMock = $this->getMockBuilder(EntityUpdateObserver::class)
            ->onlyMethods(['log'])
            ->getMock();

        $entityObserverMock->expects($this->exactly($countToCallNotifyMethod))->method('log');

        $entityManager->attach($entityObserverMock);

        /** @var InventoryItem $item */
        $item = Entity::getEntity(InventoryItem::class,
            ['sku' => 'abc-4589', 'qoh' => 0, 'cost' => '5.67', 'salePrice' => '7.27']);

        $item2 = Entity::getEntity(InventoryItem::class,
            ['sku' => 'abc-4590', 'qoh' => 0, 'cost' => '5.67', 'salePrice' => '7.27']);

        foreach ($unitMovements as $movement){
            if($movement < 0){
                $item->itemsHaveShipped(-$movement);
                continue;
            }
            $item2->itemsReceived($movement);
        }
    }

    public function dataProvider(): array
    {
        return [
            [[6, -2], 2,], // 2 updates
            [[6, -1], 2,], // 2 updates
            [[6, -1, 1, -1, 1, 1], 6,], // 6 updates
            [[6, -1, 1, -1, 1, 1], 6,], // 6 updates
            [[1, 1, 1, 1, 1, 1, 1], 7,], // 7 updates
            [[1, 1, 1, 1, 0, 0, 0], 4,], // 4 updates, 3 without changes
            [[4, 100, -100], 3,], // 3 updates
            [[4, 100, -99], 3,], // 3 updates
            [[4, 100, -100, 1000, -1000, 100000, -100000], 7,], // 7 updates
        ];
    }
}