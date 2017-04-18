<?php

namespace Erayd\Dogfood\Tests\Standard;

use Erayd\JsonSchemaInfo\SchemaInfo;

use Dogfood\Exception;
use Dogfood\Validator;

class StandardTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ROOT = __DIR__ . '/../vendor/json-schema-org/JSON-Schema-Test-Suite';

    public function dataStandardCase()
    {
        $specs = [
            SchemaInfo::SPEC_DRAFT_03 => 'draft3',
            SchemaInfo::SPEC_DRAFT_04 => 'draft4',
        ];
        $tests = [];

        foreach ($specs as $spec => $testSuiteDirectory) {
            foreach (glob(self::TEST_ROOT . "/tests/{$specs[$spec]}/*.json") as $file) {
                $cases = json_decode(file_get_contents($file));
                foreach ($cases as $caseNo => $case) {
                    foreach ($case->tests as $testNo => $test) {
                        $tests[] = [$test, $case->schema, $file, $spec, $caseNo, $testNo];
                    }
                }
            }
        }

        return $tests;
    }

    /** @dataProvider dataStandardCase **/
    public function testStandardCase($test, $schema, $file, $spec, $caseNo, $testNo)
    {
        // init defaults
        $test->description = ucfirst($test->description);
        $validationResult = false;
        $error = 'Incorrect validation outcome';
        $where = 'Test outcome';

        // provide remote schemas
        $remotes = [
            "http://json-schema.org/$spec/schema" => __DIR__ . "/../dist/$spec/schema.json",
            'http://localhost:1234/integer.json' => self::TEST_ROOT . '/remotes/integer.json',
            'http://localhost:1234/subSchemas.json' => self::TEST_ROOT . '/remotes/subSchemas.json',
            'http://localhost:1234/folder/folderInteger.json' => self::TEST_ROOT . '/remotes/folder/folderInteger.json',
        ];

        // create validator
        $v = new Validator($schema, sprintf('standard://%s.%s', realpath($file), $caseNo), [
            Validator::OPT_SPEC_VERSION    => $spec,
            Validator::OPT_EXCEPTIONS      => true,
            Validator::OPT_VALIDATE_SCHEMA => false,
            Validator::OPT_FETCH_PROVIDER  => function(string $uri) use(&$remotes) : string {
                $uri = explode('#', $uri, 2)[0];
                if (array_key_exists($uri, $remotes)) {
                    return file_get_contents($remotes[$uri]);
                }
                return file_get_contents($uri);
            },
        ]);

        // run validation
        try {
            $validationResult = $v->validate($test->data, $schema, 'file://' . realpath($file));
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $where = basename($e->getFile()) . ':' . $e->getLine();
        }

        // format error message
        $error = sprintf(
            "->  Spec: %s\n->  File: %s\n->  Test: %s (%d.%d)\n-> Where: %s\n-> Error: %s\n",
            $spec,
            basename($file),
            $test->description,
            $caseNo,
            $testNo,
            $where,
            $error
        );

        // check test outcome
        $this->assertEquals(
            $test->valid,
            $validationResult,
            $error
        );
    }
}
