<?php

namespace Agrism\Intexsys\Observers;

use Agrism\Intexsys\EntityManager;
use SplObserver;
use SplSubject;

class EntityUpdateObserver implements SplObserver
{
    private string $logFilePath = 'observer_a_log.log';

    public function update(SplSubject $subject): void
    {
        /** @var EntityManager $subject */
        $this->log($subject->entityAfterUpdate->read());
    }

    /**
     * @param array<string|mixed> $data
     */
    protected function log(array $data): void
    {
        $message = 'Entity updated: ' .json_encode($data) . PHP_EOL;

        file_put_contents($this->logFilePath, $message, FILE_APPEND);
    }
}