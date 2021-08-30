<?php

namespace Wbcodes\SiteCore\Console\Commands\Clear;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Predis\Client;

class ClearRedisCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitecore:clear:redis {prefix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clear site redis cache keys';

    protected $redis_db_prefix;
    protected $keys;
    protected $delKey;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $redis = $this->redis_connection();

        $prefix = $this->argument('prefix');
        $this->keys = $redis->keys("*$prefix:*");

        $this->choiceClearCacheKeys();

        $this->clearCacheKeys($redis);

        return 0;
    }


    /**
     * @return Client|void
     */
    private function redis_connection()
    {
        try {

            $app_name = Str::slug(config('app.name', 'laravel'), '_');
            $this->redis_db_prefix = env('REDIS_PREFIX', "{$app_name}_database_{$app_name}_cache");


            return new Client(array(
                'host'     => config("database.redis.cache.host", '127.0.0.1'),
                'port'     => config("database.redis.cache.port", 6379),
                'password' => config("database.redis.cache.password", null),
                'database' => config("database.redis.cache.database", 1),
            ));

        } catch (Exception $e) {
            $this->warn("Couldn't connect to Redis");
            $this->warn($e->getMessage());
        }
    }

    /**
     * Prompt for which provider or tag to publish.
     *
     * @return void
     */
    protected function choiceClearCacheKeys()
    {
        if (!$this->keys) {
            if ($prefix = $this->argument('prefix')) {
                $this->warn("{$this->redis_db_prefix}: No cache keys has '{$prefix}' prefix in redis storage.");
            } else {
                $this->warn("{$this->redis_db_prefix}: Cache storage is empty");
            }
            return;
        }

        $choice = $this->choice(
            "Which cache would you like to delete?",
            $choices = $this->publishableChoices()
        );


        if ($choice == $choices[0] || is_null($choice)) {
            $this->delKey = "CLEAR_ALL_REDIS_CACHE";
            return;
        }

        $this->parseChoice($choice);
    }

    /**
     * The choices available via the prompt.
     *
     * @return array
     */
    protected function publishableChoices()
    {
        $keysWithoutDbName = [];
        foreach ($this->keys as $key) {
            $keysWithoutDbName [] = str_replace("{$this->redis_db_prefix}:", '', $key);
        }

        return array_merge(
            ['<comment>Clear all cache keys listed below</comment>'],
            preg_filter('/^/', '<comment>Key: </comment>', Arr::sort($keysWithoutDbName))
        );
    }

    /**
     * Parse the answer that was given via the prompt.
     *
     * @param  string  $choice
     * @return void
     */
    protected function parseChoice($choice)
    {
        [$type, $value] = explode(': ', strip_tags($choice));

        switch ($type) {
            case "Key":
                $this->delKey = "{$this->redis_db_prefix}:$value";
                break;
        }
    }

    /**
     * @param $redis
     */
    private function clearCacheKeys($redis)
    {
        // delete all cache keys
        if ($this->delKey == 'CLEAR_ALL_REDIS_CACHE') {
            foreach ($this->keys as $key) {
                $redis->del($key);
            }
        } else {
            // delete specific cache key
            $redis->del($this->delKey);
        }

        $this->info("redis cache cleared.");
    }

}
