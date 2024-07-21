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
            <li><a class="gpt-message-create" href="#" target="_blank" role="button"><?php echo __("Generate answer (GPT)")?></a></li>
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
                <div class="gpt-message-toolbar">

                    <div class="gpt-message-toolbar-title">
                        <?php echo __('GPT Answers') ?>:
                    </div>
                    
                    <?php foreach ($messages as $index => $message): ?>
                        <?php $is_current = ($index == $current_index); ?>
                        <div class="gpt-message-triggers">
                            <a 
                                href="#" 
                                data-thread-id="<?php echo $thread->id ?>" 
                                data-message-index="<?php echo $index ?>" 
                                class="gpt-message-toggle gpt-message-trigger-<?php echo $index ?> <?php if ($is_current): ?> selected<?php endif ?>"
                            >
                                <?php echo $index+1; ?>
                            </a>
                        </div>
                    <?php endforeach ?>
                    
                    <img class="gpt-message-toolbar-icon gpt-message-loader hidden" src="/modules/hostetskigpt/img/loading.gif" alt="">

                    <button class="gpt-message-run">
                        <img class="gpt-message-toolbar-icon" src="/modules/hostetskigpt/img/ic_autorenew.png" alt="">
                    </button>
                    
                    <button class="gpt-message-copy"
                        data-thread-id="<?php echo $thread->id ?>" 
                        data-message-index="<?php echo $index ?>" 
                    >
                        <img class="gpt-message-toolbar-icon" src="/modules/hostetskigpt/img/ic_content_copy.png" alt="">
                    </button>
                </div>
                <?php foreach ($messages as $index => $message): ?>
                    <div class="gpt-message-text gpt-message-<?php echo $index ?> <?php if ($index != $current_index): ?>hidden<?php endif ?>">
                        <?php echo nl2br(htmlspecialchars($message)) ?>
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
