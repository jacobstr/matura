<?php namespace Matura\Core;

use Matura\Blocks\Block;
use Matura\Blocks\Methods\TestMethod;

interface ResultComponent
{
    public function totalFailures();
    public function totalSkipped();
    public function totalSuccesses();
    public function totalTests();

    public function isSuccessful();
    public function isFailure();
    public function isSkipped();

    public function getFailures();
}
