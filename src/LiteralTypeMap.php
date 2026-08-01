<?php

namespace alcamo\rdf_literal_workbench;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\rdf_literal\{
    ConstructedHexBinaryLiteral,
    ConstructedStringLiteral,
    LangStringLiteral,
    LiteralInterface,
    StringLiteral
};

/**
 * @brief Map of literal classes to their datatypes
 *
 * @date Last reviewed 2026-05-04
 */
class LiteralTypeMap
{
    /// Classes whose type URI does not point to a real XSD type
    public const PSEUDO_TYPE_URI_CLASSES = [
        ConstructedHexBinaryLiteral::class,
        ConstructedStringLiteral::class,
        LangStringLiteral::class,
    ];

    private $schema_; ///< Schema

    /// map of string to SimpleTypeInterface
    private $literalClassToDefaultDatatype_ = [];

    public function __construct(?Schema $schema = null)
    {
        $this->schema_ = $schema ?? (new SchemaFactory())->getMainSchema();
    }

    public function getSchema(): Schema
    {
        return $this->schema_;
    }

    public function getDefaultDatatype($literalClass): SimpleTypeInterface
    {
        if (!isset($this->literalClassToDefaultDatatype_[$literalClass])) {
            $this->literalClassToDefaultDatatype_[$literalClass] =
                $this->createTypeFromUri(
                    $literalClass::getClassDefaultDatatypeUri()
                );
        }

        return $this->literalClassToDefaultDatatype_[$literalClass];
    }

    /**
     * @brief Check whether the literal's datatype is derived from the
     * literal's default datatype.
     *
     * @return The literal's datatype.
     */
    public function validateLiteral(
        LiteralInterface $literal
    ): SimpleTypeInterface {
        $datatype = $this->createTypeFromUri($literal->getDatatypeUri());

        $datatype->throwUnlessEqualToOrDerivedFrom(
            $this->getDefaultDatatype(get_class($literal))->getXName()
        );

        return $datatype;
    }

    /** If the datatype URI is the DEFAULT_DATATYPE_URI of one of those in
     *  alcamo::rdf_literal_workbench::LiteralTypeMap::PSEUDO_TYPE_URI_CLASSES,
     *  replace it with the PRIMITIVE_DATATYPE_URI of the same class. */
    protected function createTypeFromUri($datatypeUri): SimpleTypeInterface
    {
        foreach (static::PSEUDO_TYPE_URI_CLASSES as $class) {
            if ($datatypeUri == $class::DEFAULT_DATATYPE_URI) {
                $datatypeUri = $class::PRIMITIVE_DATATYPE_URI;
                break;
            }
        }

        return $this->schema_->createTypeFromUri($datatypeUri);
    }
}
