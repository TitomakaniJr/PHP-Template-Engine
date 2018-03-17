
<?php
  class Tokenizer {

    /**
     * All operator strings that will be searched for while tokenizing the input
     * @static
     * @var array
     */
    private static $operators = array(
      '{{'        => Parser::T_BRACES_OPEN,
      '}}'        => Parser::T_BRACES_CLOSE,
      '#'         => Parser::T_FUNCTION_START,
      '@'         => Parser::T_AT,
    );

    /**
     * All keyword strings that will be searched for while tokenizing the input
     * @static
     * @var array
     */
    private static $keywords = array(
      'unless'     => Parser::T_IF,
      'else'       => Parser::T_ELSE,
      'each'       => Parser::T_EACH,
      'first'      => Parser::T_FIRST,
      'last'       => Parser::T_LAST,
    );

    /**
     * Creates an array of tokens from the input line
     * @param string the string that will be tokenized
     */
    public function tokenize(string $line) {
      /**
       * Bool that determines if we are looking for keywords, or strings
       * Will be set true if the Tokenizer finds opening function braces: '{{'
       * @var bool
       */
      $search_keywords = false;

      /** @var int */
      $offset = 0;

      /** @var int */
      $len = strlen($line);

      /**
       * An array of tokens that will be used by the parser
       * @var array
       */
      $tokens = array();

      for($i = 0; $i < $len; $i++) {
        $offset = $i;

        /** @var Token */
        $token = NULL;

        switch($line[$i]) {
          case '.':
            break;
          //Check if
          case '0': case '1': case '2': case '3': case '4':
          case '5': case '6': case '7': case '8': case '9':
            $token = self::tokenizeNumeric($line, $i, $len);
            array_push($tokens, $token);
            break;
          case '{': case '}': case '#': case '@':
            foreach(self::$operators as $value => $type) {
              $op_len = strlen($value);
              if(substr_compare($line, $value, $i, $op_len) == 0){
                $token = new Token($type, $value, $i);
                array_push($tokens, $token);
                $i += $op_len - 1;
                if($value == '{{') {
                  $search_keywords = true;
                } else if($value == '}}'){
                  $search_keywords = false;
                }
                break;
              }
            }
            break;
          default:
            if($search_keywords){
              foreach(self::$keywords as $value => $type) {
                $op_len = strlen($value);
                if(substr_compare($line, $value, $i, $op_len) == 0){
                  $token = new Token($type, $value, $i);
                  array_push($tokens, $token);
                  $i += $op_len - 1;
                  break;
                }
              }
            } else {

            }
          break;
        }
      }
      print_r($tokens) . '<br/>';
    }

    /**
     * Creates a T_NUMERIC token from the input string
     * @access public
     * @param string the string that will be tokenized
     * @param int the index at which the number starts in $line
     * @param int the length of the $line
     * @return Token the resulting T_NUMERIC token
     * @throws Exception if the number is not formed correctly, 1..234
     */
    function tokenizeNumeric(string $line, int &$i, int $len): Token {
      $value = $line[$i++];
      $has_dot = false;
      for($j = $i; $j < $len; $j++){
        switch($line[$j]){
          case '.':
            if($has_dot) {
              throw new Exception('Improperly formed number starting at position ' . $i);
              break;
            } else {
              $has_dot = true;
              $value .= $line[$j];
              break;
            }
          case '0': case '1': case '2': case '3': case '4':
          case '5': case '6': case '7': case '8': case '9':
            $value .= $line[$j];
            break;
          default:
            $i = $j;
            break 2;
        }
      }
      return new Token(Parser::T_NUMERIC, $value, $i);
    }
  }
?>
