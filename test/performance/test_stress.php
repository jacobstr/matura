<?php

use Matura\Test\Support\Util;

describe('Deep and Branched', function ($ctx) use (&$gensuite) {
    Util::gensuite(array('depth' => 5, 'tests' => 15, 'befores' => 5));
});

describe('Shallow', function ($ctx) use (&$gensuite) {
    Util::gensuite(array('depth' => 1, 'tests' => 1000, 'befores' => 5));
});
