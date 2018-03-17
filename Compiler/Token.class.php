<?php

  /**
    * Token class that will be used by the Tokenizer
    */
  class Token {
    /**
     * The type of the token, i.e. T_ALPHA
     * @var int
     */
    public $type;

    /**
     * The string associated with this token
     * @var string
     */
    public $value;

    /**
     * The offset amount of this token in it's original string
     * @var int
     */
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
