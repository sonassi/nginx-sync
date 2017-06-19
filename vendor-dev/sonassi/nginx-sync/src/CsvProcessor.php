<?php namespace Sonassi\NginxSync;

use Sonassi\Di\Object;
use Philo\Blade\Blade;

class CsvProcessor extends Object
{

    private $headers = ['02-timestamp', '03-name', '04-location', '01-ip_address'];
    private $views = __DIR__ . '/views';
    private $cache = '/tmp/nginxsync';

    public function __construct(Nginx $nginx)
    {
        $this->di = self::di(func_get_args());

        $this->client = $this->di->nginx->di->guzzle->client;

        if (!file_exists($this->cache))
            mkdir($this->cache);
    }

    public function process($url)
    {
        $response = $this->client->request('GET', $url, ['http_errors' => false]);

        $rules = [];
        if ($response->getStatusCode() == 200) {
            $rows = str_getcsv((string) $response->getBody(), "\n");

            // Skip header row.
            unset($rows[0]);

            foreach ($rows as $id => $row) {
                $row = str_getcsv($row);
                if (count($this->headers) != count($row)) {
                    $this->di->nginx->message('Invalid row count on line %d (skipping)', [$id]);
                }
                $row = array_combine($this->headers, $row);
                ksort($row, SORT_NATURAL);

                $keys = array_keys($row);
                $values = array_values($row);
                $result = preg_replace('#^[0-9]+\-#', '', $keys);
                $row = array_combine($result, $values);

                $rules[] = $row;
            }
        }

        return (count($rules)) ? $rules : false;
    }

    public function writeToFile($rules, $outputfile, $template, $rules)
    {
        $blade = new Blade($this->views, $this->cache);
        $data = $blade->view()->make($template)->with(['rules' => $rules])->render();

        if (file_exists($outputfile)) {
            // Compare the file contents
            if (md5($data) == md5_file($outputfile)) {
                $this->di->nginx->message('Identical content, no changes detected (skipping)', [$id]);
                return false;
            }

            return file_put_contents($outputfile, $data);
        }

        return false;
    }

    public function getTemplates()
    {
        $templateFiles = glob(__DIR__.'/views/*');
        $templates = [];
        foreach ($templateFiles as $templateFile) {
            $filename = basename($templateFile);
            $templates[$filename] = ['path' => $templateFile, 'filename' => $filename];
        }

        return $templates;
    }
}