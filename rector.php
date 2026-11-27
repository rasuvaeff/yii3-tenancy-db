<?php

declare(strict_types=1);

use Rasuvaeff\RectorNamedLiterals\AddNameToLiteralArgumentRector;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withRules([AddNameToLiteralArgumentRector::class])
    ->withSkip([
        // `$x === null` reads clearer than `!$x instanceof Some\Long\ClassName`
        // when the declared type already names the class.
        ExplicitNullableObjectInstanceRector::class,
        // Removes the `@var mixed` on an assignment from a mixed-returning
        // call (PSR-16 get(), a decoded JSON value). Rector reads them as
        // useless; psalm requires them and reports MixedAssignment without.
        // The documented rector<->psalm conflict, resolved the same way as
        // in media-converter.
        RemoveUselessVarTagRector::class,
        // Removes `/** @var mixed */` on assignments from a mixed-returning
        // call. Rector reads them as redundant; psalm requires them and
        // reports MixedAssignment without. The documented rector<->psalm
        // conflict, resolved the same way as in the other packages here.
        RemoveNonExistingVarAnnotationRector::class,
        // Every throw here already passes `previous:`; what the rule adds on top
        // is the caught exception's code, which changes the code the thrown
        // exception carries. That is a behaviour change no test asked for.
        ThrowWithPreviousExceptionRector::class,
    ]);
