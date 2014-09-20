<?php namespace Matura\Blocks\Methods;

use Matura\Exceptions\Exception;
use Matura\Exceptions\SkippedException;
use Matura\Exceptions\IncompleteException;
use Matura\Blocks\Describe;

class TestMethod extends Method
{
    public function collectOrderedBefores()
    {
        $befores = array();
        $this->traversePost(function ($block)  use (&$befores) {
            $befores = array_merge($befores, $block->befores());
        });
        return $befores;
    }

    public function collectOrderedAfters()
    {
        $afters = array();
        $this->traversePre(function ($block)  use (&$afters) {
            $afters = array_merge($afters, $block->afters());
        });
        return $afters;
    }

    public function aroundEach($fn)
    {
        foreach(array_merge(
            $this->collectOrderedBefores(),
            array($this),
            $this->collectOrderedAfters()
        ) as $block) {
            $fn($block);
        }
    }

    public function invoke()
    {
        if ($this->hasSkippedAncestors()) {
            return $this->invokeWithin(
                function() { throw New SkippedException(); },
                array($this->createContext())
            );
        } else {
            return $this->invokeWithin($this->fn, array($this->createContext()));
        }
    }
}
