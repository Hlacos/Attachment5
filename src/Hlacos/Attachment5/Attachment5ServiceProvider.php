<?php

namespace Hlacos\Attachment5;

use Illuminate\Support\ServiceProvider;

class Attachment5ServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
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

	public function boot()
	{
		$this->publishes([
    		__DIR__.'/../../../config/attachment5.php' => config_path('attachment5.php'),
		]);

		$this->publishes([
	    	__DIR__.'/../../../database/migrations/' => database_path('/migrations')
		], 'migrations');
	}

}
