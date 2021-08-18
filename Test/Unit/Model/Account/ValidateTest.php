<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Account;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Account\Validate;
use PureClarity\Api\Account\Validate as ApiValidate;
use PureClarity\Api\Account\ValidateFactory;

/**
 * Class ValidateTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Account\Validate
 */
class ValidateTest extends TestCase
{
    const ACCESS_KEY = 'AccessKey1234';
    const SECRET_KEY = 'SecretKey1234';
    const REGION_ID = 1;
    const STORE_ID = 1;

    /** @var Validate $object */
    private $object;

    /** @var MockObject|ValidateFactory $validateFactory */
    private $validateFactory;

    /** @var MockObject|ApiValidate $validate */
    private $validate;

    protected function setUp(): void
    {
        $this->validateFactory = $this->createMock(ValidateFactory::class);
        $this->validate = $this->createMock(ApiValidate::class);

        $this->validateFactory->method('create')
            ->willReturn($this->validate);

        $this->object = new Validate(
            $this->validateFactory
        );
    }

    /**
     * Returns default parameters to use in calls
     */
    private function getDefaultParams()
    {
        return [
            'access_key' => self::ACCESS_KEY,
            'secret_key' => self::SECRET_KEY,
            'region' => self::REGION_ID,
            'store_id' => self::STORE_ID
        ];
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Validate::class, $this->object);
    }

    /**
     * Tests that sendRequest handles errors
     */
    public function testSendRequestErrors()
    {
        $this->validate->expects(self::once())
            ->method('request')
            ->with($this->getDefaultParams())
            ->willReturn([
                'errors' => ['error1', 'error2']
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            ['error' => 'error1, error2'],
            $result
        );
    }

    /**
     * Tests that sendRequest handles a non-200 http status
     */
    public function testSendRequestNon200Status()
    {
        $this->validate->expects(self::once())
            ->method('request')
            ->with($this->getDefaultParams())
            ->willReturn([
                'errors' => '',
                'status' => '400'
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            [
                'error' => 'PureClarity server error occurred. If this persists,'
                            . ' please contact PureClarity support. Error code 400'
            ],
            $result
        );
    }

    /**
     * Tests that sendRequest handles an invalid response
     */
    public function testSendRequestInvalidResponse()
    {
        $this->validate->expects(self::once())
            ->method('request')
            ->with($this->getDefaultParams())
            ->willReturn([
                'errors' => '',
                'status' => 200,
                'response' => []
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            [
                'error' => 'An error occurred. If this persists, please contact PureClarity support.'
            ],
            $result
        );
    }

    /**
     * Tests that sendRequest handles an invalid account
     */
    public function testSendRequestNotValidAccount()
    {
        $this->validate->expects(self::once())
            ->method('request')
            ->with($this->getDefaultParams())
            ->willReturn([
                'errors' => '',
                'status' => 200,
                'response' => [
                    'IsValid' => false
                ]
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            [
                'error' => 'Account not found, please check your details and try again.'
            ],
            $result
        );
    }

    /**
     * Tests that sendRequest handles a valid account
     */
    public function testSendRequestValidAccount()
    {
        $this->validate->expects(self::once())
            ->method('request')
            ->with($this->getDefaultParams())
            ->willReturn([
                'errors' => '',
                'status' => 200,
                'response' => [
                    'IsValid' => true
                ]
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(['error' => ''], $result);
    }

    /**
     * Tests that sendRequest handles a thrown \Exception
     */
    public function testSendRequestException()
    {
        $this->validate->expects(self::once())
            ->method('request')
            ->with($this->getDefaultParams())
            ->willThrowException(new \Exception('Some error'));

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(['error' => 'PureClarity Link Account Error: Some error'], $result);
    }
}
