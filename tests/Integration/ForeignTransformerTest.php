<?php

namespace NavJobs\LaravelApi\Test\Integration;

use League\Fractal\ParamBag;
use NavJobs\LaravelApi\ForeignTransformer;

class ForeignTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_correctly_transforms_foreign_data()
    {
        $data = [
            'data' => $this->testBooks[0],
            'status_code' => 200
        ];

        $transformer = new ForeignTransformer();
        $response = $transformer->transform(json_encode($data));

        $this->assertEquals($this->testBooks[0], $response);
    }
}