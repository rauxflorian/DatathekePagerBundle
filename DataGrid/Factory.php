<?php

namespace Datatheke\Bundle\PagerBundle\DataGrid;

use Datatheke\Bundle\PagerBundle\DataGrid\Column\Guesser\GuesserInterface;
use Datatheke\Bundle\PagerBundle\DataGrid\Column\Guesser\Exception\UnableToGuessException;
use Datatheke\Bundle\PagerBundle\DataGrid\Handler\Http\HttpHandlerInterface;
use Datatheke\Bundle\PagerBundle\DataGrid\Handler\Http\ViewHandler;
use Datatheke\Bundle\PagerBundle\DataGrid\Handler\Console\ConsoleHandlerInterface;
use Datatheke\Bundle\PagerBundle\Pager\PagerInterface;
use Datatheke\Bundle\PagerBundle\Pager\HttpPagerInterface;
use Datatheke\Bundle\PagerBundle\Pager\Factory as PagerFactory;
use Datatheke\Bundle\PagerBundle\Pager\Handler\Http\ViewHandler as PagerViewHandler;

class Factory
{
    protected $config;
    protected $pagerFactory;
    protected $guesser;
    protected $httpHandlers;
    protected $consoleHandlers;

    public function __construct(Configuration $config, PagerFactory $pagerFactory, GuesserInterface $guesser, array $httpHandlers, array $consoleHandlers)
    {
        $this->config          = $config;
        $this->pagerFactory    = $pagerFactory;
        $this->guesser         = $guesser;
        $this->httpHandlers    = $httpHandlers;
        $this->consoleHandlers = $consoleHandlers;
    }

    /**
     * @deprecated
     */
    public function createWebDataGrid($pager, array $options = array(), array $columns = null)
    {
        trigger_error('createWebDataGrid() is deprecated. Use createHttpDataGrid() instead.', E_USER_DEPRECATED);

        $handler = 'view';
        if ($pager instanceof HttpPagerInterface && ($pagerHandler = $pager->getHandler()) instanceof PagerViewHandler) {
            $handler = new ViewHandler($pagerHandler);
        }

        return $this->createHttpDataGrid($pager, $options, $handler, $columns);
    }

    public function createHttpDataGrid($pager, array $options = array(), $handler = 'view', array $columns = null)
    {
        if (!$pager instanceof PagerInterface) {
            $pager = $this->pagerFactory->createPager($pager);
        }

        if (!$handler instanceof HttpHandlerInterface) {
            $handler = $this->createHandler($handler, $this->httpHandlers);
        }

        if (null === $columns) {
            $columns = $this->guessColumns($pager->getFields());
        }

        return new HttpDataGrid($pager, $handler, $columns, $options);
    }

    public function createConsoleDataGrid($pager, array $options = array(), $handler = 'default', array $columns = null)
    {
        if (!$pager instanceof PagerInterface) {
            $pager = $this->pagerFactory->createPager($pager);
        }

        if (!$handler instanceof ConsoleHandlerInterface) {
            $handler = $this->createHandler($handler, $this->consoleHandlers);
        }

        if (null === $columns) {
            $columns = $this->guessColumns($pager->getFields());
        }

        return new ConsoleDataGrid($pager, $handler, $columns, $options);
    }

    protected function createHandler($name, $list)
    {
        if (!isset($list[$name])) {
            throw new \Exception('The handler "'.$name.'" does not exist');
        }

        return clone $list[$name];
    }

    protected function guessColumns(array $fields)
    {
        $columns = array();
        foreach ($fields as $fieldAlias => $field) {
            try {
                $columns[$fieldAlias] = $this->guesser->guess($field, $fieldAlias);
            } catch (UnableToGuessException $e) {
            }
        }

        return $columns;
    }
}
