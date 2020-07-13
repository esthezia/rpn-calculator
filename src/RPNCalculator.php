<?php

/**
 * Command-line Reverse Polish Notation (RPN) calculator
 *
 * Note: Requires PHP v7.3.*
 *
 * @uses "BCMath" PHP extension (usually enabled by default).
 *       if the extension doesn't exist for some reason,
 *       the script falls back to PHP's native arithmetic operators.
 * @link https://en.wikipedia.org/wiki/Reverse_Polish_notation
 */

namespace RPNCalculator;

class RPNCalculator {

    /**
     * 'bcmath' functions' minimum number of decimals to use
     * (if the user inputs numbers with more than this number of decimals,
     * or the result is likely to exceed this number of decimals,
     * the script will honor that and not limit them, which means
     * that memory could be exceeded due to user's input.)
     * (PHP's 'float' has apx. 14 decimals, commonly,
     * so don't limit more than the native solution.
     * (https://www.php.net/manual/en/language.types.float.php))
     *
     * @var (int)
     */
    public const PRECISION = 14;

    /**
     * The operators currently implemented
     *
     * @var (array)
     */
    public const OPERATORS = ['+', '-', '*', '/'];

    /**
     * @var (string)
     */
    protected const PROMPT = '> ';

    /**
     * The current stack
     *
     * @var (array)
     */
    private $stack = [];

    /**
     * Get user input
     *
     * Note: Separate function, in case
     * different input source or input processing
     * will want to be used in the future.
     *
     * @return (string)
     */
    protected function getInput () : string {
        return fgets(STDIN);
    }

    /**
     * Output something to the user
     *
     * Note: Separate function, in case different
     * output destination will want to be used in the future.
     *
     * @param (mixed) $output - it will be converted to string
     * @return (null|false|int) - 'null' if there's nothing to be displayed,
     *                            'false' if write was unsuccessful,
     *                            the number of bytes written, otherwise.
     */
    protected function output ($output = null) {
        $output = (string) $output;

        if (!strlen($output)) {
            // nothing to output...
            return;
        }

        return fwrite(STDOUT, $output);
    }

    /**
     * Check if the provided input is valid,
     * i.e. is numeric (exponential notation allowed), or an operator.
     */
    protected function isValidInput (array $candidateStack = []) : bool {
        if (empty($candidateStack)) {
            return true;
        }

        // make sure the first two tokens in the stack are numerical,
        // as in RPN this is required.
        // (we expect exactly two operands for each operation)
        if (
            ((count($candidateStack) >= 1) && !is_numeric($candidateStack[0])) ||
            ((count($candidateStack) >= 2) && !is_numeric($candidateStack[1]))
        ) {
            return false;
        }

        if (count($candidateStack) > 2) {
            // we checked the first two, above, so don't check them again
            for ($i = 2; $i < count($candidateStack); $i++) {
                if (!is_numeric($candidateStack[$i]) && !in_array($candidateStack[$i], static::OPERATORS)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Do a single operation
     *
     * @return (string|float) one of 'E_DIVISION_BY_ZERO', 'E_INVALID_OPERATOR', 'E_INVALID_OPERAND' in case of an error,
     *                        the result as a string, if 'bcmath' PHP extension is enabled,
     *                        and 'float' otherwise.
     */
    public function operate ($operand1 = null, $operand2 = null, $operator = null) {
        if (empty($operator) || !in_array($operator, static::OPERATORS)) {
            return 'E_INVALID_OPERATOR';
        }

        if (!is_numeric($operand1) || !is_numeric($operand2)) {
            return 'E_INVALID_OPERAND';
        }

        $sOperand1 = (string) $operand1;
        $sOperand2 = (string) $operand2;

        // if operands use exponential notation, convert them to 'float',
        // so the 'bcmath' functions will handle them correctly,
        // otherwise, leave them as-is, for maximum precision (in case they're big numbers).
        // ('e' cannot be in the first position, so the 'strpos' check is ok)
        if (strpos(strtolower($sOperand1), 'e')) {
            $sOperand1 = (string) (float) $operand1;
        }
        if (strpos(strtolower($sOperand2), 'e')) {
            $sOperand2 = (string) (float) $operand2;
        }

        $result = null;
        $precision = static::PRECISION;

        switch ($operator) {
            case '+':
                if (function_exists('bcadd')) {
                    if ((strpos($sOperand1, '.') !== false) || (strpos($sOperand2, '.') !== false)) {
                        $precision = max($precision, strlen(@explode('.', $sOperand1)[1]), strlen(@explode('.', $sOperand2)[1])) + 1;
                    }

                    $result = bcadd($sOperand1, $sOperand2, $precision);
                } else {
                    $result = (float) $operand1 + (float) $operand2;
                }

                break;
            case '-':
                if (function_exists('bcsub')) {
                    if ((strpos($sOperand1, '.') !== false) || (strpos($sOperand2, '.') !== false)) {
                        $precision = max($precision, strlen(@explode('.', $sOperand1)[1]), strlen(@explode('.', $sOperand2)[1]));
                    }

                    $result = bcsub($sOperand1, $sOperand2, $precision);
                } else {
                    $result = (float) $operand1 - (float) $operand2;
                }

                break;
            case '*':
                if (function_exists('bcmul')) {
                    if ((strpos($sOperand1, '.') !== false) || (strpos($sOperand2, '.') !== false)) {
                        $precision = max($precision, strlen(@explode('.', $sOperand1)[1]) + strlen(@explode('.', $sOperand2)[1]));
                    }

                    $result = bcmul($sOperand1, $sOperand2, $precision);
                } else {
                    $result = (float) $operand1 * (float) $operand2;
                }

                break;
            case '/':
                if (!(float) $operand2) {
                    $result = 'E_DIVISION_BY_ZERO';
                    break;
                }

                if (function_exists('bcdiv')) {
                    $result = bcdiv($sOperand1, $sOperand2, $precision);
                } else {
                    $result = (float) $operand1 / (float) $operand2;
                }

                break;
        }

        // trim trailing decimal zeros
        if (is_numeric($result) && (strpos($result, '.') !== false)) {
            $result = rtrim($result, '0');
            $result = rtrim($result, '.'); // maybe it didn't actually have a decimal part
        }

        return $result;
    }

    /**
     * Process a candidate stack
     *
     * (Note: If the candidate stack will prove valid, i.e. gives a valid result,
     * we'll adopt it as the current stack, later on.)
     *
     * @param (array) $s - the candidate stack to process
     * @return (array|'E_DIVISION_BY_ZERO') the result, or 'E_DIVISION_BY_ZERO'.
     */
    protected function processCandidateStack (array $s = []) {
        if (empty($s)) {
            return $s;
        }

        $result = [];

        foreach ($s as $i => $v) {
            if (is_numeric($v)) {
                $result[] = $v;
                continue;
            }

            // operator encountered, so operate

            // don't remove the elements from the array just yet,
            // as we may need them later
            $operand2 = @$result[count($result) - 1];
            $operand1 = @$result[count($result) - 2];

            $r = $this->operate($operand1, $operand2, $v);

            if ($r === 'E_DIVISION_BY_ZERO') {
                return $r;
            }

            if (($r === 'E_INVALID_OPERAND') || ($r === 'E_INVALID_OPERATOR')) {
                return array_merge($result, array_slice($s, $i));
            }

            // now we can remove the old operands from the array
            array_pop($result);
            array_pop($result);

            $result[] = $r;
        }

        return $result;
    }

    /**
     * Read and process the user's input
     */
    protected function readAndProcess () {
        // $this->getInput() must be within the while's condition, cannot be taken outside.
        while ($line = trim($this->getInput())) {
            if ($line === 'q') {
                exit();
            }

            $candidateStack = (!empty($this->stack) ? implode(' ', $this->stack) . ' ' : '') . $line;
            // ignore multiple spaces between parts of the input, as they are harmless
            // (but keep the values of "0")
            $candidateStack = array_values(array_filter(explode(' ', $candidateStack), 'strlen'));

            if (!$this->isValidInput($candidateStack)) {
                $output = PHP_EOL . 'Invalid input.' . PHP_EOL . PHP_EOL .
                          'Only allowed:' . PHP_EOL .
                          '- numbers (integers or floats, positive or negative, exponential part allowed)' . PHP_EOL .
                          '- operators (' . implode(', ', static::OPERATORS) . ')' . PHP_EOL .
                          '- spaces' . PHP_EOL . PHP_EOL .
                          'Please also make sure that each operation has exactly two operands (both operands should precede their operator).';
            } else {
                if (is_numeric($line)) {
                    // valid input, we can adopt the candidate stack
                    $this->stack = $candidateStack;

                    $output = $line;
                } else {
                    // don't adopt the candidate stack just yet,
                    // but only after processing it and seeing we've got a valid result,
                    // which means the input was valid.
                    // (valid result = there are no operators without operands.
                    // if there are more operands than operators, we expect more user input.)

                    $candidateStack = $this->processCandidateStack($candidateStack);

                    if ($candidateStack === 'E_DIVISION_BY_ZERO') {
                        $output = PHP_EOL . 'Division by zero encountered. We\'re starting over. Please type in your input.';

                        // reset the stack and start over, so the user knows where they stand
                        $this->stack = [];
                    } else {
                        $isOperatorsLeft = !!count(array_intersect($candidateStack, static::OPERATORS));

                        if ($isOperatorsLeft) {
                            $output = PHP_EOL . 'Invalid input: too many operations, not enough operands.';
                            $output .= PHP_EOL . 'Please make sure that each operation has exactly two operands (both operands should precede their operator).';
                        } else {
                            // we can adopt the candidate stack now
                            $this->stack = $candidateStack;

                            $output = $this->stack[count($this->stack) - 1];
                        }
                    }
                }
            }

            if (!empty($this->stack)) {
                $output .= PHP_EOL . PHP_EOL . 'Your current expression: ' . implode(' ', $this->stack) . '.';
            }

            $output .= PHP_EOL . PHP_EOL . static::PROMPT;

            $this->output($output);
        }
    }

    public function init () {
        // display welcoming message and instructions, to the user
        $output = PHP_EOL . '--------------------------------------------------';
        $output .= PHP_EOL . 'Welcome to our command-line Reverse Polish Notation calculator!';
        $output .= PHP_EOL . PHP_EOL . 'Type in what you want calculated (in Reverse Polish Notation) and press "enter" to see the result. E.g.: 5 9 1 - +.';
        $output .= PHP_EOL . PHP_EOL . 'Supported operations: ' . implode(', ', static::OPERATORS) . '.';
        $output .= PHP_EOL . PHP_EOL . '--------------------------------------------------';
        $output .= PHP_EOL . PHP_EOL . 'To exit, type "q" and press "enter", or just press "enter".' . PHP_EOL . PHP_EOL;

        // begin
        $output .= static::PROMPT;

        $this->output($output);

        $this->readAndProcess();
    }

}
