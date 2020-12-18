<?php namespace Pensoft\InternalDocuments;

use Pensoft\InternalDocuments\Components\InternalRepository;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
      return [
        InternalRepository::class => 'internalrepository'
      ];
    }
}
