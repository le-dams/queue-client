<?php

namespace QueueClient\Transactions;

use QueueClient\Enum\Method;
use QueueClient\Enum\Priority;
use QueueClient\Enum\Queue;

class JobRequest implements \Serializable
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
     * @var array|null
     */
    private $headers = [];

    /**
     * @var \DateTime|null
     */
    private $date;

    /**
     * @var string|null
     */
    private $externalId;

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
     * @return array|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * @param array|null $headers
     */
    public function setHeaders(?array $headers): void
    {
        $this->headers = $headers;
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
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @param string|null $externalId
     */
    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'uri' => $this->uri,
            'method' => $this->method,
            'name' => $this->name,
            'queue' => $this->queue,
            'priority' => $this->priority,
            'date' => $this->date ? $this->date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            'params' => $this->params,
            'headers' => $this->headers,
            'externalId' => $this->externalId,
        ];
    }

    /**
     * @param array|string $serialized
     * @return array|void
     * @throws \Exception
     */
    public function unserialize($serialized)
    {
        if (is_string($serialized)) {
            $data = \json_decode($serialized);
        } else {
            $data = $serialized;
        }

        if (isset($data['uri'])) {
            $this->uri = $data['uri'];
        }
        if (isset($data['method'])) {
            $this->method = $data['method'];
        }
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        if (isset($data['queue'])) {
            $this->queue = $data['queue'];
        }
        if (isset($data['priority'])) {
            $this->priority = $data['priority'];
        }
        if (isset($data['params'])) {
            $this->params = $data['params'];
        }
        if (isset($data['headers'])) {
            $this->headers = $data['headers'];
        }
        if (isset($data['date'])) {
            $this->date = new \DateTime($data['date']);
        }
        if (isset($data['externalId'])) {
            $this->externalId = $data['externalId'];
        }
    }
}
