<?php namespace Sonassi\NginxSync;

use GuzzleHttp\Client;
use Sonassi\Di\Object;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Nginx extends Object
{

    const CMD_CONFIGTEST = '/etc/init.d/nginx configtest';
    const CMD_RELOAD = '/etc/init.d/nginx reload';

    public function __construct(Client $guzzle_client)
    {
        $this->di = self::di(func_get_args());
    }

    public function message($msg, $elems = [], $level = 'notice')
    {
        $msg = vsprintf($msg, $elems);
        printf("%s: %s\n", ucfirst($level), $msg);

        if ($level == 'fatal')
            exit(1);
    }

    public function configTest()
    {
        $process = new Process(self::CMD_CONFIGTEST);
        $process->run();

        // Executes after the command finishes
        if (!$process->isSuccessful())
            $this->message('Nginx configuration test failed.', [], 'fatal');

        if (preg_match('#Config test OK#', $process->getOutput())) {
            $this->reload();
        }
    }

    public function reload()
    {
        $process = new Process(self::CMD_RELOAD);
        $process->run();

        // Executes after the command finishes
        if (!$process->isSuccessful())
            $this->message('Nginx failed to reload', [], 'fatal');

        if (preg_match('#Reloading nginx configuration#', $process->getOutput()))
            $this->message('Nginx reload completed successfully.', [], 'success');
    }

}