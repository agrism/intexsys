<?php

namespace Agrism\Intecsys\Observers;

use Agrism\Intecsys\EntityManager;
use SplObserver;
use SplSubject;
class LowQuantityOnHandObserver implements SplObserver
{
    const MIN_UNIT_QUANTITY = 5;

    private array $data = [];
    public function update(SplSubject $subject): void
    {
        /** @var EntityManager $subject */
        $data = $subject->currentEntity->read(); // TODO: agris

        $quantity = $data['qoh'];

        if($this->shouldNotify($quantity)){
            $this->setData($data)->notify();
        }
    }

    private function shouldNotify(int $quantity): bool
    {
        return $quantity < self::MIN_UNIT_QUANTITY;
    }

    private function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    private function notify(): void
    {
        $msg = 'Enity quantity is bellow 5 units, data: ' .json_encode($this->data) . PHP_EOL;
        $subject = "{$this->data['sku']} is bellow five units";

        dump('SEND EMAIL for sku, subject::'. $subject .', message: '. $msg);
        $this->senEmail('7924@inbox.lv', $subject, $msg);
    }

    private function senEmail(string $to, string $subject, string $msg): void
    {
        mail($to, $subject, $msg);
    }
}