<?php

namespace sigalx\dbio\Query;

class SelectCountQuery extends SelectQuery
{
    public function __construct()
    {
        parent::__construct(['COUNT(0)']);
    }
}
