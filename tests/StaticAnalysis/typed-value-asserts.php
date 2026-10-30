<?php
declare(strict_types=1);

namespace tests\dzentota\TypedValue\StaticAnalysis;

use function PHPStan\Testing\assertType;

function checkFromNative(): void
{
    $foo = StringFixture::fromNative('x');
    assertType(StringFixture::class, $foo);
    assertType('string', $foo->toNative());
}

function checkTryParse(mixed $input): void
{
    if (StringFixture::tryParse($input, $out)) {
        assertType(StringFixture::class, $out);
        assertType('string', $out->toNative());
    } else {
        assertType(StringFixture::class . '|null', $out);
    }
}
