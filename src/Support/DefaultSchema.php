<?php

namespace Bakery\Support;

class DefaultSchema extends Schema
{
    public function models()
    {
        return app('config')->get('bakery.models', []);
    }
}