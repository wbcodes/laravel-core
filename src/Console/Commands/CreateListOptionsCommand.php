<?php

namespace Wbcodes\SiteCore\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Str;
use Wbcodes\SiteCore\Models\ListOption;

class CreateListOptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sitecore:listOption:create';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Create New List Options';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        $save_data = null;

        $list_name = $this->ask('What is your list option name?');
        if (!$list_name) {
            $this->warn('list option name is required');

            return 0;
        }
        $options = [];
        do {
            $new_option = $this->ask('add new option name?');
            if ($new_option) {
                $options[] = $new_option;
            }
        } while ($new_option);

        $this->warn("List Name is: {$list_name}");
        $this->info("Options:");
        foreach ($options as $key => $option) {
            $this->warn(($key + 1).". $option");
        }
        $save_data = $this->askWithCompletion('Do you want to save this data?', ['no', 'yes'], 'no');

        if ($save_data == 'yes') {
            $this->saveOptions($options, $list_name);
        }

        return 0;
    }

    /**
     * @param $options
     * @param      $list_name
     */
    function saveOptions($options, $list_name)
    {
        if (is_array($options)) {
            foreach ($options as $option) {
                $this->newItem($option, $list_name);
            }
        } else {
            $this->newItem($options, $list_name);
        }
    }

    /**
     * @param      $title
     * @param      $list_name
     *
     * @return int
     */
    function newItem($title, $list_name)
    {
        $slug = Str::slug($title);
        $item = ListOption::where('slug', $slug)->where('list_name', $list_name)->first();
        if ($item) {
            return 0;
        }
        $item = new ListOption();
        $item->slug = $slug;
        $item->title = Str::ucfirst(str_replace('_', ' ', $title));
        $item->list_name = $list_name;
        $item->created_by = 1;

        $item->save();
        $this->info("{$title} created successfully exists.");
    }
}