# A command-line Reverse Polish Notation (RPN) Calculator

**Reverse Polish notation (RPN)** is a mathematical notation in which operators _follow_ their operands. For instance, to add 3 and 4, one would write **3 4 +** rather than **3 + 4**.
([https://en.wikipedia.org/wiki/Reverse_Polish_notation](https://en.wikipedia.org/wiki/Reverse_Polish_notation))

This is a custom implementation of the algorithm to evaluate RPN expressions, as a PHP command-line script.

### Requirements

* PHP 7.3.*
* BCMath PHP extension (if missing, the script will fall back to PHP's native arithmetical operators)

> **Notes:**
>
> 1. I chose the minimum PHP version of 7.3, so all the BCMath functions for the implemented operators will behave the same (see **Changelog** at [https://www.php.net/manual/en/function.bcmul.php](https://www.php.net/manual/en/function.bcmul.php)).
> 2. I used the BCMath functions for increased arithmetical precision, given this is a calculator, for which accurate and precise results are paramount.
> 3. I chose a precision of 4 decimals for the BCMath functions, but if the user inputs numbers with more than 4 decimals, the script will honor that and aim to give the user as high precision of a result as possible.

### Operations implemented

1. Addition (+)
2. Subtraction (-)
3. Multiplication (*)
4. Division (/)

### Approach

* The user can input the whole expression at once, or just parts of it and continue adding to it.
* If the user inputs more operators than operands, the script will disregard the input that invalidates the expression.
* If the user inputs more operands than operators, the script will accept that and will wait for more user input (if the operands are at the beginning, and the expression could still be valid).

### Usage

```bash
$ php index.php
```

### Examples

```bash
> 5 5 5 8 + + -
-13
> 13 +
0
```
---
```bash
> 5
5
> 8
8
> +
13
```
---
```bash
> -3
-3
> -2
-2
> *
6
> 5
5
> +
11
```
---
```bash
> 5
5
> 9
9
> 1
1
> -
8
> /
0.625
```

### To do

* Add more operations

### In closing

Please feel free to submit any feedback or feature requests!

Enjoy! :)
