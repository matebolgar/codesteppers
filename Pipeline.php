<?php

namespace CodeSteppers;

use FastRoute\RouteCollector;

class Pipeline
{
    /**
     * @var RouteCollector
     */
    private $r;

    public function __construct($r)
    {
        $this->r = $r;
    }

    public function get($path, ...$fns)
    {
        $this->r->get($path, $this->compose(...$fns));
    }
    
    public function head($path, ...$fns)
    {
        $this->r->head($path, $this->compose(...$fns));
    }

    public function post($path, ...$fns)
    {
        $this->r->post($path, $this->compose(...$fns));
    }

    public function put($path, ...$fns)
    {
        $this->r->put($path, $this->compose(...$fns));
    }
    public function delete($path, ...$fns)
    {
        $this->r->delete($path, $this->compose(...$fns));
    }

    private function compose()
    {
        $callbacks = func_get_args();
        return function () use ($callbacks) {
            $arguments = func_get_args();
            return array_reduce($callbacks, function ($result, $item) {
                return !is_array($result)
                    ? call_user_func_array($item, [$result])
                    : call_user_func_array($item, $result);
            }, $arguments);
        };
    }
}
