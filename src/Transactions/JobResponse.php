<?php

namespace QueueClient\Transactions;

class JobResponse implements \Serializable
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
     * @var string|null
     */
    private $externalId;

    /**
     * @var bool
     */
    private $success;

    /**
     * @var string|null
     */
    private $message;

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
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success === true;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'success' => $this->success,
            'message' => $this->message,
            'externalId' => $this->externalId
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

        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        if (isset($data['provider'])) {
            $this->provider = $data['provider'];
        }
        if (isset($data['success'])) {
            $this->success = $data['success'];
        }
        if (isset($data['message'])) {
            $this->message = $data['message'];
        }
        if (isset($data['externalId'])) {
            $this->externalId = $data['externalId'];
        }
    }
}
