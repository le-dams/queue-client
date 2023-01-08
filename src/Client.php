<?php

namespace QueueClient;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
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
        $this->logger = $logger ?: new NullLogger();
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
                'Authorization' => 'Bearer '.$this->secretKey,
            ]
        ]);

        $contentJson = $request->getBody()->getContents();
        $response = \json_decode($contentJson, true);

        if (!isset($response['id']) || !$response['id']) {
            throw new QueueServerException('Invalid transaction');
        }

        return $response['id'];
    }

    /**
     * @param string $transactionId
     * @return void
     * @throws GuzzleException
     */
    public function rollbackTransaction(string $transactionId): void
    {
        $this->server->request('DELETE', 'transaction/' . $transactionId, [
            'connect_timeout' => $this->connectionTimeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Correlation-Id' => $this->correlationId,
                'Authorization' => 'Bearer '.$this->secretKey,
            ]
        ]);
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
                'Authorization' => 'Bearer '.$this->secretKey,
            ]
        ]);

        $contentJson = $request->getBody()->getContents();
        return \json_decode($contentJson, true);
    }

    /**
     * @param string $transactionId
     * @return JobResponse[]
     * @throws QueueServerException
     * @throws GuzzleException
     */
    public function getTransactionJobs(string $transactionId): array
    {
        $request = $this->server->request('GET','transaction/'.$transactionId.'/jobs', [
            'connect_timeout' => $this->connectionTimeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Correlation-Id' => $this->correlationId,
                'Secret-Key' => $this->secretKey,
            ]
        ]);

        $contentJson = $request->getBody()->getContents();
        $responses = \json_decode($contentJson, true);

        $jobResponses = [];
        foreach ($responses as $response) {
            $jobResponse = new JobResponse();
            $jobResponse->unserialize($response);

            $jobResponses[] = $jobResponse;
        }

        return $jobResponses;
    }

    /**
     * @param string $transactionId
     * @return void
     */
    public function closeTransaction(string $transactionId): void
    {
        $this->server->request('PUT', 'transaction/' . $transactionId, [
            'connect_timeout' => $this->connectionTimeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Correlation-Id' => $this->correlationId,
                'Authorization' => 'Bearer '.$this->secretKey,
            ]
        ]);
    }

    /**
     * @param JobRequest $jobRequest
     * @param string|null $transactionId
     * @return JobResponse
     * @throws GuzzleException
     * @throws QueueServerException
     */
    public function createJob(JobRequest $jobRequest, ?string $transactionId = null): JobResponse
    {
        try {
            if (null === $transactionId && true === $this->autoStartTransaction) {
                $transactionId = $this->startTransaction();
            }

            $request = $this->server->request('POST','job', [
                'connect_timeout' => $this->connectionTimeout,
                'body' => \json_encode($jobRequest->serialize()),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Authorization' => 'Bearer '.$this->secretKey,
                    'Transaction-Id' => $transactionId,
                ]
            ]);

            if (true === $this->autoStartTransaction) {
                $this->closeTransaction($transactionId);
            }

            $contentJson = $request->getBody()->getContents();
            $response = \json_decode($contentJson, true);

            $jobResponse = new JobResponse();
            $jobResponse->unserialize($response);

            return $jobResponse;
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            throw new QueueServerException('Queue server return an error: '.$e->getMessage(), -1, $e);
        }
    }

    public function ping(): bool
    {
        try {
            $signature = uniqid();
            $response = $this->server->request('GET', 'ping', [
                'connect_timeout' => $this->connectionTimeout,
                'headers' => [
                    'Correlation-Id' => $this->correlationId,
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'X-Ping-Signature' => $signature
                ]
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 300) {
                $this->logger->warning('Invalid server Status Code, [' . $response->getStatusCode() . ']');
                return false;
            }

            $content = $response->getBody()->getContents();
            $json = \json_decode($content, true);
            if (null === $json) {
                $this->logger->warning('Not adequate response from Server');
                return false;
            }

            if (false === isset($json['success'])) {
                $this->logger->warning('Field [success] is missing');
                return false;
            } else if ($json['success'] !== true) {
                $this->logger->warning('Field [success] is wrong');
                return false;
            } else if (false === isset($json['signature'])) {
                $this->logger->warning('Field [signature] is missing');
                return false;
            } else if ($json['signature'] !== $signature) {
                $this->logger->warning('Field [signature] is wrong');
                return false;
            }

            return true;
        } catch (ClientException $exception) {
            $this->logger->warning($exception);
            return false;
        } catch (ServerException $exception) {
            $this->logger->error($exception);
            return false;
        } catch (\Exception $e) {
            $this->logger->alert($e);
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
    public function createJobs(array $jobRequests, ?string $transactionId = null): array
    {
        try {
            $data = [];
            foreach ($jobRequests as $jobRequest) {
                $data[] = $jobRequest->serialize();
            }

            if (null === $transactionId && true === $this->autoStartTransaction) {
                $transactionId = $this->startTransaction();
            }

            $request = $this->server->request('POST','jobs', [
                'connect_timeout' => $this->connectionTimeout,
                'body' => \json_encode($data),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Authorization' => 'Bearer '.$this->secretKey,
                    'Transaction-Id' => $transactionId,
                ]
            ]);

            if (true === $this->autoStartTransaction) {
                $this->closeTransaction($transactionId);
            }

            $contentJson = $request->getBody()->getContents();
            $responses = \json_decode($contentJson, true);

            $jobResponses = [];
            foreach ($responses as $response) {
                $jobResponse = new JobResponse();
                $jobResponse->unserialize($response);

                $jobResponses[] = $jobResponse;
            }

            return $jobResponses;
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            if (null !== $transactionId && true === $this->autoStartTransaction) {
                $this->rollbackTransaction($transactionId);
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
                    'Authorization' => 'Bearer '.$this->secretKey,
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
                    'Authorization' => 'Bearer '.$this->secretKey,
                ]
            ]);

            return \json_decode($request->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $this->logger->error($e);
            throw new QueueServerException('Queue server return an error', -1, $e);
        }
    }
}
