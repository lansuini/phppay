<?php

namespace App\Controllers;

use PSR\Container\ContainerInterface;

abstract class Controller
{
    /**
     * The container instance.
     *
     * @var \PSR\Container\ContainerInterface
     */
    protected $c;

    /**
     * Set up controllers to have access to the container.
     *
     * @param \PSR\Container\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->c = $container;
    }
}
