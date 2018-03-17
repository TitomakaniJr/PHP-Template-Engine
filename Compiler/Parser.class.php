<?php
  class Parser {

    /**
     * All Token Types that will be used by both the Parser and Tokenizer
     */
     const T_BRACES_OPEN        = 1;
     const T_BRACES_CLOSE       = 2;
     const T_FUNCTION_START     = 3;
     const T_FUNCTION_END       = 4;
     const T_IF                 = 5;
     const T_ELSE               = 6;
     const T_EACH               = 7;
     const T_FIRST              = 8;
     const T_LAST               = 9;
     const T_AT                 = 10;
     const T_ALPHA              = 11;
     const T_NUMERIC            = 12;
     const T_VARIABLE           = 13;

  }
?>
