<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Cron;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Cron\CheckVersion;
use Pureclarity\Core\Helper\Serializer;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Service\Url;
use Magento\Framework\HTTP\Client\Curl;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\State;

/**
 * Class CheckVersionTest
 *
 * Tests the methods in \Pureclarity\Core\Cron\CheckVersion
 */
class CheckVersionTest extends TestCase
{
    /** @var string $testUrl */
    private $testUrl = 'https://www.google.com/';

    /** @var CheckVersion $object */
    private $object;

    /** @var MockObject|Url $url*/
    private $url;

    /** @var MockObject|Curl $curl*/
    private $curl;

    /** @var MockObject|Serializer $serializer*/
    private $serializer;

    /** @var MockObject|StateRepositoryInterface $stateRepository*/
    private $stateRepository;

    /** @var MockObject|LoggerInterface $logger*/
    private $logger;

    protected function setUp()
    {
        $this->url = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new CheckVersion(
            $this->url,
            $this->curl,
            $this->serializer,
            $this->stateRepository,
            $this->logger
        );

        $this->url->expects($this->any())
            ->method('getGithubUrl')
            ->willReturn('https://www.google.com/');

        $this->serializer->expects($this->any())->method('serialize')->will($this->returnCallback(function ($param) {
            return json_encode($param);
        }));

        $this->serializer->expects($this->any())->method('unserialize')->will($this->returnCallback(function ($param) {
            return json_decode($param, true);
        }));
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $state->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $state->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $state->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        $state->expects($this->any())
            ->method('getValue')
            ->willReturn($value);

        return $state;
    }

    private function setupCurlGet()
    {
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->testUrl);
    }

    private function setupCurlGetStatus($code = 200)
    {
        $this->curl->expects($this->once())
            ->method('getStatus')
            ->willReturn($code);
    }

    private function setupCurlGetBody($version = Data::CURRENT_VERSION)
    {
        $data = [
            'tag_name' => $version
        ];

        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));
    }

    private function expectError($error)
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with($error);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(CheckVersion::class, $this->object);
    }

    public function testExecuteException()
    {
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->testUrl)
            ->willThrowException(new \Exception('Some sort of error'));

        $this->expectError('PureClarity Check Version cron error: Some sort of error');

        $this->object->execute();
    }

    public function testExecuteErrorResponse()
    {
        $this->setupCurlGet();
        $this->setupCurlGetStatus(500);

        $this->expectError(
            'PureClarity Check Version cron error: error retrieving latest version number. Response code 500'
        );

        $this->object->execute();
    }

    public function testExecuteInvalidResponse()
    {
        $this->setupCurlGet();
        $this->setupCurlGetStatus();

        $this->expectError(
            'PureClarity Check Version cron error: error retrieving latest version number, bad response format'
        );

        $this->object->execute();
    }

    public function testExecuteUpToDateNoDelete()
    {
        $this->setupCurlGet();
        $this->setupCurlGetStatus();
        $this->setupCurlGetBody();

        $state = $this->getStateMock();

        $state->expects($this->never())
            ->method('setName')
            ->with('new_version');

        $this->stateRepository->expects($this->once())
            ->method('getByNameAndStore')
            ->with('new_version', 0)
            ->willReturn($state);

        $this->stateRepository->expects($this->never())
            ->method('save');

        $this->stateRepository->expects($this->never())
            ->method('delete');

        $this->object->execute();
    }

    public function testExecuteUpToDateWithDelete()
    {
        $this->setupCurlGet();
        $this->setupCurlGetStatus();
        $this->setupCurlGetBody();

        $state = $this->getStateMock();
        $this->stateRepository->expects($this->once())
            ->method('getByNameAndStore')
            ->with('new_version', 0)
            ->willReturn($this->getStateMock(1, 'new_version', Data::CURRENT_VERSION, 0));

        $state->expects($this->never())
            ->method('setName')
            ->with('new_version');

        $this->stateRepository->expects($this->never())
            ->method('save');

        $this->stateRepository->expects($this->once())
            ->method('delete');

        $this->object->execute();
    }

    public function testExecuteNewVersion()
    {
        $this->setupCurlGet();
        $this->setupCurlGetStatus();
        $this->setupCurlGetBody('9.9.9');

        $state = $this->getStateMock();
        $state->expects($this->once())
            ->method('setValue')
            ->with('9.9.9');

        $state->expects($this->once())
            ->method('setStoreId')
            ->with(0);

        $state->expects($this->once())
            ->method('setName')
            ->with('new_version');

        $this->stateRepository->expects($this->once())
            ->method('getByNameAndStore')
            ->with('new_version', 0)
            ->willReturn($state);

        $this->stateRepository->expects($this->once())
            ->method('save');

        $this->stateRepository->expects($this->never())
            ->method('delete');

        $this->object->execute();
    }
}
