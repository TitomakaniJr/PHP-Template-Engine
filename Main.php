<!DOCTYPE html>
<html>
  <body>

    <?php
      spl_autoload_register(function ($class_name) {
        include 'Compiler/' . $class_name . '.class.php';
      });

      try{
        Tokenizer::tokenize('{{#unless @last}}@ 12.345@@@@');
      } catch(Exception $e) {
        echo $e->getMessage();
      }
    ?>

  </body>
</html>
