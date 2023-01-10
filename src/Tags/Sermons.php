<?php

namespace Eklektos\Sermons\Tags;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Statamic\Entries\Entry;
use Statamic\Tags\Concerns\OutputsItems;
use Statamic\Tags\Collection\Collection;
use Statamic\Extensions\Pagination\LengthAwarePaginator;

class Sermons extends Collection
{
    use OutputsItems;
    
    public function index()
    {

        // Store the 'limit' param
        $pageLimit = $this->params->get('limit');
        
        // Forget it so parent call doesnt consider it
        $this->params->forget('limit');

        // Get 'as'
        $asParam = $this->params->get('as', 'entries');
        $this->params->put('as', $asParam);

        $this->params->put('from', 'sermons'); // Define collection as 'sermons'
        $collectionTagResults = parent::index(); // Call the collection tag    
            
        $filters = (object)[
            'date' => request()->input('date', false),
            'speaker' => request()->input('speaker', false),
            'scripture' => request()->input('scripture', false),
            'series' => request()->input('series', false),
        //    'language' => request()->input('language', false),
        //    'service' => request()->input('service', false),
        ];
        
        // Do some processing on filters
        if ($filters->date) {
            $filters->date = date("Y-m", strtotime($filters->date));
        }
        
        if ($filters->speaker) {
            $filters->speaker = Entry::query() # Query "people" collection
                ->where('collection', 'people')
                ->where('slug', $filters->speaker) # Match slug to URL "speaker" param
                ->first()
                ->id();
        }

        if ($filters->scripture) {
            $filters->scripture = trim(preg_replace('~[-_]~', ' ', $filters->scripture), ' ');
        }

        if ($filters->series) {
            $filters->series = Entry::query() # Query "people" collection
            ->where('collection', 'sermon_series')
            ->where('slug', $filters->series) # Match slug to URL "speaker" param
            ->first()
            ->id();
        }
    
        // Filter collection tag results
        $collectionTagResults[$asParam] = $collectionTagResults[$asParam]->filter(function ($entry) use ($filters) {
            
            if ($filters->date) {
                if (! Str::of($entry->value('sermon_date'))->lower()->contains($filters->date)) {
                    return false;
                }  
            }
            
            if ($filters->speaker) {
                if (! Str::of($entry->value('speaker'))->lower()->contains($filters->speaker)) {
                    return false;
                }  
            }

            if ($filters->scripture) {
                if (! Str::of($entry->value('scripture'))->lower()->contains($filters->scripture)) {
                    return false;
                }  
            }

            if ($filters->series) {
                if (! Str::of($entry->value('sermon_series'))->lower()->contains($filters->series)) {
                    return false;
                }  
            }

            //if ($filters->language) {
            //    if ($filters->language == 'spanish') {
            //        if (! $entry->value('spanish_audio')) // check for false value (null, empty, blank)
            //            return false;
            //    }

            //    if ($filters->language == 'english') {
            //        if (! $entry->value('audio')) // check for false value (null, empty, blank)
            //            return false;
            //    }
            //}

            //if ($filters->service) {
            //    if (! Str::of($entry->value('service'))->lower()->contains($filters->service)) {
            //        return false;
            //    }  
            //}
            
            return true;
            
        });  
        
        $totalResults = $collectionTagResults[$asParam]->count();

        if ($pageLimit) {
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            
            $collectionTagResults[$asParam] = $collectionTagResults[$asParam]
                ->skip(($currentPage-1) * $pageLimit)
                ->take($pageLimit);
        
            $collectionTagResults[$asParam] = app()->makeWith(LengthAwarePaginator::class, [
                'items' => $collectionTagResults[$asParam],
                'total' => $totalResults,
                'perPage' => $pageLimit,
                'currentPage' =>  $currentPage,
                'options' => [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ],
            ]);
        
        }
        
        return $this->output($collectionTagResults[$asParam]);
        
    }
}