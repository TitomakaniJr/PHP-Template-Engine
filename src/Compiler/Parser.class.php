<?php
  class Parser {

    /**
     * Array that will be used to track function calls from the template
     * Error will be thrown if functions are not matched with closing tags, i.e. #unless -> /unless
     * @var array $function_stack
     */
    private static $function_stack = array();
    /**
     * Array that will be used to match up certain operators from the template
     * Error will be thrown if operators are not matched
     * @var array $operator_stack
     */
    private static $operator_stack = array();

    /**
     * Creates an array of tokens from the input line
     * @param array $tokenized_file_stream The array of file tokens from the tokenizer
     * @param array $data Array which contains all necessary data for the parser
     * @return array The output which was parsed from the tokens
     * @throws Exception
     */
    public function parseTokens(array $tokenized_file_stream, array $data): array {
      /** @var array $output An array of parsed output */
      $output = array();
      /**
       * Used by the parser to determine if we should be searching for function code
       * @var bool $in_func_braces
       */
      $in_func_braces = false;

      /** @var int $i The line index number */
      for($i = 0; $i < count($tokenized_file_stream); $i++) {
        /** @var int $line_num The current line number being parsed from the input file */
        $line_num = $i + 1;
        array_push($output, '');
        /** @var array $tokens The tokens of the current line from the input file */
        $tokens = $tokenized_file_stream[$i];
        /** @var int $j The current token index being parsed */
        for($j = 0; $j < count($tokens); $j++) {
          /** @var Token $token The current token being parsed */
          $token = $tokens[$j];
          switch($token->type) {
            case Token::T_ALPHANUMERIC:
              $output[$i] .= $token->value;
              break;
            case Token::T_AT:
              array_push(self::$operator_stack, Token::T_AT);
              break;
            case Token::T_BRACES_CLOSE:
              self::setFunctionBraces($in_func_braces, false, $line_num, $token->offset);
              break;
            case Token::T_BRACES_OPEN:
              self::setFunctionBraces($in_func_braces, true, $line_num, $token->offset);
              break;
            case Token::T_EACH:
              if(current(self::$function_stack) !== Token::T_EACH){
                /** Check if there was a matching @ tag */
                self::peakPoundOperator($line_num, $token->offset);
                array_push(self::$function_stack, Token::T_EACH);
                if($tokens[$j + 1]->type === Token::T_VARIABLE && $tokens[$j + 2]->type === Token::T_BRACES_CLOSE) {
                  /** @var string $each_var The key that will be looped over from $data */
                  $each_var = $tokens[$j + 1]->value;
                  /** @var string $each_tokens The array of tokens that will be used in every loop */
                  $each_tokens = array();
                  $j += 3;
                  for($i = $i; $i < count($tokenized_file_stream); $i++) {
                    $line_num = $i + 1;
                    array_push($each_tokens, array());
                    $tokens = $tokenized_file_stream[$i];
                    /** parse through the file until a closing /each tag is found */
                    for($j = $j; $j < count($tokens); $j++) {
                      $token = $tokens[$j];
                      if($token->type === Token::T_BRACES_OPEN
                        && $tokens[$j + 1]->type === Token::T_FUNCTION_END
                        && $tokens[$j + 2]->type === Token::T_EACH) {
                        $each_tokens = self::parseEach($each_var, $each_tokens, $data);
                        foreach($each_tokens as $output_line){
                          array_push($output, $output_line);
                        }
                        break;
                      } else {
                        array_push($each_tokens[$i], $token);
                      }
                    }
                  }
                } else {
                  throw new Exception('Expected array variable after each function call on line '
                    . $line_num . ' at position ' . $token->offset);
                }
              } else {
                //END OF EACH
              }
              break;
            case Token::T_ELSE:
              self::peakIfFunction($line_num, $token->offset);
              break;
            case Token::T_FIRST:
              self::peakPoundOperator($line_num, $token->offset);
              break;
            case Token::T_FUNCTION_END:
              self::popPoundOperator($line_num, $token->offset);
              break;
            case Token::T_FUNCTION_START:
              if(empty(self::$operator_stack)) {
                array_push(self::$operator_stack, Token::T_FUNCTION_START);
              } else {
                throw new Exception('Unexpected function start on line '
                  . $line_num . ' at position ' . $token->offset);
              }
              break;
            case Token::T_IF:
              if(current(self::$operator_stack) === Token::T_FUNCTION_START) {
                array_pop($operator_stack);
                if($j + 2 >= count($tokens)) {
                  throw new Exception('Unexpected end on line ' . ++$i);
                }
                if($tokens[$j + 1]->type === Token::T_VARIABLE) {

                } else if ($tokens[$j + 1]->type === Token::T_AT) {
                  if(current(self::$function_stack['type']) === Token::T_EACH) {

                  } else {
                    throw new Exception('@ modifiers must be used withing and each loop on line '
                      . $line_num . ' at position ' . $tokens[$j + 1]->offset);
                  }
                } else {
                  throw new Exception('Expected variable after unless on line '
                    . $line_num . ' at position ' . $tokens[$j + 1]->offset);
                }
              } else if(current(self::$operator_stack) === Token::T_FUNCTION_END) {
                array_pop(self::$operator_stack);
                self::popIfFunction($line_num, $token->offset);
                if($tokens[$j + 1]->type !== Token::T_BRACES_CLOSE){
                  throw new Exception('Unexpected function start on line '
                    . $line_num . ' at position ' . $tokens[$j + 1]->offset);
                }
              }
              break;
            case Token::T_LAST:
              self::popAtOperator($line_num, $token->offset);
              break;
            case Token::T_NUMERIC:
              break;
            case Token::T_SPACE:
              $output[$i] .= ' ';
              break;
            case Token::T_VARIABLE:
              if((empty(self::$operator_stack) && empty(self::$function_stack))
                || (!empty(self::$operator_stack) && !empty(self::$function_stack))) {
                  $output[$i] .= $data[$token->value];
              } else {
                throw new Exception('Unexpected variable on line ' . ++$i .
                  ' at position ' . $token->offset);
              }
              break;
          }
        }
      }
      return $output;
    }

    /**
     * Sets $in_func_braces to $value
     * @param bool $in_func_braces Reference to $in_func_braces
     * @param bool $value The value which $in_func_braces will be set to
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function setFunctionBraces(bool &$in_func_braces, bool $value, int $line_num, int $offset) {
      if($in_func_braces !== $value) {
        $in_func_braces = $value;
      } else {
        throw new Exception('Mismatched Function Braces on line: '
          . $line_num . ' at position ' . $offset);
      }
    }

    /**
     * Pops @ from the $operator_stack
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function popAtOperator(int $line_num, int $offset) {
      if(current(self::$operator_stack) === Token::T_AT) {
        array_pop(self::$operator_stack);
      } else {
        throw new Exception('Function call without matching @ at line: '
          . $line_num . ' at position ' . $token->offset);
      }
    }

    /**
     * Peaks at the $operator_stack to check for #
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function peakPoundOperator(int $line_num, int $offset) {
      if(!current(self::$operator_stack) === Token::T_FUNCTION_START) {
        throw new Exception('Function call without matching # at line: '
          . $line_num . ' at position ' . $token->offset);
      }
    }

    /**
     * Pops # from the $operator_stack
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function popPoundOperator(int $line_num, int $offset) {
      if(current(self::$operator_stack) === Token::T_FUNCTION_START) {
        array_pop(self::$operator_stack);
      } else {
        throw new Exception('Function end without matching # at line: '
          . $line_num . ' at position ' . $token->offset);
      }
    }

    /**
     * Peaks at the $function_stack to check for unless
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function peakIfFunction(int $line_num, int $offset) {
      if(!current(self::$function_stack) === Token::T_IF) {
        throw new Exception('else without matching unless at line: '
          . $line_num . ' at position ' . $token->offset);
      }
    }

    /**
     * Pops unless from the $function_stack
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function popIfFunction(int $line_num, int $offset) {
      if(current(self::$function_stack) === Token::T_IF) {
        array_pop(self::$function_stack);
      } else {
        throw new Exception('mismatched /unless at line: '
          . $line_num . ' at position ' . $token->offset);
      }
    }

    /**
     * Parses $each_tokens for each index in $data[$each_var]
     * @param string $each_var The key that will be used with $data
     * @param array $each_tokens All tokens found between opening and closing each tags
     * @param array $data Array which contains all necessary data for the parser
     * @return array An array of parsed output from $each_tokens for each index in $data[$each_var]
     * @throws Exception
     */
    private function parseEach(string $each_var, array $each_tokens, array $data): array {
      /** @var array $output An array of parsed output */
      $output = array();
      for($i = 0; $i < count($data[$each_var]); $i++) {
        /**
         * $each_tokens gets passed by value to parse on each $data[$each_var] index
         * @var array $temp_stream
         */
        $temp_stream = $each_tokens;
        for($j = 0; $j < count($temp_stream); $j++) {
          /** @var array $line_tokens The tokens for the current line in $temp_stream */
          $line_tokens = &$temp_stream[$j];
          for($k = 0; $k < count($line_tokens); $k++){
            /** Check if we are parsing a variable that does not already have a linked object */
            if($line_tokens[$k]->type === Token::T_VARIABLE && $line_tokens[$k - 1]->type !== Token::T_ARROW) {
              /**
               * If the token value exists as a key in $data[$each_var] it should
               * be replaced with a T_ALPHANUMERIC token
               */
              if(array_key_exists($line_tokens[$k]->value, $data[$each_var][$i])) {
                /** @var string $var_value The string value form the variable token */
                $var_value = $line_tokens[$k]->value;
                $line_tokens[$k]  = new Token(
                  Token::T_ALPHANUMERIC,
                  $data[$each_var][$i]->$var_value,
                  $line_tokens[$k]->offset
                );
              }
            /**
             * First and last should only be used withing @each, update its
             * value to be used in the parser
             */
            } else if($line_tokens[$k]->type === Token::T_FIRST) {
              if($i === count($each_var) - 1) {
                $line_tokens[$k]->value = 'true';
              } else {
                $line_tokens[$k]->value = 'false';
              }
            } else if($line_tokens[$k]->type === Token::T_LAST) {
              if($i === 0) {
                $line_tokens[$k]->value = 'true';
              } else {
                $line_tokens[$k]->value = 'false';
              }
            }
          }
        }
        array_push($output, self::parseTokens($temp_stream, $data));
      }
      return $output;
    }
  }
?>
