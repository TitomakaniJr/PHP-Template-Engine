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

      $someVar = "Desc";

      print_r($data['Stuff'][0]->$someVar);

      try{
        /** @var array $tokens An array that contains all tokens from the input file */
        $tokens = array(Tokenizer::tokenize('{{#each Stuff}} {{Thing}} are {{Desc}} {{/each}}'));
        $output = Parser::parseTokens($tokens, $data);
        for($i = 0; $i < count($output); $i++) {
          if($output[$i]){
            for($j = 0; $j < count($output[$i]); $j++){
                echo $output[$i][$j] . '<br />';
            }
          }
        }
      } catch(Exception $e) {
        echo $e->getMessage();
      }
    ?>

  </body>
</html>
