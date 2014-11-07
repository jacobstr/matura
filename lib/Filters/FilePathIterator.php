<?php namespace Matura\Filters;

use FilterIterator;
use Iterator;

/**
 * Used to filter paths by their basename and pathname. Assumes it's iterating
 * over SplFileInfo objects.
 */
class FilePathIterator extends  FilterIterator
{
    private $basename_include;
    private $basename_exclude;

    public function __construct(
        Iterator $iterator,
        $basename_include = Defaults::MATCH_ALL,
        $basename_exclude = Defaults::EXCLUDE
    )
    {
        parent::__construct($iterator);
        $this->basename_include = $basename_include;
        $this->basename_exclude = $basename_exclude;
    }

    public function accept()
    {
        $file_info = $this->getInnerIterator()->current();
        return preg_match($this->basename_include, $file_info->getBaseName())
            && !preg_match($this->basename_exclude, $file_info->getBaseName());

    }
}

