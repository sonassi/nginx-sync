<?php namespace Sonassi\NginxSync;

use Commando\Command;
use Sonassi\Di\Object;

class CsvProcessorCli extends Object
{


    public function __construct(CsvProcessor $csvProcessor, Command $cmd, Nginx $nginx)
    {
        $this->di = self::di(func_get_args());

        $cmd->option('u')
            ->aka('url')
            ->describedAs('URL to CSV file')
            ->require(true)
            ->must(function($url) {
                return filter_var($url, FILTER_VALIDATE_URL);
            });

        $cmd->option('f')
            ->aka('output-file')
            ->describedAs('Output file to write to')
            ->require(true)
            ->must(function($output_file) {
                return file_exists($output_file);
            });

        $cmd->option('t')
            ->aka('template')
            ->describedAs('Template to apply CSV reformatting to')
            ->require(true)
            ->must(function($template) {
                $templates = array_keys($this->di->csvProcessor->getTemplates());
                return in_array(sprintf("%s.blade.php", $template), $templates);
            });

        $cmd->option('r')
            ->aka('reload')
            ->describedAs('Configtest and reload Nginx');;
    }

    public function process()
    {
        if ($rules = $this->di->csvProcessor->process($this->di->cmd['url'])) {
            $result = $this->di->csvProcessor->writeToFile($rules, $this->di->cmd['output-file'], $this->di->cmd['template'], $rules);

            if ($result) {
                $this->di->nginx->message('File (%s) written successfully.', [basename($this->di->cmd['output-file'])], 'success');

                if (isset($this->di->cmd['reload'])) {
                    $this->di->nginx->configtest();
                }
            }
        } else {
            $this->di->nginx->message('Failed to parse URL', [], 'fatal');
        }
    }

}