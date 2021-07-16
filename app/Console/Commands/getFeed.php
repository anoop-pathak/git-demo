<?php

namespace App\Console\Commands;

use App\Models\TradeNewsFeed;
use App\Models\TradeNewsUrl;
use Feed;
use Illuminate\Console\Command;

class getFeed extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:get_feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command use to get RSS feed from various urls and store feeds in the database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $urls = TradeNewsUrl::active()->with('tradeNews')->get();

        foreach ($urls as $url) {
            $items = $this->fetchFeed($url->url);
            $this->saveFeed($url, $items);
        }
    }

    private function fetchFeed($url)
    {
        try {
            $rss = Feed::loadRss($url);
            $feed = $rss->toArray();
            return $feed['item'];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function saveFeed(TradeNewsUrl $url, array $feeds)
    {
        if (empty($feeds)) {
            return;
        }
        TradeNewsFeed::where('url', $url->url)->delete();
        $order = 1;
        foreach ($feeds as $key => $feed) {
            TradeNewsFeed::create([
                'url' => $url->url,
                'trade_id' => $url->trade_news->trade_id,
                'feed' => $feed,
                'order' => $order++
            ]);
        }
    }

    // /**
    //  * Get the console command arguments.
    //  *
    //  * @return array
    //  */
    // protected function getArguments()
    // {
    // 	return array(
    // 		array('example', InputArgument::REQUIRED, 'An example argument.'),
    // 	);
    // }

    // /**
    //  * Get the console command options.
    //  *
    //  * @return array
    //  */
    // protected function getOptions()
    // {
    // 	return array(
    // 		array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    // 	);
    // }
}
