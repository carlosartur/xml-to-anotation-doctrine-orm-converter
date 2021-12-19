<?php

namespace Info\Relations;

use Info\Fields\JoinColumn;

class JoinTable
{
    /** @var JoinColumn[] */
    private array $joinColumns = [];

    /** @var JoinColumn[] */
    private array $reversedJoinColumns = [];

    public function __construct()
    {
        
    }
}
