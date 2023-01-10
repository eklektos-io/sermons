<?php

namespace Eklektos\Sermons;

use Eklektos\Sermons\Tags\Sermons;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    
    protected $tags = [
        Sermons::class,
    ];
}
