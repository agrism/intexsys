<?php

namespace Tests\Unit;

use Agrism\Intexsys\Entity;
use Agrism\Intexsys\EntityManager;
use Agrism\Intexsys\InventoryItem;
use Agrism\Intexsys\Observers\LowQuantityOnHandObserver;
use PHPUnit\Framework\TestCase;

class LowQuantityOnHandObserverTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testNotifyExecutedOnlyWhenQuantityDipsBellowMarginFive(array $unitMovements, int $countToCallNotifyMethod): void
    {
        $dataStorePath = "__test_data_store_file.data";
        $entityManager = new EntityManager($dataStorePath);
        Entity::setDefaultEntityManager($entityManager);

        $entityObserverNotifyMock = $this->getMockBuilder(LowQuantityOnHandObserver::class)
            ->onlyMethods(['notify'])
            ->getMock();

        $entityObserverNotifyMock->expects($this->exactly($countToCallNotifyMethod))->method('notify');

        $entityManager->attach($entityObserverNotifyMock);

        /** @var InventoryItem $item */
        $item = Entity::getEntity(InventoryItem::class,
            ['sku' => 'abc-4589', 'qoh' => 0, 'cost' => '5.67', 'salePrice' => '7.27']);

        foreach ($unitMovements as $movement){
            if($movement < 0){
                $item->itemsHaveShipped(-$movement);
                continue;
            }
            $item->itemsReceived($movement);
        }

        unlink($dataStorePath);
    }

    public function dataProvider(): array
    {
        return [
            [[6, -2], 1,], // 6>4
            [[6, -1], 0,], // 6>5
            [[6, -1, 1, -1, 1, 1], 0,], // 6>5>6>5>6>7
            [[6, -1, 1, -1, 1, 1], 0,], // 6>5>6>5>6>7
            [[1, 1, 1, 1, 1, 1, 1], 0,], // 1>2>3>4>5>6>7
            [[1, 1, 1, 1, 0, 0, 0], 0,], // 1>1>1>1>1>1>1
            [[4, 100, -100], 1,], // 4>104>4
            [[4, 100, -99], 0,], // 4>104>5
            [[4, 100, -100, 1000, -1000, 100000, -100000], 3,], // 4>104>4.1004>4>100004>4
        ];
    }
}