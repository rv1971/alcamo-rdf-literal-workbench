<?php

namespace alcamo\rdf_literal_workbench;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{IntegerLiteral, LanguageLiteral, StringLiteral};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class LiteralWorkbenchTest extends TestCase
{
    public function testCreation(): void
    {
        $deWorkbench = LiteralWorkbench::getMainInstance();

        $this->assertInstanceOf(
            Schema::class,
            $deWorkbench->getSchema()
        );

        $this->assertInstanceOf(
            LiteralFactory::class,
            $deWorkbench->getLiteralFactory()
        );

        $this->assertInstanceOf(
            LiteralTypeMap::class,
            $deWorkbench->getLiteralTypeMap()
        );

        $this->assertSame(
            $deWorkbench->getSchema(),
            $deWorkbench->getLiteralFactory()->getSchema()
        );

        $this->assertSame(
            $deWorkbench->getSchema(),
            $deWorkbench->getLiteralTypeMap()->getSchema()
        );

        $deWorkbench2 = LiteralWorkbench::newFromFactories(
            $deWorkbench->getLiteralFactory(),
            $deWorkbench->getLiteralTypeMap()
        );

        $this->assertSame(
            $deWorkbench->getLiteralFactory(),
            $deWorkbench2->getLiteralFactory()
        );

        $this->assertSame(
            $deWorkbench->getLiteralTypeMap(),
            $deWorkbench2->getLiteralTypeMap()
        );
    }

    public function testException(): void
    {
        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            'Validation failed; Literal factory and literal type map have '
                . 'different schemas'
        );

        LiteralWorkbench::newFromFactories(
            new LiteralFactory(),
            new LiteralTypeMap(
                (new SchemaFactory())->createFromUris(
                    [
                        (new FileUriFactory())
                            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'bar.xsd')
                    ]
                )
            )
        );
    }
}
