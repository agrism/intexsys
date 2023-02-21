<?php

namespace Agrism\Intecsys\Observers;

use Agrism\Intecsys\EntityManager;
use SplObserver;
use SplSubject;

class EntityUpdateObserver implements SplObserver
{
    public function update(SplSubject $subject): void
    {
        /** @var EntityManager $subject */
        $this->log($subject->currentEntity->read());
    }

    protected function log(array $data): void
    {
        $message = 'Enity updated: ' .json_encode($data) . PHP_EOL;

        file_put_contents('observer_a_log.log', $message, FILE_APPEND);
    }
}