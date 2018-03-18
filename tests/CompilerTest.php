<?php
  use PHPUnit\Framework\TestCase;

  class CompilerTest extends TestCase {

    /**
     * Verify that the tokenizer correctly creates tokens from input string
     * @test
     */
    public function testTokenizerTokens() {
      $tokens = Tokenizer::tokenize('{{#unless @last}}@ word1234{{/unless}}');
      $this->assertEquals([
        new Token(Parser::T_BRACES_OPEN, '{{', 0),
        new Token(Parser::T_FUNCTION_START, '#', 2),
        new Token(Parser::T_IF, 'unless', 3),
        new Token(Parser::T_AT, '@', 10),
        new Token(Parser::T_LAST, 'last', 11),
        new Token(Parser::T_BRACES_CLOSE, '}}', 15),
        new Token(Parser::T_ALPHANUMERIC, '@', 17),
        new Token(Parser::T_ALPHANUMERIC, 'word1234', 19),
        new Token(Parser::T_BRACES_OPEN, '{{', 27),
        new Token(Parser::T_FUNCTION_END, '/', 29),
        new Token(Parser::T_IF, 'unless', 30),
        new Token(Parser::T_BRACES_CLOSE, '}}', 36),
      ], $tokens);
    }

    /**
     * Verify that misformed numbers within function braces causes an error to be thrown
     * @test
     * @expectedException Exception
     */
    public function testTokenizerBadNumber() {
      $tokens = Tokenizer::tokenize('1..5');
      $this->assertEquals([
        new Token(Parser::T_ALPHANUMERIC, '1..5', 0),
      ], $tokens);

      /** Improperly formed number should throw exception */
      $tokens = Tokenizer::tokenize('{{1..5}}');

    }
  }
?>
