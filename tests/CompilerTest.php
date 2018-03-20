<?php
  use PHPUnit\Framework\TestCase;

  class CompilerTest extends TestCase {

    protected static $data;

    protected function setUp() {
      self::$data = array(
        'Name'       => 'Tony Twyman',
        'MyBool'     => 'true',
        'Stuff'      => array(
          new Stuff('roses', 'red'),
          new Stuff('violets', 'blue'),
          new Stuff('you', 'able to solve this'),
          new Stuff('we', 'interested in you'),
        )
      );
    }

    /**
     * Verify that the tokenizer correctly creates tokens from input string
     * @test
     */
    public function testTokenizerTokens_0() {
      $tokens = Tokenizer::tokenize('./tests/Templates/test_0.tmpl');
      $this->assertEquals([
        new Token(Token::T_BRACES_OPEN, '{{', 0),
        new Token(Token::T_VARIABLE, 'Name', 2),
        new Token(Token::T_BRACES_CLOSE, '}}', 6),
        new Token(Token::T_SPACE, ' ', 8),
        new Token(Token::T_ALPHANUMERIC, 'is', 9),
        new Token(Token::T_SPACE, ' ', 11),
        new Token(Token::T_ALPHANUMERIC, 'testing', 12),
        new Token(Token::T_SPACE, ' ', 19),
        new Token(Token::T_BRACES_OPEN, '{{', 20),
        new Token(Token::T_FUNCTION_START, '#', 22),
        new Token(Token::T_IF, 'unless', 23),
        new Token(Token::T_VARIABLE, 'MyBool', 30),
        new Token(Token::T_BRACES_CLOSE, '}}', 36),
        new Token(Token::T_ALPHANUMERIC, 'something', 38),
        new Token(Token::T_BRACES_OPEN, '{{', 47),
        new Token(Token::T_ELSE, 'else', 49),
        new Token(Token::T_BRACES_CLOSE, '}}', 53),
        new Token(Token::T_ALPHANUMERIC, 'nothing', 55),
        new Token(Token::T_BRACES_OPEN, '{{', 62),
        new Token(Token::T_FUNCTION_END, '/', 64),
        new Token(Token::T_IF, 'unless', 65),
        new Token(Token::T_BRACES_CLOSE, '}}', 71),
        new Token(Token::T_SPACE, ' ', 74),
        new Token(Token::T_NEW_LINE, "\r\n", 74)
      ], $tokens);
    }

    /**
     * Verify that the parser outputs the correct html
     * @test
     */
    public function testParser_1() {
      $tokens = array(Tokenizer::tokenize('./tests/Templates/test_1.tmpl'));
      $parser_output = Parser::parseTokens($tokens, self::$data);
      for($i = 0; $i < count($parser_output); $i++) {
        $html_output = '';
        if($parser_output[$i]){
          if(gettype($parser_output[$i]) == 'string') {
             $html_output .= $parser_output[$i];
          } else {
            for($j = 0; $j < count($parser_output[$i]); $j++){
                $html_output .= $parser_output[$i][$j];
            }
          }
        }
        $this->assertEquals('Hey Tony Twyman, here\'s a slightly better formatted '
                .'poem for you: <br /> <br />  roses are red, <br />  violets are blue, '
                .'<br />  you are able to solve this, <br />  we are interested in you! '
                .'<br /> <br />', $html_output);
      }
    }
  }
?>
