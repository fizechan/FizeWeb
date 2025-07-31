<?php

namespace Tests;

use Fize\Web\Session;
use PHPUnit\Framework\TestCase;

class TestSession extends TestCase
{

    public function test__construct()
    {
        new Session();
    }
}
