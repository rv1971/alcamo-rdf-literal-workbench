# Usage example

~~~
namespace alcamo\rdf_literal_workbench;

use alcamo\rdf_literal\StringLiteral;
use alcamo\uri\FileUriFactory;

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

$factory = new LiteralFactory();

$factory->getSchema()->addUris(
    [
        (new FileUriFactory)->create(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor'
                . DIRECTORY_SEPARATOR . 'alcamo'
                . DIRECTORY_SEPARATOR . 'dom'
                . DIRECTORY_SEPARATOR . 'xsd'
                . DIRECTORY_SEPARATOR . 'xhtml-datatypes-1.xsd'
        )
    ]
);

$ncNameType =
    $factory->getSchema()->getGlobalType(LiteralFactory::XH11D_NS . ' Pixels');

$literal1 = $factory->create(1024, $ncNameType);

echo "$literal1: class " . get_class($literal1) . PHP_EOL;

$typeMap = new LiteralTypeMap();

$typeMap->validateLiteral($literal1);

$literal2 = new StringLiteral('foo', LiteralFactory::XSD_NS . '#QName');

$typeMap->validateLiteral($literal2);
~~~

This example is contained in this package as a file in the `bin`
directory. It will output

~~~
1024: class alcamo\rdf_literal\NonNegativeIntegerLiteral
PHP Fatal error:  Uncaught alcamo\exception\DataValidationFailed: Validation failed; literal datatype http://www.w3.org/2001/XMLSchema QName not derived from default datatype http://www.w3.org/2001/XMLSchema string in /home/rve/src/alcamo-rdf-literal-workbench/src/LiteralTypeMap.php:62
~~~

Unlike `alcamo\rdf_literal\LiteralFactory`, the class
`alcamo\rdf_literal_workbench\LiteralFactory` contains an XML schema
and hence is aware of the type hierarchy. Thus, the above call to
`create()` creates an instance of `NonNegativeIntegerLiteral` since
the factory knows that the simple type `xh11d:Pixels` is derived from
`xsd:NonNegativeInteger`.

The class `alcamo\rdf_literal\LiteralTypeMap`, which contains an XML
Schema as well, can be used to validate whether the datatype of a
literal is actually supported by the literal class, i.e. derived from
its defaut type. In the above example, $literal1 is valid while
$literal2 is not. The literal classes themselves do not check this because they would need an XML schema for this.
