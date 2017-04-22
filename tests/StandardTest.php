<?php

namespace Erayd\Dogfood\Tests\Standard;

use Erayd\JsonSchemaInfo\SchemaInfo;

use Dogfood\Exception;
use Dogfood\Validator;

class StandardTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ROOT = __DIR__ . '/../vendor/json-schema-org/JSON-Schema-Test-Suite';

    public function getValidator() : Validator
    {
        // provide remote schemas
        $remotes = [
            "http://json-schema.org/draft-03/schema" => __DIR__ . "/../dist/draft-03/schema.json",
            "http://json-schema.org/draft-04/schema" => __DIR__ . "/../dist/draft-04/schema.json",
        ];

        // set up validator
        return new Validator(null, null, [
            Validator::OPT_EXCEPTIONS      => true,
            Validator::OPT_VALIDATE_SCHEMA => true,
            Validator::OPT_FETCH_PROVIDER  => function (string $uri) use (&$remotes) : string {
                $uri = explode('#', $uri, 2)[0];
                if (array_key_exists($uri, $remotes)) {
                    return file_get_contents($remotes[$uri]);
                } elseif (preg_match('|^http://localhost:1234/(.+)$|', $uri, $matches)) {
                    return file_get_contents(self::TEST_ROOT . "/remotes/{$matches[1]}");
                }
                return file_get_contents($uri);
            },
        ]);
    }

    public function dataStandardCase() : array
    {
        $specs = [
            SchemaInfo::SPEC_DRAFT_03 => 'draft3',
            SchemaInfo::SPEC_DRAFT_04 => 'draft4',
        ];
        $tests = [];

        foreach ($specs as $spec => $testSuiteDirectory) {
            foreach (glob(self::TEST_ROOT . "/tests/{$specs[$spec]}{/,/optional/}*.json", \GLOB_BRACE) as $file) {
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
        $validator = $this->getValidator();

        // init defaults
        $test->description = ucfirst($test->description);
        $validationResult = false;
        $error = 'Incorrect validation outcome';
        $where = 'Test outcome';

        try {
            // import schema
            $uri = sprintf('standard://%s.%s', realpath($file), $caseNo);
            $validator->addSchema($uri, $schema, $spec);

            // run validation
            $validationResult = $validator->validate($test->data, $uri);
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
