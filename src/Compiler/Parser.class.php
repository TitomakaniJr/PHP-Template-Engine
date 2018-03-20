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
              /** Check if there was a matching @ tag */
              self::peakPoundOperator($line_num, $token->offset);
              array_push(self::$function_stack, Token::T_EACH);
              if($j + 2 >= count($tokens)) {
                throw new Exception('Unexpected end on line ' . $line_num);
              }
              if($tokens[$j + 1]->type === Token::T_VARIABLE
                      && $tokens[$j + 2]->type === Token::T_BRACES_CLOSE) {
                /** @var string $data_key The key that will be looped over from $data */
                $data_key = $tokens[$j + 1]->value;
                /** @var string $each_tokens The array of tokens that will be used in every loop */
                $each_tokens = array();
                $j += 3;
                for($i = $i; $i < count($tokenized_file_stream); $i++) {
                  $line_num = $i + 1;
                  array_push($each_tokens, array());
                  $tokens = $tokenized_file_stream[$i];
                  /** parse through the file until a closing /each tag is found */
                  for($j = $j; $j < count($tokens); $j++) {
                    if($j + 3 >= count($tokens)) {
                      throw new Exception('Unexpected end on line ' . $line_num);
                    }
                    $token = $tokens[$j];
                    if($token->type === Token::T_BRACES_OPEN
                          && $tokens[$j + 1]->type === Token::T_FUNCTION_END
                          && $tokens[$j + 2]->type === Token::T_EACH
                          && $tokens[$j + 3]->type === Token::T_BRACES_CLOSE) {
                      array_pop(self::$operator_stack);
                      array_pop(self::$function_stack);
                      $each_tokens = self::parseEach($data_key, $each_tokens, $data);
                      foreach($each_tokens as $output_line){
                        $output[$i] .= $output_line[0];
                      }
                      $j += 3;
                      break 2;
                    } else {
                      array_push($each_tokens[$i], $token);
                    }
                  }
                }
              } else {
                throw new Exception('Expected array variable after each function call on line '
                  . $line_num . ' at position ' . $token->offset);
              }
              break;
            case Token::T_ELSE:
              throw new Exception('else must be used within /unless statement on line '
                . $line_num . ' at position ' . $token->offset);
              break;
            case Token::T_FIRST:
              throw new Exception('First must be used within @each function on line '
                . $line_num . ' at position ' . $token->offset);
              break;
            case Token::T_FUNCTION_END:
              self::popPoundOperator($line_num, $token->offset);
              break;
            case Token::T_FUNCTION_START:
              array_push(self::$operator_stack, Token::T_FUNCTION_START);
              break;
            case Token::T_IF:
              /** @var bool $if_param set to true or false depending on unless param*/
              $if_param = false;
              if(end(self::$operator_stack) === Token::T_FUNCTION_START) {
                if($j + 2 >= count($tokens)) {
                  throw new Exception('Unexpected end on line ' . ++$i);
                }
                self::peakPoundOperator($line_num, $token->offset);
                array_push(self::$function_stack, Token::T_IF);
                /** Get true/false value from parameter */
                $if_param = self::getIfParameter($data, $tokens[$j + 1], $line_num);
                if($tokens[$j + 2]->type !== Token::T_BRACES_CLOSE) {
                  throw new Exception('Expected function close braces on line '
                    . $line_num . ' at position ' . $tokens[$j + 2]->offset);
                }
                /** @var array $if_tokens the array of tokens in the if statement that will be parsed */
                $if_tokens = array();
                /** move token index forward past the param and closing braces */
                $j += 3;
                for($i = $i; $i < count($tokenized_file_stream); $i++) {
                  $line_num = $i + 1;
                  array_push($if_tokens, array());
                  $tokens = $tokenized_file_stream[$i];
                  /** parse through the file until a closing /each tag is found */
                  for($j = $j; $j < count($tokens); $j++) {
                    $token = $tokens[$j];
                    if($j + 4 >= count($tokens)) {
                      throw new Exception('Unexpected end on line ' . $line_num);
                    }
                    /** Check if then upcoming tokens are {{else}} */
                    if($token->type === Token::T_BRACES_OPEN
                          && $tokens[$j + 1]->type === Token::T_ELSE
                          && $tokens[$j + 2]->type === Token::T_BRACES_CLOSE) {
                      $if_param = !$if_param;
                      $j += 2;
                      } else if($token->type === Token::T_BRACES_OPEN
                          && $tokens[$j + 1]->type === Token::T_FUNCTION_END
                          && $tokens[$j + 2]->type === Token::T_IF
                          && $tokens[$j + 3]->type === Token::T_BRACES_CLOSE) {
                      self::popIfFunction($line_num, $tokens[$j]->offset);
                      self::popPoundOperator($line_num, $token->offset);
                      $if_tokens = self::parseTokens($if_tokens, $data);
                      foreach($if_tokens as $output_line){
                        $output[$i] .= $output_line;
                      }
                      /** move token index past the closing {{/unless}} braces */
                      $j += 3;
                      break 2;
                    } else {
                      if($if_param) {
                        array_push($if_tokens[$i], $token);
                      }
                    }
                  }
                }
              } else {
                  throw new Exception('Expected function start # on line '
                    . $line_num . ' at position ' . $tokens[$j]->offset);
              }
              break;
            case Token::T_LAST:
              throw new Exception('Last must be used within @each function on line ' . $line_num .
                ' at position ' . $token->offset);
              break;
            case Token::T_NEW_LINE:
              if($output[$i] != ' ') {
                $output[$i] .= "<br />";
              }
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
                throw new Exception('Unexpected variable on line ' . $line_num .
                  ' at position ' . $token->offset);
              }
              break;
          }
        }
      }
      if(!empty(self::$operator_stack)) {
        throw new Exception('All operators were not consumed properly');
      } else if(!empty(self::$function_stack)) {
        throw new Exception('All functions were not closed properly');
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
      if(end(self::$operator_stack) === Token::T_AT) {
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
      if(!end(self::$operator_stack) === Token::T_FUNCTION_START) {
        throw new Exception('Function call without matching # at line: '
          . $line_num . ' at position ' . $offset);
      }
    }

    /**
     * Pops # from the $operator_stack
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function popPoundOperator(int $line_num, int $offset) {
      if(end(self::$operator_stack) === Token::T_FUNCTION_START) {
        array_pop(self::$operator_stack);
      } else {
        throw new Exception('Function end without matching # at line: '
          . $line_num . ' at position ' . $offset);
      }
    }

    /**
     * Pops unless from the $function_stack
     * @param int $line_num The current line number from the input file
     * @param int $offset The offset from the current token
     * @throws Exception
     */
    private function popIfFunction(int $line_num, int $offset) {
      if(end(self::$function_stack) === Token::T_IF) {
        array_pop(self::$function_stack);
      } else {
        throw new Exception('mismatched /unless at line: '
          . $line_num . ' at position ' . $offset);
      }
    }

    /**
     * Gets the boolean value of the if statement parameter
     * @param array $data Will be used in case the param is linked to data
     * @param Token $token The if param token
     * @param int $line_num The current $line being parsed from the file
     * @return bool if statement param value
     * @throws Exception
     */
    private function getIfParameter(array $data, Token $token, int $line_num): bool {
      /** Check if the param is a value in $data */
      if($token->type === Token::T_VARIABLE) {
        $data_value = $data[$token->value];
        if($data_value === 'true'){
          return true;
        } else if($data_value === 'false') {
          return false;
        } else {
          throw new Exception('Expected boolean after unless on line '
            . $line_num . ' at position ' . $token->offset);
        }
      } else if($token->type === Token::T_TRUE) {
        return true;
      } else if($token->type === Token::T_FALSE) {
        return false;
      } else {
        throw new Exception('Expected variable after unless on line '
          . $line_num . ' at position ' . $token->offset);
      }
    }

    /**
     * Parses $each_tokens for each index in $data[$data_key]
     * @param string $data_key The key that will be used with $data
     * @param array $each_tokens All tokens found between opening and closing each tags
     * @param array $data Array which contains all necessary data for the parser
     * @return array An array of parsed output from $each_tokens for each index in $data[$data_key]
     * @throws Exception
     */
    private function parseEach(string $data_key, array $each_tokens, array $data): array {
      /** @var array $output An array of parsed output */
      $output = array();
      for($i = 0; $i < count($data[$data_key]); $i++) {
        /**
         * $each_tokens gets passed by value to parse on each $data[$data_key] index
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
               * If the token value exists as a key in $data[$data_key] it should
               * be replaced with a T_ALPHANUMERIC token
               */
              if(array_key_exists($line_tokens[$k]->value, $data[$data_key][$i])) {
                /** @var string $var_value The string value form the variable token */
                $var_value = $line_tokens[$k]->value;
                $line_tokens[$k]  = new Token(
                  Token::T_ALPHANUMERIC,
                  $data[$data_key][$i]->$var_value,
                  $line_tokens[$k]->offset
                );
              }
            /**
             * First and last should only be used within @each, replace it with
             * new T_TRUE or T_FALSE token
             */
           } else if($line_tokens[$k]->type === Token::T_AT
                  && $line_tokens[$k + 1]->type === Token::T_LAST) {
              if($i === count($data[$data_key]) - 1) {
                $line_tokens[$k + 1]  = new Token(
                  Token::T_FALSE,
                  'false',
                  $line_tokens[$k]->offset
                );
              } else {
                $line_tokens[$k + 1]  = new Token(
                  Token::T_TRUE,
                  'true',
                  $line_tokens[$k]->offset
                );
              }
              array_splice($line_tokens, $k, 1);
            } else if($line_tokens[$k]->type === Token::T_AT
                  && $line_tokens[$k + 1]->type === Token::T_FIRST) {
              if($i === 0) {
                $line_tokens[$k + 1]  = new Token(
                  Token::T_FALSE,
                  'false',
                  $line_tokens[$k]->offset
                );
              } else {
                $line_tokens[$k + 1]  = new Token(
                  Token::T_TRUE,
                  'true',
                  $line_tokens[$k]->offset
                );
              }
              /** Remove T_AT token which is no longer needed */
              array_splice($line_tokens, $k, 1);
            }
          }
        }
        array_push($output, self::parseTokens($temp_stream, $data));
      }
      return $output;
    }
  }
?>
