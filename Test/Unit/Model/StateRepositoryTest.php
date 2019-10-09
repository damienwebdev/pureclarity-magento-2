<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Model\ResourceModel\State as StateResource;
use Pureclarity\Core\Model\State;
use Pureclarity\Core\Model\StateRepository;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\ResourceModel\StateFactory;
use Pureclarity\Core\Model\ResourceModel\State\CollectionFactory;
use Pureclarity\Core\Model\ResourceModel\State\Collection;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Class StateRespositoryTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class StateRepositoryTest extends TestCase
{
    /** @var StateRepository $object */
    private $object;

    /** @var CollectionFactory|MockObject $collectionFactoryMock */
    private $collectionFactoryMock;

    /** @var Collection|MockObject $collectionMock */
    private $collectionMock;

    /** @var SearchCriteriaInterfaceFactory|MockObject $searchCriteriaInterfaceFactoryMock */
    private $searchCriteriaInterfaceFactoryMock;

    /** @var SearchCriteriaInterface|MockObject $searchCriteriaInterfaceMock */
    private $searchCriteriaInterfaceMock;

    /** @var StateFactory|MockObject $stateFactoryMock */
    private $stateFactoryMock;

    /** @var StateResource|MockObject $stateResourceMock */
    private $stateResourceMock;

    /** @var CollectionProcessorInterface|MockObject $collectionProcessorInterfaceMock */
    private $collectionProcessorInterfaceMock;

    protected function setUp()
    {
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['addFieldToFilter', 'getSize', 'setCurPage', 'setPageSize', 'load', 'addOrder', 'getFirstItem']
            )
            ->getMock();

        $this->collectionFactoryMock->expects($this->any())->method('create')
            ->will($this->returnValue($this->collectionMock));

        $this->searchCriteriaInterfaceFactoryMock = $this->getMockBuilder(SearchCriteriaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->searchCriteriaInterfaceMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaInterfaceFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->searchCriteriaInterfaceMock));

        $this->stateFactoryMock = $this->getMockBuilder(StateFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->stateResourceMock = $this->getMockBuilder(StateResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateFactoryMock->expects($this->any())->method('create')
            ->will($this->returnValue($this->stateResourceMock));

        $this->collectionProcessorInterfaceMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new StateRepository(
            $this->collectionFactoryMock,
            $this->searchCriteriaInterfaceFactoryMock,
            $this->collectionProcessorInterfaceMock,
            $this->stateFactoryMock
        );
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

    public function testStateRepositoryInstance()
    {
        $this->assertInstanceOf(StateRepository::class, $this->object);
    }

    public function testStateRepositoryInterface()
    {
        $this->assertInstanceOf(StateRepositoryInterface::class, $this->object);
    }

    public function testGetByNameAndStore()
    {
        $this->collectionMock->expects($this->once())->method('getFirstItem')->willReturn($this->getStateMock());
        $result = $this->object->getByNameAndStore('name', 1);
        $this->assertInstanceOf(StateInterface::class, $result);
    }

    public function testGetByNameAndStoreExpectingResult()
    {
        $this->collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->getStateMock('1', 'name', 'value!', '1'));

        $result = $this->object->getByNameAndStore('name', 1);
        $this->assertInstanceOf(StateInterface::class, $result);
        $this->assertEquals('1', $result->getId());
        $this->assertEquals('name', $result->getName());
        $this->assertEquals('value!', $result->getValue());
        $this->assertEquals('1', $result->getStoreId());
    }

    public function testCollectionGetsCorrectFilters()
    {
        $this->collectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter');

        $this->collectionMock->expects($this->at(0))
        ->method('addFieldToFilter')
            ->with('name', 'name_to_look_for');

        $this->collectionMock->expects($this->at(1))
            ->method('addFieldToFilter')
            ->with('store_id', 1);

        $this->object->getByNameAndStore('name_to_look_for', 1);
    }

    public function testSave()
    {
        $this->stateResourceMock->expects($this->exactly(1))
            ->method('save');

        $this->object->save($this->getStateMock());
    }

    public function testBadSave()
    {
        $this->stateResourceMock->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception('Something bad happened'));

        $this->expectException(CouldNotSaveException::class);

        $this->object->save($this->getStateMock());
    }

    public function testDelete()
    {
        $this->stateResourceMock->expects($this->exactly(1))
            ->method('delete');

        $this->object->delete($this->getStateMock());
    }

    public function testBadDelete()
    {
        $this->stateResourceMock->expects($this->any())
            ->method('delete')
            ->willThrowException(new \Exception('Something bad happened'));

        $this->expectException(CouldNotDeleteException::class);

        $this->object->delete($this->getStateMock());
    }
}
