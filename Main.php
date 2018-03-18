<!DOCTYPE html>
<html>
  <body>

    <?php
      require __DIR__ . '/vendor/autoload.php';

      try{
        /** @var array $tokens An array that contains all tokens from the input file */
        $tokens = Tokenizer::tokenize('{{#unless @last someVar}}@ 12.345@@@@ 1234Word5678');
      } catch(Exception $e) {
        echo $e->getMessage();
      }
    ?>

  </body>
</html>
