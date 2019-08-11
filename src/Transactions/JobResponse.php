<?php

namespace QueueClient\Transactions;

class JobResponse
{

    /**
     * @var string|null
     */
    private $provider;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @return string|null
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * @param string|null $provider
     */
    public function setProvider(?string $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
        ];
    }
}
