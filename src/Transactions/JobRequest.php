<?php

namespace QueueClient\Transactions;

use QueueClient\Enum\Method;
use QueueClient\Enum\Priority;
use QueueClient\Enum\Queue;

class JobRequest
{
    /**
     * @var string|null
     */
    private $priority = Priority::NORMAL;

    /**
     * @var string|null
     */
    private $queue = Queue::DEFAULT;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $uri;

    /**
     * @var string|null
     */
    private $method = Method::GET;

    /**
     * @var array|null
     */
    private $params = [];

    /**
     * @var \DateTime|null
     */
    private $date;

    /**
     * @return string|null
     */
    public function getPriority(): ?string
    {
        return $this->priority;
    }

    /**
     * @param string|null $priority
     */
    public function setPriority(?string $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return string|null
     */
    public function getQueue(): ?string
    {
        return strtoupper($this->queue);
    }

    /**
     * @param string|null $queue
     */
    public function setQueue(?string $queue): void
    {
        $this->queue = $queue;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @param string|null $uri
     */
    public function setUri(?string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @param string|null $method
     */
    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return array|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * @param array|null $params
     */
    public function setParams(?array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime|null $date
     */
    public function setDate(?\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'method' => $this->method,
            'name' => $this->name,
            'queue' => $this->queue,
            'priority' => $this->priority,
            'date' => $this->date ? $this->date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            'params' => $this->params,
        ];
    }
}
