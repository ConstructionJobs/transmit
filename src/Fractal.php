<?php

namespace NavJobs\LaravelApi;

use League\Fractal\Manager;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Serializer\SerializerAbstract;
use NavJobs\LaravelApi\Exceptions\InvalidTransformation;
use NavJobs\LaravelApi\Exceptions\NoTransformerSpecified;

class Fractal
{
    /**
     * @var \League\Fractal\Manager
     */
    protected $manager;

    /**
     * @var \League\Fractal\Serializer\SerializerAbstract
     */
    protected $serializer;

    /**
     * @var \League\Fractal\TransformerAbstract|Callable
     */
    protected $transformer;

    /**
     * @var \League\Fractal\Pagination\PaginatorInterface
     */
    protected $paginator;

    /**
     * @var array
     */
    protected $includes = [];

    /**
     * @var string
     */
    protected $dataType;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var string
     */
    protected $resourceName;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param \League\Fractal\Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Set the collection data that must be transformed.
     *
     * @param mixed                                             $data
     * @param \League\Fractal\TransformerAbstract|Callable|null $transformer
     * @param string|null                                       $resourceName
     *
     * @return $this
     */
    public function collection($data, $transformer = null, $resourceName = null)
    {
        $this->resourceName = $resourceName;

        if ($transformer) {
            $this->transformWith($transformer);
        }

        return $this->data('collection', $data, $transformer);
    }

    /**
     * Set the item data that must be transformed.
     *
     * @param mixed                                             $data
     * @param \League\Fractal\TransformerAbstract|Callable|null $transformer
     * @param string|null                                       $resourceName
     *
     * @return $this
     */
    public function item($data, $transformer = null, $resourceName = null)
    {
        $this->resourceName = $resourceName;

        if ($transformer) {
            $this->transformWith($transformer);
        }

        return $this->data('item', $data, $transformer);
    }

    /**
     * Set the data that must be transformed.
     *
     * @param string                                            $dataType
     * @param mixed                                             $data
     * @param \League\Fractal\TransformerAbstract|Callable|null $transformer
     *
     * @return $this
     */
    protected function data($dataType, $data, $transformer = null)
    {
        $this->dataType = $dataType;

        $this->data = $data;

        if (!is_null($transformer)) {
            $this->transformer = $transformer;
        }

        return $this;
    }

    /**
     * Set the class or function that will perform the transform.
     *
     * @param \League\Fractal\TransformerAbstract|Callable $transformer
     *
     * @return $this
     */
    public function transformWith($transformer)
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Set a Fractal paginator for the data.
     *
     * @param \League\Fractal\Pagination\PaginatorInterface $paginator
     *
     * @return $this
     */
    public function paginateWith(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * Specify the includes.
     *
     * @param array|string $includes Array or csv string of resources to include
     *
     * @return $this
     */
    public function parseIncludes($includes)
    {
        if (is_string($includes)) {
            $includes = array_map(function ($value) {
               return trim($value);
            },  explode(',', $includes));
        }

        $this->includes = array_merge($this->includes, (array)$includes);

        return $this;
    }

    /**
     * Support for magic methods to included data.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return $this
     */
    public function __call($name, array $arguments)
    {
        if (!starts_with($name, 'include')) {
            trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);
        }

        $includeName = lcfirst(substr($name, strlen('include')));

        return $this->parseIncludes($includeName);
    }

    /**
     * Set the serializer to be used.
     *
     * @param \League\Fractal\Serializer\SerializerAbstract $serializer
     *
     * @return $this
     */
    public function serializeWith(SerializerAbstract $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Set the meta data.
     *
     * @param $array,...
     *
     * @return $this
     */
    public function addMeta()
    {
        foreach (func_get_args() as $meta) {
            if (is_array($meta)) {
                $this->meta += $meta;
            }
        }

        return $this;
    }

    /**
     * Set the resource name, to replace 'data' as the root of the collection or item.
     *
     * @param string $resourceName
     *
     * @return $this
     */
    public function resourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * Perform the transformation to json.
     *
     * @return string
     */
    public function toJson()
    {
        return $this->transform('toJson');
    }

    /**
     * Perform the transformation to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->transform('toArray');
    }

    /**
     *  Perform the transformation.
     *
     * @param string $conversionMethod
     *
     * @return string|array
     */
    protected function transform($conversionMethod)
    {
        $fractalData = $this->createData();

        return $fractalData->$conversionMethod();
    }

    /**
     * Create fractal data.
     */
    public function createData()
    {
        if (is_null($this->transformer)) {
            throw new NoTransformerSpecified();
        }

        if (!is_null($this->serializer)) {
            $this->manager->setSerializer($this->serializer);
        }

        if (!is_null($this->includes)) {
            $this->manager->parseIncludes($this->includes);
        }

        $resource = $this->getResource();

        return $this->manager->createData($resource);
    }

    /**
     * Get the resource.
     */
    public function getResource()
    {
        $resourceClass = 'League\\Fractal\\Resource\\'.ucfirst($this->dataType);

        if (!class_exists($resourceClass)) {
            throw new InvalidTransformation();
        }

        $resource = new $resourceClass($this->data, $this->transformer, $this->resourceName);

        $resource->setMeta($this->meta);

        if (!is_null($this->paginator)) {
            $resource->setPaginator($this->paginator);
        }

        return $resource;
    }
}
