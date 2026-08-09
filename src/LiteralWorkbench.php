<?php

namespace alcamo\rdf_literal_workbench;

use alcamo\dom\schema\{Schema, SchemaFactory, TypeMap};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{
    ConstructedHexBinaryLiteral,
    ConstructedStringLiteral,
    LangStringLiteral,
    LiteralFactory as RdfLiteralFactory,
    LiteralInterface,
    StringLiteral
};
use alcamo\uri\FileUriFactory;
use alcamo\xml\NamespaceConstantsInterface;
use Composer\InstalledVersions;

/**
 * @brief Factory creating and validating RDF literals
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
 * @date Last reviewed 2026-08-09
 */
class LiteralWorkbench implements NamespaceConstantsInterface
{
    /// Classes whose type URI does not point to a real XSD type
    public const PSEUDO_TYPE_URI_CLASSES = [
        ConstructedHexBinaryLiteral::class,
        ConstructedStringLiteral::class,
        LangStringLiteral::class,
    ];

    /**
     * @brief Absolute paths to additional XSDs to load
     *
     * Paths that begin with `vendor` are automatically prefixed with the
     * parent directory of _composer_autoload_path.
     */
    public const ADDTIONAL_XSD_PATHS = [
        'vendor' . DIRECTORY_SEPARATOR
            . 'alcamo' . DIRECTORY_SEPARATOR
            . 'rdf-literal' . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'alcamo.rdf.xsd'
    ];

    public static function getMainInstance(): self
    {
        static $instance;

        return $instance ?? ($instance = new self());
    }

    protected $schema_;           ///< Schema

    private $typeToLiteralClass_; ///< TypeMap

    /// map of string to SimpleTypeInterface
    private $literalClassToDefaultDatatype_ = [];

    public function __construct(
        ?Schema $schema = null,
        ?RdfLiteralFactory $rdfLiteralFactory = null
    ) {
        $this->schema_ = $schema ?? (new SchemaFactory())->getMainSchema();

        $fileUriFactory = new FileUriFactory();

        $xsdUris = [];

        foreach (static::ADDTIONAL_XSD_PATHS as $xsdPath) {
            if (substr($xsdPath, 0, 7) == 'vendor' . DIRECTORY_SEPARATOR) {
                $xsdPath = substr($xsdPath, 7);

                $a = explode(DIRECTORY_SEPARATOR, $xsdPath, 3);

                $xsdPath = InstalledVersions::getInstallPath("{$a[0]}/{$a[1]}")
                    . DIRECTORY_SEPARATOR . $a[2];
            }

            $xsdUris[] = $fileUriFactory->create($xsdPath);
        }

        $this->schema_->addUris($xsdUris);

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


    /**
     * @brief Create a literal
     *
     * @return RDF Literal object of the literal class of the closest ancestor
     * of $datatype for which a literal class is known, containing the URI of
     * $datatype itself.
     */
    public function createLiteral(
        $value,
        SimpleTypeInterface $datatype
    ): LiteralInterface {
        $class = $this->typeToLiteralClass_->lookup($datatype);

        /** If $value is an integer and $datatype is (derived from)
         *  `xsd:boolean`, accept any nonzero value as `true`. This differs
         *  from the behaviour of alcamo::rdf_literal::BooleanLiteral which
         *  only accepts the lexical space of the boolean type (0,1, false and
         *  true). */
        if (
            is_int($value)
                && $datatype
                ->isEqualToOrDerivedFrom(Schema::XSD_NS . ' boolean')
        ) {
            $value = (bool)$value;
        }

        return new $class($value, $datatype->getUri());
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
