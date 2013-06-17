<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\EntityAutocomplete\Transformer;

use Oro\Bundle\FormBundle\EntityAutocomplete\Property;
use Oro\Bundle\FormBundle\EntityAutocomplete\Transformer\EntityToTextTransformer;

class EntityToTextTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityToTextTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new EntityToTextTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     * @param array|object $value
     * @param Property[] $properties
     * @param string $expected
     */
    public function testTransform($value, array $properties, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value, $properties));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return array(
            'no value, no properties' => array(
                null, array(), ''
            ),
            'no value, properties' => array(
                null, array($this->getPropertyMock('name')), ''
            ),
            'value array, no properties' => array(
                array('name' => 'test'), array(), ''
            ),
            'value object, no properties' => array(
                $this->getValueObjectMock(array('getName' => 'test')), array(), ''
            ),
            'value array, properties unknown' => array(
                array('name' => 'test'), array($this->getPropertyMock('unknown')), ''
            ),
            'value array, one property' => array(
                array('name' => 'test'), array($this->getPropertyMock('name')), 'test'
            ),
            'value array, more than one property' => array(
                array('name' => 'test', 'second_name' => 'second'),
                array($this->getPropertyMock('name'), $this->getPropertyMock('second_name')),
                'test second'
            ),
            'value object method, properties unknown' => array(
                $this->getValueObjectMock(array('getName' => 'test')), array($this->getPropertyMock('unknown')), ''
            ),
            'value object method, one property' => array(
                $this->getValueObjectMock(array('getName' => 'test')), array($this->getPropertyMock('name')), 'test'
            ),
            'value object method, more than one property' => array(
                $this->getValueObjectMock(array('getName' => 'test', 'getSecondName' => 'second')),
                array($this->getPropertyMock('name'), $this->getPropertyMock('second_name')),
                'test second'
            ),

            'value object property, properties unknown' => array(
                (object)array('name' => 'test'), array($this->getPropertyMock('unknown')), ''
            ),
            'value object property, one property' => array(
                (object)array('name' => 'test'), array($this->getPropertyMock('name')), 'test'
            ),
            'value object property, more than one property' => array(
                (object)array('name' => 'test', 'second_name' => 'second'),
                array($this->getPropertyMock('name'), $this->getPropertyMock('second_name')),
                'test second'
            ),
        );
    }

    protected function getValueObjectMock(array $data)
    {
        $mock = $this->getMockBuilder('\stdClass')
            ->setMethods(array_keys($data))
            ->getMock();
        foreach ($data as $method => $val) {
            $mock->expects($this->once())
                ->method($method)
                ->will($this->returnValue($val));
        }
        return $mock;
    }

    protected function getPropertyMock($name)
    {
        $mock = $this->getMockBuilder('Oro\Bundle\FormBundle\EntityAutocomplete\Property')
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));
        return $mock;
    }
}
