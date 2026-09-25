<?php
// Lab 4 - WITH Decorator (GOOD starter, live API, has TODOs)
interface HttpClient {
    public function get(string $url): string;
}
class BaseHttpClient implements HttpClient {
    public function get(string $url): string {
        // Live to real endpoint
        return file_get_contents($url);
    }
}
abstract class HttpDecorator implements HttpClient {
    public function __construct(protected HttpClient $wrapped) {}
}
class LoggingDecorator extends HttpDecorator {
    public function get(string $url): string {
        echo "[Log] GET $url\n";
        $r = $this->wrapped->get($url);
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}
class CachingDecorator extends HttpDecorator {
    private array $cache = [];
    public function get(string $url): string {
        if (isset($this->cache[$url])) { echo "[Cache] Hit $url\n"; return $this->cache[$url]; }
        $r = $this->wrapped->get($url);
        $this->cache[$url] = $r;
        echo "[Cache] Stored $url\n";
        return $r;
    }
}

class RetryDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        for ($i = 0; $i < 3; $i++) {
            try {
                return $this->wrapped->get($url);
            } catch (Exception $e) {
                echo "[Retry] Attempt " . ($i + 1) . " failed\n";

                if ($i == 2) {
                    throw $e;
                }
            }
        }

        return "";
    }
}
class TokenCounterDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        $r = $this->wrapped->get($url);

        echo "[Tokens] " . str_word_count($r) . "\n";

        return $r;
    }
}
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {

    echo "WITH Decorator (GOOD):\n\n";

    $url = "https://jsonplaceholder.typicode.com/posts/1";

    // ORDER 1
    echo "=== ORDER 1 ===\n";
    echo "Retry(TokenCounter(Caching(Logging(BaseHttpClient))))\n\n";

    $client = new BaseHttpClient();
    $client = new LoggingDecorator($client);
    $client = new CachingDecorator($client);
    $client = new TokenCounterDecorator($client);
    $client = new RetryDecorator($client);

    echo substr($client->get($url), 0, 60) . "...\n";
    echo substr($client->get($url), 0, 60) . "...\n";


    // ORDER 2
    echo "\n=== ORDER 2 ===\n";
    echo "Caching(Logging(TokenCounter(Retry(BaseHttpClient))))\n\n";

    $client2 = new BaseHttpClient();
    $client2 = new RetryDecorator($client2);
    $client2 = new TokenCounterDecorator($client2);
    $client2 = new LoggingDecorator($client2);
    $client2 = new CachingDecorator($client2);

    echo substr($client2->get($url), 0, 60) . "...\n";
    echo substr($client2->get($url), 0, 60) . "...\n";
}