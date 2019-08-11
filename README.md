le-dams / QueueClient
========================

Create JOB
----------

```php
$client = new QueueClient\Client(getenv('QUEUE_BASE_URI'), getenv('QUEUE_SECRET_KEY'), time());

$jobRequest = new QueueClient\Transactions\JobRequest();
$jobRequest->setUri('https://www.google.be');
$jobRequest->setQueue('TEST');
$jobRequest->setPriority(QueueClient\Enum\Priority::LOW);

$client->create($jobRequest);
```

Delete JOB
----------

```php
$client = new QueueClient\Client(getenv('QUEUE_BASE_URI'), getenv('QUEUE_SECRET_KEY'), time());

$client->delete(1);
```