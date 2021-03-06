
<?php
  class Tokenizer {

    /**
     * @static
     * @var array $operators All operator strings that will be searched for while tokenizing the input
     */
    private static $operators = array(
      '{{'        => Token::T_BRACES_OPEN,
      '}}'        => Token::T_BRACES_CLOSE,
      '#'         => Token::T_FUNCTION_START,
      '/'         => Token::T_FUNCTION_END,
      '@'         => Token::T_AT,
      '->'        => Token::T_ARROW
    );

    /**
     * @static
     * @var array $keywords All keyword strings that will be searched for while tokenizing the input
     */
    private static $keywords = array(
      'unless'     => Token::T_IF,
      'else'       => Token::T_ELSE,
      'each'       => Token::T_EACH,
      'first'      => Token::T_FIRST,
      'last'       => Token::T_LAST,
      'true'       => Token::T_TRUE,
      'false'      => Token::T_FALSE
    );

    /**
     * @static
     * @var array $regex All regular expressions that will be used in tokenizer
     */
    private static $regex = array(
      '/[0-9]/',
      '/[\{\}\#\@\/(->)]/',
      '/[a-zA-Z]/',
      '/[^\s^\{]/',
      '/\s/'
    );

    /**
     * All constants that correspond to a specific regex
     */

    /** @var int RE_NUMERIC */
    const RE_NUMERIC          = 0;
    /** @var int RE_OPERATORS */
    const RE_OPERATORS        = 1;
    /** @var int RE_ALPHA */
    const RE_ALPHA            = 2;
    /** @var int RE_ALPHANUMERIC */
    const RE_ALPHANUMERIC     = 3;
    /** @var int RE_SPACE */
    const RE_SPACE            = 4;

    /**
     * Creates an array of tokens from the input line
     * @param string $file_path The path to the file that will be read and tokenized
     * @return array An array of tokens that were made from the input
     */
    public function tokenize(string $file_path): array {
      /** @var bool $search_keywords Determines if we are looking for keywords or strings */
      $search_keywords = false;
      $file = fopen($file_path, 'r');
      /** @var array $tokens An array of tokens that will be used by the parser */
      $tokens = array();
      if($file) {
        while(($line = fgets($file)) !== false) {
          /** @var int $offset Current position in the input line */
          $offset = 0;
          /** @var int $len Length of input line*/
          $len = strlen($line);

          for($i = 0; $i < $len; $i++) {
            /** @var Token $token An empty object that will be used to add new token to token array */
            $token = NULL;
            $offset = $i;
            /** Use $regex to search for a match on the current character in $line */
            if(preg_match(self::$regex[self::RE_OPERATORS], $line[$i])) {
              if(!$search_keywords && $line[$i] !== '{'){
                $token = self::tokenizeAlpha($line, $i, $len, false);
              } else {
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
                    $i += $op_len - 1;
                    /** If we have found the opening or close funtion braces, update $search_keywords */
                    if($value == '{{') {
                      $search_keywords = true;
                    } else if($value == '}}'){
                      $search_keywords = false;
                    }
                  }
                }
              }
            /** If {{ has not been found we should tokenize as a string of Alphanumerical characters */
            } else if (!$search_keywords && preg_match(self::$regex[self::RE_ALPHANUMERIC], $line[$i])) {
              $token = self::tokenizeAlpha($line, $i, $len, false);
            } else if(preg_match(self::$regex[self::RE_NUMERIC], $line[$i])) {
              $token = self::tokenizeNumeric($line, $i, $len);
            } else if(preg_match(self::$regex[self::RE_ALPHA], $line[$i])) {
              /**
               * Compare each keyword in $keywords to the current character in $line
               * @var string $value The keyword string
               * @var string $type The keyword type from Parser
               */
               foreach(self::$keywords as $value => $type) {
                /** @var int $key_len Length of the keyword we are comparing */
                $key_len = strlen($value);
                if(substr_compare($line, $value, $i, $key_len) == 0){
                  $token = new Token($type, $value, $i);
                  $i += $key_len - 1;
                  break;
                }
              }
              if(!$token){
                /** Keyword was not found, tokenize the word as a variable instead */
                $token = self::tokenizeAlpha($line, $i, $len, true);
              }
            } else if(preg_match(self::$regex[self::RE_SPACE], $line[$i]) && !$search_keywords) {
              /** @var int $init_offset The starting position in $line */
              $init_offset = $i;
              /** Count all white space as a single space */
              for($i; $i < $len; $i++){
                if(!preg_match(self::$regex[self::RE_SPACE], $line[$i])) {
                  /** Update $i to match current offset */
                  $i--;
                  break;
                }
              }
              $token = new Token(Token::T_SPACE, ' ', $i);
            }
            if($token) {
              array_push($tokens, $token);
            }
          }
          array_push($tokens, new Token(
            Token::T_NEW_LINE,
            "\r\n",
            $offset + 1
          ));
        }
        fclose($file);
        return $tokens;
      } else {
        throw new Exception('Could not open file: ' . $file_path);
      }
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
    private function tokenizeNumeric(string $line, int &$i, int $len): Token {
      /** @var int $init_offset The starting position in $line */
      $init_offset = $i;
      /** @var string $value Holds the full number that will be tokenized */
      $value = $line[$i++];
      /** @var bool $has_dot Used in the case of a float */
      $has_dot = false;

      /** Parse string starting from $i to find the full number */
      for($i; $i < $len; $i++){
        if(preg_match(self::$regex[self::RE_NUMERIC], $line[$i])) {
          /** Concat found number to $value */
          $value .= $line[$i];
        } else if($line[$i] === '.') {
          if(!$has_dot) {
            /** The number is a float */
            $has_dot = true;
            $value .= $line[$i];
          } else {
            throw new Exception('Improperly formed number starting at position ' . $i);
          }
        } else { /** Non-numeric character found, full number is in $value */
          /** Update $i to match current offset */
          $i--;
          break;
        }
      }
      return new Token(Token::T_NUMERIC, $value, $init_offset);
    }

    /**
     * Creates a T_ALPHA token from the input string
     * @access public
     * @param string $line The string that will be tokenized
     * @param int $i The index at which the number starts in $line
     * @param int $len The length of the $line
     * @param bool $search_keywords Determines if we are looking for words or variable names
     * @return Token The resulting T_ALPHA token
     */
    private function tokenizeAlpha(string $line, int &$i, int $len, bool $search_keywords): Token {
      /** @var int $init_offset The starting position in $line */
      $init_offset = $i;
      /** @var string $value Holds the full word that will be tokenized */
      $value = $line[$i++];
      /** @var int $regex_index The index of the regex in $regex that will be used for matching */
      $regex_index = $search_keywords ? self::RE_ALPHA : self::RE_ALPHANUMERIC;

      /** Parse string starting from $i to find the full word */
      for($i; $i < $len; $i++){
        if(preg_match(self::$regex[$regex_index], $line[$i])) {
          /** Concat found letter to $value */
          $value .= $line[$i];
        } else { /** Non-alpha character found, full word is in $value */
          /** Update $i to match current offset */
          $i--;
          break;
        }
      }
      if($search_keywords){
        return new Token(Token::T_VARIABLE, $value, $init_offset);
      }else {
        return new Token(Token::T_ALPHANUMERIC, $value, $init_offset);
      }
    }
  }
?>
