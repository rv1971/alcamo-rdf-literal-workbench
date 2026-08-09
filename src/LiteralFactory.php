<?php

namespace alcamo\rdf_literal_workbench;

use alcamo\dom\schema\{Schema, SchemaFactory, TypeMap};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\{LiteralFactory as RdfLiteralFactory, LiteralInterface};
use alcamo\xml\NamespaceConstantsInterface;

/**
 * @brief Factory creating RDF literals from data types and values
 *
 * While alcamo::rdf_literal::LiteralFactory creates literals from a value, a
 * language and a datatype URI, this class creates them from a value and a
 * SimpleTypeInterface object.
 *
 * Unlike alcamo::rdf_literal::LiteralFactory, this class is aware of type
 * hierarchies. For instance, when given a value and a custom type derived
 * from `xsd:integer`, it is able to conclude that the needed literal class is
 * that for xsd:integer.
 *
 * Since there is no XML Schema datatype corresponding to LangStringLiteral,
 * this class does not support creating LangStringLiteral objects, and
 * therefore the create() method does not have a language parameter.
 *
 * @date Last reviewed 2026-05-04
 */
class LiteralFactory implements NamespaceConstantsInterface
{
    private $schema_;             ///< Schema
    private $typeToLiteralClass_; ///< TypeMap

    public function __construct(
        ?Schema $schema = null,
        ?RdfLiteralFactory $rdfLiteralFactory = null
    ) {
        $this->schema_ = $schema ?? (new SchemaFactory())->getMainSchema();

        if (!isset($rdfLiteralFactory)) {
            $rdfLiteralFactory = new RdfLiteralFactory();
        }

        $map = [];

        foreach (
            $rdfLiteralFactory::getDatatypeUriToClass() as $uri => $class
        ) {
            $map[(string)$this->schema_->createTypeFromUri($uri)->getXName()] =
                $class;
        }

        $this->typeToLiteralClass_ = new TypeMap($map);
    }

    public function getSchema(): Schema
    {
        return $this->schema_;
    }
}
