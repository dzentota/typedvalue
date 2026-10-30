<?php
declare(strict_types=1);

namespace tests\dzentota\TypedValue\StaticAnalysis;

use PHPStan\Testing\TypeInferenceTestCase;

final class TypeInferenceTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/typed-value-asserts.php');
    }

    /**
     * @dataProvider dataFileAsserts
     */
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
