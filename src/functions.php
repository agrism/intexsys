<?php

use Agrism\Intecsys\Entity;
use Agrism\Intecsys\EntityManager;
use Agrism\Intecsys\InventoryItem;
use Agrism\Intecsys\Observers\EntityUpdateObserver;
use Agrism\Intecsys\Observers\LowQuantityOnHandObserver;

//Helper function for printing out error information
function getLastError()
{
    $errorInfo = error_get_last();
    $errorString = " Error type {$errorInfo['type']}, {$errorInfo['message']} on line {$errorInfo['line']} of " .
        "{$errorInfo['file']}. ";
    return $errorString;
}


function driver()
{
    $dataStorePath = "data_store_file.data";
    $entityManager = new EntityManager($dataStorePath);

    Entity::setDefaultEntityManager($entityManager);
    //create five new Inventory items

    $obesrverA = new EntityUpdateObserver;
    $obesrverB = new LowQuantityOnHandObserver;

    $entityManager->attach($obesrverA);
    $entityManager->attach($obesrverB);

    /** @var InventoryItem $item1 */
    $item1 = Entity::getEntity(InventoryItem::class,
        ['sku' => 'abc-4589', 'qoh' => 0, 'cost' => '5.67', 'salePrice' => '7.27']);
    /** @var InventoryItem $item2 */
    $item2 = Entity::getEntity(InventoryItem::class,
        ['sku' => 'hjg-3821', 'qoh' => 0, 'cost' => '7.89', 'salePrice' => '12.00']);
    /** @var InventoryItem $item3 */
    $item3 = Entity::getEntity(InventoryItem::class,
        ['sku' => 'xrf-3827', 'qoh' => 0, 'cost' => '15.27', 'salePrice' => '19.99']);
    /** @var InventoryItem $item4 */
    $item4 = Entity::getEntity(InventoryItem::class,
        ['sku' => 'eer-4521', 'qoh' => 0, 'cost' => '8.45', 'salePrice' => '1.03']);
    /** @var InventoryItem $item5 */
    $item5 = Entity::getEntity(InventoryItem::class,
        ['sku' => 'qws-6783', 'qoh' => 0, 'cost' => '3.00', 'salePrice' => '4.97']);


    $item1->itemsReceived(4);
    $item2->itemsReceived(2);
    $item3->itemsReceived(12);
    $item4->itemsReceived(20);
    $item5->itemsReceived(1);
    $item3->itemsHaveShipped(5);
    $item4->itemsHaveShipped(16);

    $item4->changeSalePrice(0.87);

    $entityManager->updateStore();
}

function dump($args): void
{
    echo '<pre style="background-color: greenyellow">';
    print_r($args);
    echo '</pre>';
}
