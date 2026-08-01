<?php

namespace alcamo\rdf_literal_workbench;

use alcamo\dom\schema\Schema;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\{
    AnyUriLiteral,
    Base64BinaryLiteral,
    BooleanLiteral,
    ConstructedHexBinaryLiteral,
    ConstructedStringLiteral,
    DateLiteral,
    DateTimeLiteral,
    DecimalLiteral,
    DigitStringLiteral,
    DoubleLiteral,
    DurationLiteral,
    FloatLiteral,
    FourBitCharStringLiteral,
    GDayLiteral,
    GMonthDayLiteral,
    GMonthLiteral,
    GYearLiteral,
    GYearMonthLiteral,
    HexBinaryLiteral,
    IntegerLiteral,
    LangStringLiteral,
    LanguageLiteral,
    MediaTypeLiteral,
    NonNegativeIntegerLiteral,
    NotationLiteral,
    PositiveGYearLiteral,
    QNameLiteral,
    StringLiteral,
    TimeLiteral
};
use alcamo\exception\InvalidType;
use PHPUnit\Framework\TestCase;

class LiteralTypeMapTest extends TestCase
{
    public const XSD_NS = Schema::XSD_NS;

    private static $literalTypeMap_;

    public static function setUpBeforeClass(): void
    {
        self::$literalTypeMap_ = new LiteralTypeMap();
    }

    /**
     * @dataProvider arrayAccessProvider
     */
    public function testGetDefaultDatatype(
        $literalClass,
        $expectedLocalName
    ): void {
        $this->assertSame(
            $expectedLocalName,
            self::$literalTypeMap_
                ->getDefaultDatatype($literalClass)
                ->getXName()
                ->getLocalName()
        );
    }

    public function arrayAccessProvider(): array
    {
        return [
            [ AnyUriLiteral::class, 'anyURI' ],
            [ Base64BinaryLiteral::class, 'base64Binary' ],
            [ BooleanLiteral::class, 'boolean' ],
            [ DateLiteral::class, 'date' ],
            [ DateTimeLiteral::class, 'dateTime' ],
            [ DecimalLiteral::class, 'decimal' ],
            [ DigitStringLiteral::class, 'DigitString' ],
            [ DoubleLiteral::class, 'double' ],
            [ DurationLiteral::class, 'duration' ],
            [ FloatLiteral::class, 'float' ],
            [ FourBitCharStringLiteral::class, 'FourBitCharString' ],
            [ GDayLiteral::class, 'gDay' ],
            [ GMonthDayLiteral::class, 'gMonthDay' ],
            [ GMonthLiteral::class, 'gMonth' ],
            [ GYearLiteral::class, 'gYear' ],
            [ GYearMonthLiteral::class, 'gYearMonth' ],
            [ IntegerLiteral::class, 'integer' ],
            [ LangStringLiteral::class, 'string' ],
            [ LanguageLiteral::class, 'language' ],
            [ MediaTypeLiteral::class, 'string' ],
            [ NotationLiteral::class, 'NOTATION' ],
            [ PositiveGYearLiteral::class, 'PositiveGYear' ],
            [ QNameLiteral::class, 'QName' ],
            [ StringLiteral::class, 'string' ],
            [ TimeLiteral::class, 'time' ]
        ];
    }

    /**
     * @dataProvider validateLiteralProvider
     */
    public function testValidateLiteral($literal): void
    {
        $this->assertInstanceOf(
            SimpleTypeInterface::class,
            self::$literalTypeMap_->validateLiteral($literal)
        );
    }

    public function validateLiteralProvider(): array
    {
        return [
            [ new BooleanLiteral() ],
            [ new IntegerLiteral(42, self::XSD_NS . '#byte') ],
            [
                new GYearLiteral(
                    2027,
                    PositiveGYearLiteral::DEFAULT_DATATYPE_URI
                )
            ],
            [
                new ConstructedStringLiteral(
                    [
                        new StringLiteral('foo'),
                        new IntegerLiteral(42)
                    ],
                    self::XSD_NS . '#token'
                )
            ],
            [
                new ConstructedHexBinaryLiteral(
                    [
                        new HexBinaryLiteral('ABCD')
                    ]
                )
            ]
        ];
    }

    public function testGetDefaultDatatypeException(): void
    {
        $this->expectException(\Error::class);

        $this->expectExceptionMessage("Class 'foo' not found");

        self::$literalTypeMap_->getDefaultDatatype('foo');
    }

    public function testValidateLiteralException(): void
    {
        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type "http://www.w3.org/2001/XMLSchema integer", '
                . 'expected one of "derived from http://www.w3.org/2001/X..."'
        );

        self::$literalTypeMap_->validateLiteral(
            new NonNegativeIntegerLiteral(
                42,
                IntegerLiteral::DEFAULT_DATATYPE_URI
            )
        );
    }
}
