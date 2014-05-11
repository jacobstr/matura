<?php namespace Matura\Blocks\Methods;

class OnceBeforeHook extends HookMethod
{
    protected $result;
    protected $invoked;

    public function invoke()
    {
        if($this->invoked) {
            return $this->result;
        } else {
            $this->result = $this->invokeWithin($this->fn, array($this->createContext()));
            $this->invoked = true;
            return $this->result;
        }
    }

    public function createContext()
    {
        if($this->context) {
            return $this->context;
        } else {
            return parent::createContext();
        }
    }

}
