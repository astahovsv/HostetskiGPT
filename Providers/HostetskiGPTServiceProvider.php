<?php

namespace Modules\HostetskiGPT\Providers;

use App\Mailbox;
use App\Thread;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Nwidart\Modules\Facades\Module;

define('GPT_ASSISTANT_MODULE', 'hostetskigpt');

class HostetskiGPTServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    //save the mailbox for re-use in the javascripts hook
    private $mailbox = null;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            array_push($javascripts, \Module::getPublicPath("hostetskigpt").'/js/module.js');
            return $javascripts;
        });

        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($stylesheets) {
            array_push($stylesheets, \Module::getPublicPath("hostetskigpt").'/css/module.css');
            return $stylesheets;
        });

        //catch the mailbox for the current request
        \Eventy::addFilter('mailbox.show_buttons', function($show, $mailbox){
            $this->mailbox =$mailbox;
            return $show;
        }, 20 , 2);

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            $version = Module::find('hostetskigpt')->get('version');
            $copiedToClipboard = __("Copied to clipboard");
            $send = __("Send");

            echo "const gptassistantData = {" .
                    "'copiedToClipboard': '{$copiedToClipboard}'," .
                    "'version': '{$version}'," .
                    "'send': `{$send}`," .
                "};";
            echo 'hostetskigptInit();';
        });

        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('hostetskigpt::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 80);

        \Eventy::addAction('thread.menu', function ($thread) {
            if ($thread->type == Thread::TYPE_LINEITEM) {
                return;
            }
            ?>
            <li><a class="chatgpt-get" href="#" target="_blank" role="button"><?php echo __("Generate answer (GPT)")?></a></li>
            <?php
        }, 100);

        // Show answers
        \Eventy::addAction('thread.before_body', function($thread, $loop, $threads, $conversation, $mailbox) {
            $messages = \Helper::jsonToArray($thread->chatgpt);
            if (!$messages) {
                return;
            }
            $i = 0;
            $current_index = count($messages) - 1;
            ?>
            <div class="margin-bottom">
                <span class="small margin-bottom-10">
                    <strong><?php echo __('GPT Answers') ?>:</strong>
                    &nbsp;
                    <span class="gpt-message-triggers">
                    <?php foreach ($messages as $index => $message): ?>
                        <?php $is_current = ($index == $current_index); ?>
                        <a 
                            href="#" 
                            data-thread-id="<?php echo $thread->id ?>" 
                            data-message-index="<?php echo $index ?>" 
                            class="gpt-message-toggle gpt-message-trigger-<?php echo $index ?> <?php if ($is_current): ?> selected<?php endif ?>"
                        >
                            <?php echo $index+1; ?>
                        </a>
                        <?php if ($index < count($messages)-1): ?>&nbsp;|&nbsp;<?php endif ?>
                        <?php endforeach ?>
                    </span>
                    &nbsp;
                    <span 
                        data-thread-id="<?php echo $thread->id ?>" 
                        data-message-index="<?php echo $index ?>" 
                        class="gpt-message-copy"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#000000">
                            <path d="M360-240q-29.7 0-50.85-21.15Q288-282.3 288-312v-480q0-29.7 21.15-50.85Q330.3-864 360-864h384q29.7 0 50.85 21.15Q816-821.7 816-792v480q0 29.7-21.15 50.85Q773.7-240 744-240H360Zm0-72h384v-480H360v480ZM216-96q-29.7 0-50.85-21.15Q144-138.3 144-168v-552h72v552h456v72H216Zm144-216v-480 480Z"/>
                        </svg>
                    </span>
                </span>
                <br/>
                <?php foreach ($messages as $index => $message): ?>
                <div class="alert alert-note gpt-message-text gpt-message-<?php echo $index ?> <?php if ($index != $current_index): ?>hidden<?php endif ?>">
                    <?php echo \Helper::nl2brDouble(htmlspecialchars($message)) ?>
                </div>
                <?php endforeach ?>
            </div>
            <?php
        }, 20, 5);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('hostetskigpt.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'hostetskigpt'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/hostetskigpt');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/hostetskigpt';
        }, \Config::get('view.paths')), [$sourcePath]), 'hostetskigpt');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
