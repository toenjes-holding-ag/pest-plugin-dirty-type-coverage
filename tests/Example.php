<?php

use function Pest\DirtyTypeCoverage\example;

it('may be accessed on the `$this` closure', function () {
    $this->example('foo');
});

it('may be accessed as function', function () {
    example('foo');
});
