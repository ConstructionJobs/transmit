<?php

namespace Spatie\Fractal\Test;

use League\Fractal\Manager;
use NavJobs\LaravelApi\ArraySerializer;
use NavJobs\LaravelApi\Fractal;

class FractalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Spatie\Fractal\Fractal
     */
    protected $fractal;

    public function setUp()
    {
        $this->fractal = new Fractal(new Manager());
    }

    /**
     * @test
     */
    public function it_provides_chainable_methods()
    {
        $this->assertInstanceOf(get_class($this->fractal), $this->fractal->item('test'));
        $this->assertInstanceOf(get_class($this->fractal), $this->fractal->collection([]));
        $this->assertInstanceOf(get_class($this->fractal), $this->fractal->transformWith(function () {}));
        $this->assertInstanceOf(get_class($this->fractal), $this->fractal->serializeWith(new ArraySerializer()));
        $this->assertInstanceOf(get_class($this->fractal), $this->fractal->addMeta([]));
        $this->assertInstanceOf(get_class($this->fractal), $this->fractal->paginateWith(
            $this->getMock('League\Fractal\Pagination\PaginatorInterface')
        ));
    }
}
