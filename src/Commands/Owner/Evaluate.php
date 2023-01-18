<?php

namespace Bot\Commands\Owner;

use Bot\Commands\Command;
use Discord\Builders\MessageBuilder;
use Throwable;

class Evaluate extends Command
{
    public $name = "eval";
    public $aliases = ['ev', 'e'];
    public $description = "Evaluate PHP code";
    public $category = "Owner";

    public function execute($message, $args)
    {
        $builder = MessageBuilder::new();
        $builder->setAllowedMentions(['parse' => []]);

        if((strtolower($args[0]) === "---file") || (strtolower($args[0]) === "-f")) {
            $code = trim(substr(substr($args[1], 0, -3), 6));
            $codeFile = "cache/eval.php";
            $outputFile = "cache/eval.log";

            $GLOBALS['message'] = $message;
            $GLOBALS['self'] = $this;

            file_put_contents($codeFile, "<?php\n$code");
            exec("php $codeFile >> $outputFile");

            $output = file_get_contents($outputFile);
            $builder->setContent("```$output```");

            $message->reply($builder)->done(function () use ($codeFile, $outputFile) {
                unlink($codeFile);
                unlink($outputFile);
            });

            return;
        }

        $code = trim(substr(substr(join(" ", $args), 0, -3), 6));
       
        try {
            eval($code);
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $message->reply($builder->setContent("```$error```"));
        }

        return;
    }
}
