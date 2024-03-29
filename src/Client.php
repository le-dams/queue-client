<?php

namespace QueueClient;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QueueClient\Transactions\JobResponse;
use QueueClient\Transactions\JobRequest;
use QueueClient\Exception\QueueServerException;
use \GuzzleHttp\Exception\GuzzleException;
use \Exception;

class Client
{
    /**
     * @var Client
     */
    private $server;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var string
     */
    private $correlationId;

    /**
     * @var bool
     */
    private $autoStartTransaction = true;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $connectionTimeout = 0;

    /**
     * Client constructor.
     * @param string $baseUri
     * @param string $secretKey
     * @param string|null $correlationId
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $baseUri, string $secretKey, string $correlationId  = null, LoggerInterface $logger = null)
    {
        $this->logger = $logger ? $logger : new NullLogger();
        $this->secretKey = $secretKey;
        $this->correlationId = $correlationId;
        $this->server = new \GuzzleHttp\Client([
            'base_uri' => $baseUri.(substr($baseUri, -1)!=='/' ? '/' : null),
        ]);
    }

    /**
     * @param bool $autoStartTransaction
     */
    public function setAutoStartTransaction(bool $autoStartTransaction): void
    {
        $this->autoStartTransaction = $autoStartTransaction;
    }

    /**
     * @param int $connectionTimeout
     */
    public function setConnectionTimeout(int $connectionTimeout): void
    {
        if ($connectionTimeout < 0) {
            throw new \InvalidArgumentException('Connection timeout must be bigger than zero');
        }
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * @return string
     * @throws QueueServerException
     * @throws GuzzleException
     */
    public function startTransaction(): string
    {
        $request = $this->server->request('POST','transaction', [
            'connect_timeout' => $this->connectionTimeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Correlation-Id' => $this->correlationId,
                'Secret-Key' => $this->secretKey,
            ]
        ]);

        $contentJson = $request->getBody()->getContents();
        $response = \json_decode($contentJson, JSON_OBJECT_AS_ARRAY);

        if (!isset($response['id']) || !$response['id']) {
            throw new QueueServerException('Invalid transaction');
        }

        $this->transactionId = $response['id'];

        return $this->transactionId;
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function rollbackTransaction(): void
    {
        if ($this->transactionId) {
            $this->server->request('DELETE', 'transaction/' . $this->transactionId, [
                'connect_timeout' => $this->connectionTimeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            $this->transactionId = null;
        }
    }

    /**
     * @param string $transactionId
     * @return
     * @throws QueueServerException
     * @throws GuzzleException
     */
    public function getTransactionInfo(string $transactionId): array
    {
        $request = $this->server->request('GET','transaction/'.$transactionId, [
            'connect_timeout' => $this->connectionTimeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Correlation-Id' => $this->correlationId,
                'Secret-Key' => $this->secretKey,
            ]
        ]);

        $contentJson = $request->getBody()->getContents();
        return \json_decode($contentJson, JSON_OBJECT_AS_ARRAY);
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function closeTransaction(): void
    {
        if ($this->transactionId) {
            $this->server->request('PUT', 'transaction/' . $this->transactionId, [
                'connect_timeout' => $this->connectionTimeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            $this->transactionId = null;
        }
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function deleteTransaction(): void
    {
        if ($this->transactionId) {
            $this->server->request('DELETE', 'transaction/' . $this->transactionId, [
                'connect_timeout' => $this->connectionTimeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            $this->transactionId = null;
        }
    }

    /**
     * @param JobRequest $jobRequest
     * @return JobResponse
     * @throws QueueServerException
     * @throws GuzzleException
     * @throws Exception
     */
    public function createJob(JobRequest $jobRequest): JobResponse
    {
        try {
            if ($this->autoStartTransaction === true) {
                $this->startTransaction();
            }

            $request = $this->server->request('POST','job', [
                'connect_timeout' => $this->connectionTimeout,
                'body' => \json_encode($jobRequest->serialize()),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                    'Transaction-Id' => $this->transactionId,
                ]
            ]);

            if ($this->autoStartTransaction === true) {
                $this->closeTransaction();
            }

            $contentJson = $request->getBody()->getContents();
            $response = \json_decode($contentJson, JSON_OBJECT_AS_ARRAY);

            $jobResponse = new JobResponse();
            $jobResponse->unserialize($response);

            return $jobResponse;
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            if ($this->autoStartTransaction === true) {
                $this->rollbackTransaction();
            }
            throw new QueueServerException('Queue server return an error: '.$e->getMessage(), -1, $e);
        }
    }

    public function ping(): bool
    {
        try {
            $request = $this->server->request('GET','', [
                'connect_timeout' => $this->connectionTimeout,
                'headers' => [
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            if ($request->getStatusCode() < 200 || $request->getStatusCode() > 300) {
                $this->logger->warning('Invalid server Status Code, ['.$request->getStatusCode().']');
                return false;
            }

            $content = $request->getBody()->getContents();
            $json = \json_decode($content, true);
            if (null === $json) {
                $this->logger->warning('Not adequate response from Server');
                return false;
            }

            return isset($json['success']) && $json['success'] === true;
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * @param array $jobRequests
     * @return array
     * @throws QueueServerException
     * @throws GuzzleException
     * @throws Exception
     */
    public function createJobs(array $jobRequests): array
    {
        try {
            $data = [];
            foreach ($jobRequests as $jobRequest) {
                $data[] = $jobRequest->serialize();
            }

            if ($this->autoStartTransaction === true) {
                $this->startTransaction();
            }

            $request = $this->server->request('POST','jobs', [
                'connect_timeout' => $this->connectionTimeout,
                'body' => \json_encode($data),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                    'Transaction-Id' => $this->transactionId,
                ]
            ]);

            if ($this->autoStartTransaction === true) {
                $this->closeTransaction();
            }

            $contentJson = $request->getBody()->getContents();
            $responses = \json_decode($contentJson, JSON_OBJECT_AS_ARRAY);

            $jobResponses = [];
            foreach ($responses as $response) {
                $jobResponse = new JobResponse();
                $jobResponse->unserialize($response);

                $jobResponses[] = $jobResponse;
            }

            return $jobResponses;
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            if ($this->autoStartTransaction === true) {
                $this->rollbackTransaction();
            }
            throw new QueueServerException('Queue server return an error: '.$e->getMessage(), -1, $e);
        }
    }

    /**
     * @param int $idJob
     * @return bool
     * @throws QueueServerException
     */
    public function deleteJob(int $idJob): bool
    {
        try {
            $request = $this->server->request('DELETE','job/'.$idJob, [
                'connect_timeout' => $this->connectionTimeout,
                'headers' => [
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            return $request->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            throw new QueueServerException('Queue server return an error', -1, $e);
        }
    }

    /**
     * @param int $idJob
     * @return array|null
     * @throws QueueServerException
     */
    public function getJob(int $idJob): ?array
    {
        try {
            $request = $this->server->request('GET','job/'.$idJob, [
                'connect_timeout' => $this->connectionTimeout,
                'headers' => [
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            return \json_decode($request->getBody()->getContents(), JSON_OBJECT_AS_ARRAY);
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            throw new QueueServerException('Queue server return an error', -1, $e);
        }
    }
}
