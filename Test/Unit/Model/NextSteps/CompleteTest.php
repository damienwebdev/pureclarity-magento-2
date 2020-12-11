<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\NextSteps;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\NextSteps\Complete;
use PureClarity\Api\NextSteps\Complete as ApiComplete;
use PureClarity\Api\NextSteps\CompleteFactory;
use Psr\Log\LoggerInterface;

/**
 * Class CompleteTest
 *
 * Tests the methods in \Pureclarity\Core\Model\NextSteps\Complete
 */
class CompleteTest extends TestCase
{
    const ACCESS_KEY = 'AccessKey1234';
    const REGION_ID = 1;
    const STORE_ID = 17;

    /** @var Complete $object */
    private $object;

    /** @var MockObject|CompleteFactory $completeFactory */
    private $completeFactory;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    /** @var MockObject|ApiComplete $complete */
    private $complete;

    /** @var MockObject|LoggerInterface $logger*/
    private $logger;

    protected function setUp()
    {
        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->complete = $this->getMockBuilder(ApiComplete::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->completeFactory = $this->getMockBuilder(CompleteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Complete(
            $this->coreConfig,
            $this->logger,
            $this->completeFactory
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Complete::class, $this->object);
    }

    /**
     * Tests that markNextStepComplete handles an API call correctly
     */
    public function testRequest()
    {
        $this->coreConfig->expects(self::once())
            ->method('getAccessKey')
            ->with(self::STORE_ID)
            ->willReturn(self::ACCESS_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getRegion')
            ->with(self::STORE_ID)
            ->willReturn(self::REGION_ID);

        $this->completeFactory->method('create')
            ->with([
                'accessKey' => self::ACCESS_KEY,
                'nextStepId' => 'next-step-id-17',
                'region' => self::REGION_ID
            ])
            ->willReturn($this->complete);

        $this->complete->expects(self::once())
            ->method('request');

        $this->object->markNextStepComplete(self::STORE_ID, 'next-step-id-17');
    }

    /**
     * Tests that markNextStepComplete handles an Exception correctly
     */
    public function testRequestException()
    {
        $this->coreConfig->expects(self::once())
            ->method('getAccessKey')
            ->with(self::STORE_ID)
            ->willReturn(self::ACCESS_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getRegion')
            ->with(self::STORE_ID)
            ->willReturn(self::REGION_ID);

        $this->complete->expects(self::once())
            ->method('request')
            ->willThrowException(new \Exception('Some error'));

        $this->completeFactory->method('create')
            ->with([
                'accessKey' => self::ACCESS_KEY,
                'nextStepId' => 'next-step-id-17',
                'region' => self::REGION_ID
            ])
            ->willReturn($this->complete);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity Next Step complete call Error: Some error');

        $this->object->markNextStepComplete(self::STORE_ID, 'next-step-id-17');
    }
}
