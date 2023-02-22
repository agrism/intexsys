<?php

namespace Agrism\Intexsys\Observers;

use Agrism\Intexsys\EntityManager;
use SplObserver;
use SplSubject;
class LowQuantityOnHandObserver implements SplObserver
{
    const MARGIN_UNIT_QUANTITY = 5;
    /**
     * @var array<string, float|int|string>
     */
    private array $dataBeforeUpdate = [];
    /**
     * @var array<string, float|int|string>
     */
    private array $dataAfterUpdate = [];
    public function update(SplSubject $subject): void
    {
        /** @var EntityManager $subject */
        $dataBeforeUpdate = $subject->entityBeforeUpdate?->read();
        $dataAfterUpdate = $subject->entityAfterUpdate?->read();

        $quantityBeforeUpdate = $dataBeforeUpdate['qoh'] ?? 0;
        $quantityAfterUpdate = $dataAfterUpdate['qoh'] ?? 0;

        if($this->shouldNotify($quantityBeforeUpdate, $quantityAfterUpdate)){
            $this->setData($dataBeforeUpdate ?? [], $dataAfterUpdate ?? [])->notify();
        }
    }

    private function shouldNotify(int $quantityBeforeUpdate, int $quantityAfterUpdate): bool
    {
        return $quantityBeforeUpdate >= self::MARGIN_UNIT_QUANTITY
            && $quantityAfterUpdate < self::MARGIN_UNIT_QUANTITY;
    }

    /**
     * @param array<string, mixed> $dataBeforeUpdate
     * @param array<string, mixed> $dataAfterUpdate
     */
    private function setData(array $dataBeforeUpdate, array $dataAfterUpdate): self
    {
        $this->dataBeforeUpdate = $dataBeforeUpdate;
        $this->dataAfterUpdate = $dataAfterUpdate;
        return $this;
    }

    protected function notify(): void
    {
        $msg = "Entity quantity dips bellow 5 units, data: from {$this->dataBeforeUpdate['qoh']} to {$this->dataAfterUpdate['qoh']}";

        $subject = "{$this->dataAfterUpdate['sku']} is bellow five units";

        dump('SEND EMAIL for sku, subject::'. $subject .', message: '. $msg);
        $this->senEmail($subject, $msg);
    }

    private function senEmail(string $subject, string $msg): void
    {
        mail('7924@inbox.lv', $subject, $msg);
    }
}