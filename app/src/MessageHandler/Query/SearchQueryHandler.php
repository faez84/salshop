<?php

namespace App\MessageHandler\Query;

use App\Message\Query\SearchQuery;

class SearchQueryHandler
{
    public function __invoke(SearchQuery $searchQuery): string
    {
        sleep(2);
       

        return 'result from DB';
    }
}