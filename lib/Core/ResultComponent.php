<?php namespace Matura\Core;

/**
 * Composite Pattern for Results / ResultSets.
 */
interface ResultComponent
{
    // Counting
    // ########

    /** @return int */
    public function totalFailures();

    /** @return int */
    public function totalSkipped();

    /** @return int */
    public function totalSuccesses();

    /** @return int */
    public function totalTests();

    // Boolean Checks
    // ##############

    /** @return bool */
    public function isSuccessful();

    /** @return bool */
    public function isFailure();

    /** @return bool */
    public function isSkipped();

    // Retrieval
    // #########

    /** @return ResultComponent[] */
    public function getFailures();
}
