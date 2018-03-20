<!DOCTYPE html>
<html>
  <body>

    <?php
      require __DIR__ . '/vendor/autoload.php';

      $data = array(
        'Name'      => 'Tony Twyman',
        'Stuff'     => array(
          new Stuff('roses', 'red'),
          new Stuff('violets', 'blue'),
          new Stuff('you', 'able to solve this'),
          new Stuff('we', 'interested in you'),
        )
      );

      try{
        /** @var array $tokens An array that contains all tokens from the input file */
        $tokens = array(Tokenizer::tokenize('./src/Templates/extra.tmpl'));
        $parser_output = Parser::parseTokens($tokens, $data);
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
          echo '<p>' . $html_output . '</p>';
        }
      } catch(Exception $e) {
        echo $e->getMessage();
      }
    ?>

  </body>
</html>
