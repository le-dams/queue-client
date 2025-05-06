<?php

namespace QueueClient\Transactions;

/**
 * @method JobResponse[] toArray()
 */
class JobResponseCollection extends AbstractCollection
{
    protected function isValid($item): bool
    {
        return $item instanceof JobResponse;
    }
}
