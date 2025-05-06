<?php

namespace QueueClient\Transactions;

/**
 * @method JobRequest[] toArray()
 */
class JobRequestCollection extends AbstractCollection
{
    protected function isValid($item): bool
    {
        return $item instanceof JobRequest;
    }
}
