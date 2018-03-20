<?php

  /**
    * Token class that will be used by the Tokenizer
    */
  class Token {

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
     const T_ALPHANUMERIC       = 11;
     /** @var int T_NUMERIC */
     const T_NUMERIC            = 12;
     /** @var int T_VARIABLE */
     const T_VARIABLE           = 13;
     /** @var int T_ARROW */
     const T_ARROW              = 14;
     /** @var int T_TRUE */
     const T_TRUE              = 15;
     /** @var int T_FALSE */
     const T_FALSE             = 16;
     /** @var int T_SPACE */
     const T_SPACE             = 17;
     /** @var int T_NEW_LINE */
     const T_NEW_LINE          = 18;

    /** @var int $type The type of the token, i.e. T_ALPHA */
    public $type;
    /** @var string $value The string associated with this token */
    public $value;
    /** @var int $offset The offset amount of this token in it's original string */
    public $offset;

    /**
     * Creates a new Token from the data sent by Tokenizer
     * @param int $type the type of token, i.e. T_NUMERIC
     * @param string $value the value associated with this Token
     * @param int $offset the offset of this token it's original string
     */
    public function __construct(int $type, string $value, int $offset) {
      $this->type = $type;
      $this->value = $value;
      $this->offset = $offset;
    }
  }
?>
