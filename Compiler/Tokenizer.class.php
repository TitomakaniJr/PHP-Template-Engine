
<?php
  class Tokenizer {

    /**
     * @static
     * @var array $operators All operator strings that will be searched for while tokenizing the input
     */
    private static $operators = array(
      '{{'        => Parser::T_BRACES_OPEN,
      '}}'        => Parser::T_BRACES_CLOSE,
      '#'         => Parser::T_FUNCTION_START,
      '@'         => Parser::T_AT,
    );

    /**
     * @static
     * @var array $keywords All keyword strings that will be searched for while tokenizing the input
     */
    private static $keywords = array(
      'unless'     => Parser::T_IF,
      'else'       => Parser::T_ELSE,
      'each'       => Parser::T_EACH,
      'first'      => Parser::T_FIRST,
      'last'       => Parser::T_LAST,
    );

    /**
     * @static
     * @var array $regex All regular expressions that will be used in tokenizer
     */
    private static $regex = array(
      '/[0-9]/',
      '/[\{\}\#\@]/',
      '/[a-zA-Z]/'
    );

    /**
     * All constants that correspond to a specific regex
     */

    /** @var int RE_NUMERIC */
    const RE_NUMERIC       = 0;
    /** @var int RE_OPERATORS */
    const RE_OPERATORS     = 1;
    /** @var int RE_ALPHA */
    const RE_ALPHA         = 2;

    /**
     * Creates an array of tokens from the input line
     * @param string $line The string that will be tokenized
     */
    public function tokenize(string $line) {
      /** @var bool $search_keywords Determines if we are looking for keywords or strings */
      $search_keywords = false;
      /** @var int $offset Current position in the input line */
      $offset = 0;
      /** @var int $len Length of input line*/
      $len = strlen($line);
      /** @var array $tokens An array of tokens that will be used by the parser */
      $tokens = array();

      for($i = 0; $i < $len; $i++) {
        /** @var Token $token An empty object that will be used to add new token to token array */
        $token = NULL;
        $offset = $i;
        // Use $regex to search for a match on the current character in $line
        if(preg_match(self::$regex[self::RE_NUMERIC], $line[$i])) {
          $token = self::tokenizeNumeric($line, $i, $len);
          array_push($tokens, $token);
        } else if(preg_match(self::$regex[self::RE_OPERATORS], $line[$i])) {
          /**
           * Compare each operator in $operators to the current character in $line
           * @var string $value The operator string
           * @var string $type The operator type from Parser
           */
          foreach(self::$operators as $value => $type) {
            /** @var int $op_len Length of the operator we are comparing */
            $op_len = strlen($value);
            if(substr_compare($line, $value, $i, $op_len) == 0){
              $token = new Token($type, $value, $i);
              array_push($tokens, $token);
              $i += $op_len - 1;
              /** If we have found the opening or close funtion braces, update $search_keywords */
              if($value == '{{') {
                $search_keywords = true;
              } else if($value == '}}'){
                $search_keywords = false;
              }
            }
          }
        } else if(preg_match(self::$regex[self::RE_ALPHA], $line[$i])) {
          if($search_keywords){
            /**
             * Compare each keyword in $keywords to the current character in $line
             * @var string $value The keyword string
             * @var string $type The keyword type from Parser
             */
            foreach(self::$keywords as $value => $type) {
              /** @var int $op_len Length of the operator we are comparing */
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
        }
      }
      print_r($tokens) . '<br/>';
    }

    /**
     * Creates a T_NUMERIC token from the input string
     * @access public
     * @param string $line The string that will be tokenized
     * @param int $i The index at which the number starts in $line
     * @param int $len The length of the $line
     * @return Token The resulting T_NUMERIC token
     * @throws Exception If the number is not formed correctly, 1..234
     */
    function tokenizeNumeric(string $line, int &$i, int $len): Token {
      /** @var string $value Holds the full number that will be tokenized */
      $value = $line[$i++];

      /** @var bool $has_dot Used in the case of a float */
      $has_dot = false;

      // Parse string starting from $i to find the full number
      for($j = $i; $j < $len; $j++){
        if(preg_match(self::$regex[self::RE_NUMERIC], $line[$j])) {
          // Concat found number to $value
          $value .= $line[$j];
        } else if($line[$j] === '.') {
          if(!$has_dot) {
            // The number is a float
            $has_dot = true;
            $value .= $line[$j];
          } else {
            throw new Exception('Improperly formed number starting at position ' . $i);
          }
        } else { // Non-numeric character found, full number is in $value
          // Update $i to match current offset
          $i = $j;
          break;
        }
      }
      return new Token(Parser::T_NUMERIC, $value, $i);
    }
  }
?>
