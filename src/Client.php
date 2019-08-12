<?php

namespace QueueClient;

use QueueClient\Transactions\JobResponse;
use QueueClient\Transactions\JobRequest;
use QueueClient\Exception\QueueServerException;

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
     * Client constructor.
     * @param string $baseUri
     * @param string $secretKey
     * @param string|null $correlationId
     */
    public function __construct(string $baseUri, string $secretKey, string $correlationId  = null)
    {
        $this->secretKey = $secretKey;
        $this->correlationId = $correlationId;
        $this->server = new \GuzzleHttp\Client([
            'base_uri' => $baseUri,
        ]);
    }

    /**
     * @param JobRequest $jobRequest
     * @return JobResponse
     * @throws QueueServerException
     */
    public function createJob(JobRequest $jobRequest): JobResponse
    {
        try {
            $request = $this->server->request('POST','/queue/server/public/index.php/job', [
                'body' => \json_encode($jobRequest->serialize()),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            $contentJson = $request->getBody()->getContents();
            $response = \json_decode($contentJson, JSON_OBJECT_AS_ARRAY);

            $jobResponse = new JobResponse();
            $jobResponse->unserialize($response);

            return $jobResponse;
        } catch (\GuzzleHttp\Exception\GuzzleException | \Exception $e) {
            throw new QueueServerException('Queue server return an error', -1, $e);
        }
    }

    /**
     * @param JobRequest[] $jobRequests
     * @return JobResponse[]
     * @throws QueueServerException
     */
    public function createJobs(array $jobRequests): array
    {
        try {
            $data = [];
            foreach ($jobRequests as $jobRequest) {
                $data[] = $jobRequest->serialize();
            }
            $request = $this->server->request('POST','/queue/server/public/index.php/jobs', [
                'body' => \json_encode($data),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            $contentJson = $request->getBody()->getContents();
            $responses = \json_decode($contentJson, JSON_OBJECT_AS_ARRAY);

            $jobResponses = [];
            foreach ($responses as $response) {
                $jobResponse = new JobResponse();
                $jobResponse->unserialize($response);

                $jobResponses[] = $jobResponse;
            }

            return $jobResponses;
        } catch (\GuzzleHttp\Exception\GuzzleException | \Exception $e) {
            throw new QueueServerException('Queue server return an error', -1, $e);
        }
    }

    /**
     * @param int $idJob
     * @return bool
     * @throws QueueServerException
     */
    /**
     * @param int $idJob
     * @return bool
     * @throws QueueServerException
     */
    public function deleteJob(int $idJob): bool
    {
        try {
            $request = $this->server->request('DELETE','/queue/server/public/index.php/job/'.$idJob, [
                'headers' => [
                    'Correlation-Id' => $this->correlationId,
                    'Secret-Key' => $this->secretKey,
                ]
            ]);

            return $request->getStatusCode() === 200;
        } catch (\GuzzleHttp\Exception\GuzzleException | \Exception $e) {
            throw new QueueServerException('Queue server return an error', -1, $e);
        }
    }
}
