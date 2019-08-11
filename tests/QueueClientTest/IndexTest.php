<?php

namespace QueueClientTest;

use PHPUnit\Framework\TestCase;
use QueueClient\Client;
use QueueClient\Enum\Priority;
use QueueClient\Transactions\JobRequest;
use QueueClient\Transactions\JobResponse;

class IndexTest extends TestCase
{
    public function testIndex()
    {
        try {
            $client = new QueueClient\Client(getenv('QUEUE_BASE_URI'), getenv('QUEUE_SECRET_KEY'), time());

            $jobRequest = new JobRequest();
            $jobRequest->setUri('https://www.google.be');
            $jobRequest->setQueue('TEST');
            $jobRequest->setPriority(Priority::LOW);

            $jobResponse = $client->create($jobRequest);

            $this->assertInstanceOf(JobResponse::class, $jobResponse);
            $this->assertTrue($client->delete($jobResponse->getId()));
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }
}