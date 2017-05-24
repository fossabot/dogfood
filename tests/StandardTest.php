<?php

namespace JsonValidator\Tests;

use JsonValidator\Exception;
use JsonValidator\Validator;
use JsonValidator\Config;
use JsonValidator\Internal\SchemaInfo;

class StandardTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ROOT = __DIR__ . '/../vendor/json-schema-org/JSON-Schema-Test-Suite';

    public function getValidator() : Validator
    {
        // provide remote schemas
        $remotes = [
            "http://json-schema.org/draft-03/schema" => __DIR__ . "/../dist/draft-03/schema.json",
            "http://json-schema.org/draft-04/schema" => __DIR__ . "/../dist/draft-04/schema.json",
            "http://json-schema.org/draft-06/schema" => __DIR__ . "/../dist/draft-06/schema.json",
        ];

        // set up validator
        return new Validator([
            Config::APPLY_DEFAULTS => false,
            Config::THROW_EXCEPTIONS => true,
            Config::FETCH_HANDLER  => function (string $uri) use (&$remotes) : string {
                $uri = explode('#', $uri, 2)[0];
                if (array_key_exists($uri, $remotes)) {
                    return file_get_contents($remotes[$uri]);
                } elseif (preg_match('|^http://localhost:1234/(.+)$|', $uri, $matches)) {
                    return file_get_contents(self::TEST_ROOT . "/remotes/{$matches[1]}");
                }
                throw new \Exception("Not available in testsuite: $uri");
            },
        ]);
    }

    public function dataStandardCase() : array
    {
        $specs = [
            SchemaInfo::SPEC_DRAFT_03 => 'draft3',
            SchemaInfo::SPEC_DRAFT_04 => 'draft4',
            //SchemaInfo::SPEC_DRAFT_06 => 'draft4',
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
            //printf("\n ===== Start validation =====\n");
            // import schema
            $uri = sprintf('standard:%s.%s.%s', realpath($file), $caseNo, $testNo);
            $validationURI = $validator->addSchema($schema, $uri, $spec);

            // run validation
            $validationResult = $validator->validate($test->data, $validationURI);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $where = basename($e->getFile()) . ':' . $e->getLine();
        }

        // format error message
        $error = sprintf(
            "->  Spec: %s\n->  File: %s\n->  Test: %s (%d.%d)\n-> Where: %s\n".
            "-> Error: %s\n\nTest Schema:\n%s\n\nTest Document:\n%s\n",
            $spec,
            basename($file),
            $test->description,
            $caseNo,
            $testNo,
            $where,
            $error,
            json_encode($schema, \JSON_PRETTY_PRINT|\JSON_UNESCAPED_SLASHES),
            json_encode($test->data, \JSON_PRETTY_PRINT|\JSON_UNESCAPED_SLASHES)
        );

        // check test outcome
        $this->assertEquals(
            $test->valid,
            $validationResult,
            $error
        );
    }
}
