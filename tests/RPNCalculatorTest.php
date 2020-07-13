<?php

namespace RPNCalculator\Tests;

use PHPUnit\Framework\TestCase;
use RPNCalculator\RPNCalculator;

class RPNCalculatorTest extends TestCase {

    /**
     * Get protected and private methods of the tested class
     *
     * @param (\RPNCalculator\RPNCalculator) $object - the object whose method to call
     * @param (string) $method - the method's name
     * @param (array) $params - the parameters to pass to the method, as an array
     * @return (mixed) the result of calling that method
     */
    protected function callMethod ($object, $method, $params = []) {
        $m = (new \ReflectionClass(get_class($object)))->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($object, $params);
    }

    /**
     * Get protected and private properties of the tested class
     *
     * (NOT USED CURRENTLY)
     *
     * @param (\RPNCalculator\RPNCalculator) $object - the object whose property to get
     * @param (string) $property - the property's name
     * @return (mixed) that property's value
     */
    // protected function getProperty ($object, $property) {
    //     return (new \ReflectionClass(get_class($object)))
    //            ->getProperty($property)
    //            ->setAccessible(true);
    // }

    /**
     * Test that the first two tokens are numerical,
     * and that the input contains only allowed characters.
     */
    public function testIsValidInput () {
        $calc = new RPNCalculator;

        $this->assertTrue($this->callMethod($calc, 'isValidInput', [['3']]));
        $this->assertTrue($this->callMethod($calc, 'isValidInput', [['3', '4']]));
        $this->assertTrue($this->callMethod($calc, 'isValidInput', [['3', '4', '+']]));
        $this->assertTrue($this->callMethod($calc, 'isValidInput', [['3', '4', '+', '-', '*', '/']]));
        $this->assertTrue($this->callMethod($calc, 'isValidInput', [['.7', '1.5', '0.567', '-.80', '-2.98', '1.678e+32', '67E+5', '43E2', '70e-54', '-80E-79', '-', '+', '+', '-', '-', '-']]));

        $this->assertFalse($this->callMethod($calc, 'isValidInput', [['+']]));
        $this->assertFalse($this->callMethod($calc, 'isValidInput', [['a']]));
        $this->assertFalse($this->callMethod($calc, 'isValidInput', [['3', '-', '4']]));
        $this->assertFalse($this->callMethod($calc, 'isValidInput', [['3', '4', '-', 'e20']]));
        $this->assertFalse($this->callMethod($calc, 'isValidInput', [['3', '4', '-', 'a']]));
    }

    public function testOperate () {
        $calc = new RPNCalculator;

        $this->assertIsString($calc->operate());

        // "assertEquals" doesn't check type, but we do
        // (maybe in the future it will, too)

        $isBcadd = function_exists('bcadd');
        $isBcsub = function_exists('bcsub');
        $isBcmul = function_exists('bcmul');
        $isBcdiv = function_exists('bcdiv');

        $this->assertEquals($isBcadd ? '10' : 10, $calc->operate('3', '7', '+'));
        $this->assertEquals($isBcadd ? '800' : 800, $calc->operate('5e2', '3e2', '+'));
        $this->assertEquals($isBcadd ? '3' : 3, $calc->operate(-1512.3, 1515.3, '+'));
        $this->assertNotEquals($isBcadd ? '0' : 0, $calc->operate('3', '0', '+'));

        $this->assertEquals($isBcsub ? '-1' : -1, $calc->operate('7', '8', '-'));
        $this->assertEquals($isBcsub ? '-100' : -100, $calc->operate('0', '100', '-'));
        $this->assertEquals($isBcsub ? '-5.2' : -5.2, $calc->operate(1.38E5, 138005.2, '-'));
        $this->assertNotEquals($isBcsub ? '0' : 0, $calc->operate('17', '15', '-'));

        $this->assertEquals($isBcmul ? '6' : 6, $calc->operate('-2', '-3', '*'));
        $this->assertEquals($isBcmul ? '0' : 0, $calc->operate('0', '70E-100', '*'));
        $this->assertEquals($isBcmul ? '-1056.0174' : -1056.0174, $calc->operate('765.23', -138e-2, '*'));
        $this->assertEquals($isBcmul ? '4560000' : 4560000, $calc->operate('1.52', 30e5, '*'));

        if ($isBcmul) {
            // there should be no trailing zeros
            $this->assertNotEquals('4560000.00', $calc->operate('1.52', 30e5, '*'));
        }

        $this->assertEquals('E_DIVISION_BY_ZERO', $calc->operate('5', '0', '/'));
        $this->assertEquals($isBcdiv ? '0.625' : 0.625, $calc->operate(5, 8, '/'));
        $this->assertEquals($isBcdiv ? '70' : 70, $calc->operate('70', '1', '/'));

        if ($isBcdiv) {
            // there should be no trailing zeros
            $this->assertNotEquals('70.00', $calc->operate('140', '2', '/'));
        }
    }

    public function testProcessCandidateStack () {
        $calc = new RPNCalculator;

        $isBcadd = function_exists('bcadd');
        $isBcsub = function_exists('bcsub');
        $isBcmul = function_exists('bcmul');
        $isBcdiv = function_exists('bcdiv');

        $this->assertEquals('E_DIVISION_BY_ZERO', $this->callMethod($calc, 'processCandidateStack', [['5', '2', '0', '/', '+']]));

        $this->assertEquals([$isBcadd ? '16' : 16, '-', '-', '-'], $this->callMethod($calc, 'processCandidateStack', [['7', '9', '+', '-', '-', '-']]));
        $this->assertEquals([$isBcadd ? '13' : 13], $this->callMethod($calc, 'processCandidateStack', [['5', '8', '+']]));
        $this->assertEquals([$isBcadd ? '0' : 0], $this->callMethod($calc, 'processCandidateStack', [['5', '5', '5', '8', '+', '+', '-', '13', '+']]));
        $this->assertEquals([$isBcadd ? '11' : 11], $this->callMethod($calc, 'processCandidateStack', [['-3', '-2', '*', '5', '+']]));
        $this->assertEquals([$isBcdiv ? '0.625' : 0.625], $this->callMethod($calc, 'processCandidateStack', [['5', '9', '1', '-', '/']]));
    }

}
