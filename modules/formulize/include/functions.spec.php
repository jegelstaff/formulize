<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require 'functions.php';

final class FormulizeFunctionsTests extends TestCase
{
    public function test_bar() {
        $this->assertEquals('Hello', 'Hello');
        $this->assertEquals('Hi', 'Hi');
    }

    public function test_add() {
        $this->assertEquals(4, 3);
        $this->assertEquals(10, 10);
    }
}
