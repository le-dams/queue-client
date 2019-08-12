<?php

namespace QueueClientTest;

use PHPUnit\Framework\TestCase;
use QueueClient\Client;
use QueueClient\Enum\Priority;
use QueueClient\Transactions\JobRequest;
use QueueClient\Transactions\JobResponse;

class IndexTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;

    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->client = new Client(getenv('QUEUE_BASE_URI'), getenv('QUEUE_SECRET_KEY'), time());
    }

    /**
     * @return JobResponse
     * @throws \Exception
     */
    public function testCreateJob()
    {
        try {
            $jobRequest = new JobRequest();
            $jobRequest->setUri('https://www.google.be');
            $jobRequest->setQueue('TEST');
            $jobRequest->setPriority(Priority::LOW);

            $jobResponse = $this->client->createJob($jobRequest);

            $this->assertInstanceOf(JobResponse::class, $jobResponse);

            return $jobResponse;
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param JobResponse $jobResponse
     * @throws \Exception
     * @depends testCreateJob
     */
    public function testDeleteJob(JobResponse $jobResponse)
    {
        $this->assertTrue($this->client->deleteJob($jobResponse->getId()));
    }

    /**
     * @return array|JobResponse[]
     * @throws \Exception
     */
    public function testCreateJobs()
    {
        try {
            $jobRequests = [];
            foreach (range(1, 10) as $i):
                $jobRequest = new JobRequest();
                $jobRequest->setUri('https://www.google.be');
                $jobRequest->setQueue('TEST');
                $jobRequest->setExternalId(uniqid());
                $jobRequest->setPriority(Priority::LOW);

                $jobRequests[] = $jobRequest;
            endforeach;

            $jobResponses = $this->client->createJobs($jobRequests);

            $this->assertInternalType('array', $jobResponses);

            return $jobResponses;
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param array $jobResponses
     * @throws \QueueClient\Exception\QueueServerException
     * @depends testCreateJobs
     */
    public function testDeleteJobs(array $jobResponses)
    {
        foreach ($jobResponses as $jobResponse) {
            $this->assertTrue($this->client->deleteJob($jobResponse->getId()));
        }
    }
}