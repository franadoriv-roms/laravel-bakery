<?php

namespace Bakery\Tests\Types;

use Bakery\Tests\TestCase;
use Bakery\Tests\Stubs\EnumTypeStub;
use GraphQL\Type\Definition\EnumType as GraphQLEnumType;

class EnumTypeTest extends TestCase
{
    /** @test */
    public function it_returns_the_enum_object_type()
    {
        $type = new EnumTypeStub();
        $objectType = $type->toType();

        $this->assertInstanceOf(GraphQLEnumType::class, $objectType);
        $this->assertEquals($type->name, $objectType->name);
    }
}
