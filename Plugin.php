<?php namespace Pensoft\InternalDocuments;

use Pensoft\InternalDocuments\Components\InternalRepository;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function boot(): void {}

    public function registerComponents(): array
    {
      return [
        InternalRepository::class => 'internalrepository'
      ];
    }
}