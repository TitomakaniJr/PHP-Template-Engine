<?php

  class Stuff {

    /** @var int $Thing The 'Thing' associated with this 'Stuff' */
    public $Thing;
    /** @var string $Desc The string description associated with this 'Stuff' */
    public $Desc;

    /**
     * Creates a new Token from the data sent by Tokenizer
     * @param string $type the type of token, i.e. T_NUMERIC
     * @param string $value the value associated with this Token
     * @param int $offset the offset of this token it's original string
     */
    public function __construct(string $Thing, string $Desc) {
      $this->Thing = $Thing;
      $this->Desc = $Desc;
    }
  }

?>
