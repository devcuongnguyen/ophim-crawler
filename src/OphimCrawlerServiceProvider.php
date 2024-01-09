<?php

namespace Ophim\Crawler\OphimCrawler;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as SP;
use Ophim\Crawler\OphimCrawler\Console\CrawlerScheduleCommand;
use Ophim\Crawler\OphimCrawler\Option;

class OphimCrawlerServiceProvider extends SP
{
    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    public function policies()
    {
        return [];
    }

    public function register()
    {

        config([
            'plugins' => array_merge(config('plugins', []), [
                'devcuongnguyen/ophim-crawler' =>
                    [
                        'name' => 'Ophim Crawler',
                        'package_name' => 'devcuongnguyen/ophim-crawler',
                        'icon' => 'la la-hand-grab-o',
                        'entries' => [
                            ['name' => 'Crawler', 'icon' => 'la la-hand-grab-o', 'url' => backpack_url('/plugin/ophim-crawler')],
                            ['name' => 'Option', 'icon' => 'la la-cog', 'url' => backpack_url('/plugin/ophim-crawler/options')],
                        ],
                    ]
            ])
        ]);



        config([
            'logging.channels' => array_merge(config('logging.channels', []), [
                'ophim-crawler' => [
                    'driver' => 'daily',
                    'path' => storage_path('logs/devcuongnguyen/ophim-crawler.log'),
                    'level' => env('LOG_LEVEL', 'debug'),
                    'days' => 7,
                ],
            ])
        ]);

        config([
            'filesystems.disks' => array_merge(config('filesystems.disks', []), [
                'r2' => [
                    'driver' => 's3',
                    'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
                    'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
                    'region' => 'us-east-1', // Cloudflare R2 doesn't have specific regions, so 'us-east-1' is fine.
                    'bucket' => env('CLOUDFLARE_R2_BUCKET'),
                    'url' => env('CLOUDFLARE_R2_URL'),
                    'visibility' => 'private',
                    'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
                    'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', false),
                    'throw' => false,
                ],
            ])
        ]);


        config([
            'ophim.updaters' => array_merge(config('ophim.updaters', []), [
                [
                    'name' => 'Ophim Crawler',
                    'handler' => 'Ophim\Crawler\OphimCrawler\Crawler'
                ]
            ])
        ]);


    }

    public function boot()
    {
        $this->commands([
            CrawlerScheduleCommand::class,
        ]);

        $this->app->booted(function () {
            $this->loadScheduler();
        });

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ophim-crawler');
    }

    protected function loadScheduler()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('ophim:plugins:ophim-crawler:schedule')->cron(Option::get('crawler_schedule_cron_config', '*/10 * * * *'))->withoutOverlapping();
    }
}