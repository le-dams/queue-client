<?php

namespace QueueClient\Transactions;

abstract class AbstractCollection implements \Countable
{
    private array $items = [];

    abstract protected function isValid($item): bool;

    public function __construct(array $items)
    {
        $this->items = [];
        foreach ($items as $item) {
            if ($this->isValid($item)) {
                $this->items[] = $item;
            }
        }
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
