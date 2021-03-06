<?php
namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

abstract class ExportMessageProcessorAbstract implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var LoggerInterface
     */
    protected $jobStorage;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var TokenSerializerInterface
     */
    protected $tokenSerializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param JobRunner $jobRunner
     * @param JobStorage $jobStorage
     * @param TokenStorageInterface $tokenStorage
     * @param TokenSerializerInterface $tokenSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobRunner $jobRunner,
        JobStorage $jobStorage,
        TokenStorageInterface $tokenStorage,
        TokenSerializerInterface $tokenSerializer,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->jobStorage = $jobStorage;
        $this->tokenStorage = $tokenStorage;
        $this->tokenSerializer = $tokenSerializer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (! ($body = $this->getMessageBody($message))) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            $this->getRunDelayedJobCallback($body)
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param array $body
     *
     * @return \Closure
     */
    protected function getRunDelayedJobCallback(array $body)
    {
        return function (JobRunner $jobRunner, Job $job) use ($body) {
            $exportResult = $this->handleExport($body);

            $this->logger->info(sprintf(
                'Export result. Success: %s. ReadsCount: %s. ErrorsCount: %s',
                $exportResult['success'] ? 'Yes' : 'No',
                $exportResult['readsCount'],
                $exportResult['errorsCount']
            ));

            $this->saveJobResult($job, $exportResult);

            return $exportResult['success'];
        };
    }

    /**
     * @param string $serializedToken
     *
     * @return bool
     */
    protected function setSecurityToken($serializedToken)
    {
        $token = $this->tokenSerializer->deserialize($serializedToken);

        if (null === $token) {
            return false;
        }

        $this->tokenStorage->setToken($token);

        return true;
    }

    /**
     * @param Job $job
     * @param array $data
     */
    protected function saveJobResult(Job $job, array $data)
    {
        $this->jobStorage->saveJob($job, function (Job $job) use ($data) {
            $job->setData($data);
        });
    }

    /**
     * @param array $body
     *
     * @return array
     */
    abstract protected function handleExport(array $body);

    /**
     * @param MessageInterface $message
     *
     * @return array|bool
     */
    abstract protected function getMessageBody(MessageInterface $message);
}
