<?php
  class Parser {

    /**
     * All Token Types that will be used by both the Parser and Tokenizer
     */
     /** @var int T_BRACES_OPEN   */
     const T_BRACES_OPEN        = 1;
     /** @var int T_BRACES_CLOSE */
     const T_BRACES_CLOSE       = 2;
     /** @var int T_FUNCTION_START */
     const T_FUNCTION_START     = 3;
     /** @var int T_FUNCTION_END */
     const T_FUNCTION_END       = 4;
     /** @var int T_IF */
     const T_IF                 = 5;
     /** @var int T_ELSE */
     const T_ELSE               = 6;
     /** @var int T_EACH */
     const T_EACH               = 7;
     /** @var int T_FIRST */
     const T_FIRST              = 8;
     /** @var int T_LAST */
     const T_LAST               = 9;
     /** @var int T_AT */
     const T_AT                 = 10;
     /** @var int T_ALPHA */
     const T_ALPHA              = 11;
     /** @var int T_NUMERIC */
     const T_NUMERIC            = 12;
     /** @var int T_VARIABLE */
     const T_VARIABLE           = 13;

  }
?>
