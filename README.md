## Usage

~~~
php-5.6 app.php --help
 app.php


-f/--output-file <argument>
     Required. Output file to write to


--help
     Show the help page for this command.


-r/--reload <argument>
     Configtest and reload Nginx


-t/--template <argument>
     Required. Template to apply CSV reformatting to


-u/--url <argument>
     Required. URL to CSV file
~~~

## Templates

The templates use Laravel's [Blade format](https://laravel.com/docs/5.1/blade) and can be found in,

    vendor-dev/sonassi/nginx-sync/src/views

## Examples

### Using Google sheets as source

~~~
php-5.6 app.php --url 'https://docs.google.com/spreadsheets/d/xxx?output=csv' --output-file '/microcloud/domains/example/domains/example.com/___general/example.com.conf' --template 'admin-whitelist' -r
Success: File (example.conf) written successfully.
Success: Nginx reload completed successfully.
~~~~
