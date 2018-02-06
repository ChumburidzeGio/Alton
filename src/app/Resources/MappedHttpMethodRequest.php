<?php
namespace App\Resources;

use Illuminate\Support\Facades\Log;

class MappedHttpMethodRequest extends HttpMethodRequest
{
    protected $inputTransformations = [];
    protected $inputGenerators = [];
    protected $inputToExternalMapping = false;
    protected $externalToResultMapping = false;
    protected $resultTransformations = [];

    protected $inputParams = null;

    protected $clearUnmapped = false;

    protected function getDefaultParams()
    {
        return [];
    }

    public function setParams(array $params)
    {
        if (!isset($this->inputParams))
            $this->inputParams = $params;

        $params = $this->applyInputTransforms($params);

        $params = $this->applyInputGenerators($params);

        $params = $this->applyPathParams($params);

        $params = $this->mapInputToExternal($params, $this->getDefaultParams());

        parent::setParams($params);
    }

    protected function applyPathParams(array $params)
    {
        if (str_contains($this->url, '{'))
        {
            $this->url = preg_replace_callback('~\{([^\}]+)\}~', function ($matches) use (&$params) {
                if (isset($params[$matches[1]])) {
                    $value = $params[$matches[1]];
                    unset($params[$matches[1]]);
                    return $value;
                }
                $this->setErrorString(sprintf('Parameter `%s` is required in input path.', $matches[1]));
                return $matches[0];
            }, $this->url);
        }

        return $params;
    }

    protected function applyInputTransforms(array $params)
    {
        foreach ($this->inputTransformations as $key => $transformationFunction)
        {
            try
            {
                if (!method_exists($this, $transformationFunction)) {
                    $this->setErrorString('Missing input transform function: `'. $transformationFunction .'`');
                    continue;
                }

                //Already existing key
                if (array_key_exists($key, $params)){
                    $params[$key] = $this->{$transformationFunction}($params[$key], $params, $key);
                }else{

                }
            }
            catch (\Exception $e)
            {
                $this->addErrorMessage($key, 'input-error', 'Error encountered while processing input `'. $params[$key] .'` for parameter `'. $key .'`.');
                Log::error($e);
            }
        }

        return $params;
    }

    /**
     * Simpler way to generate input params that offers more functionality
     * by delegating the responsibility of setting a key to the generator function.
     * You can for example combine two different inputs into a new input with this.
     * @param array $params
     * @return array
     */
    protected function applyInputGenerators(array $params)
    {
        foreach ($this->inputGenerators as $key => $generatorFunction)
        {
            try
            {
                if (!method_exists($this, $generatorFunction)) {
                    $this->setErrorString('Missing input generator function: `'. $generatorFunction .'`');
                    continue;
                }
                $params = $this->{$generatorFunction}($params, $key);
            }
            catch (\Exception $e)
            {
                $this->addErrorMessage($key, 'input-error', 'Error encountered while generating input  for parameter `'. $key .'`.');
                Log::error($e);
            }
        }

        return $params;
    }

    public function getResult()
    {
        $result = parent::getResult();

        if (!is_array($result))
        {
            $this->setErrorString('Unexpected empty result.');
            return null;
        }

        $result = $this->mapExternalToResult($result);

        $result = $this->applyResultTransforms($result);

        if ($this->clearUnmapped)
        {
            if (isset($result[0])) {
                foreach ($result as $key => $value)
                    unset($result[$key]['@unmapped']);
            }
            else
                unset($result['@unmapped']);
        }

        return $result;
    }

    protected function applyResultTransforms(array $result)
    {
        if (isset($result[0]))
        {
            foreach ($result as $itemKey => $item)
            {
                foreach ($this->resultTransformations as $key => $transformationFunction)
                {
                    if (!method_exists($this, $transformationFunction)) {
                        $this->setErrorString('Missing output transform function: `'. $transformationFunction .'`');
                        continue;
                    }

                    $result[$itemKey][$key] = $this->{$transformationFunction}(isset($item[$key]) ? $item[$key] : null, $item, $key);
                }
            }
        }
        else if ($result != [])
        {
            foreach ($this->resultTransformations as $key => $transformationFunction)
            {
                if (!method_exists($this, $transformationFunction)) {
                    $this->setErrorString('Missing transform function: `'. $transformationFunction .'`');
                    continue;
                }

                $result[$key] = $this->{$transformationFunction}(isset($result[$key]) ? $result[$key] : null, $result, $key);
            }
        }

        return $result;
    }

    protected function mapInputToExternal(array $inputParams, array $params, $unsetNullValues = true, $unsetEmptyArrays = true)
    {
        if ($this->inputToExternalMapping === false)
            return $inputParams;

        foreach($inputParams as $key => $value)
        {
            if (!isset($this->inputToExternalMapping[$key]))
                continue;

            if (is_array($this->inputToExternalMapping[$key]))
            {
                foreach ($this->inputToExternalMapping[$key] as $externalParam)
                    array_set($params, $externalParam, $value);
            }
            else
            {
                array_set($params, $this->inputToExternalMapping[$key], $value);
            }
        }

        if ($unsetNullValues)
            $params = $this->filterNullValues($params);
        if ($unsetEmptyArrays)
            $params = $this->filterEmptyArrayValues($params);

        return $params;
    }

    protected function mapExternalToResult(array $rawResult)
    {
        if (!is_array($this->externalToResultMapping) || $rawResult === [])
            return $rawResult;

        $result = [];


        if (isset($rawResult[0]))
        {
            foreach ($rawResult as $itemKey => $item) {
                foreach ($this->externalToResultMapping as $mapping => $resultKey) {
                    if (!is_array($resultKey))
                        $resultKey = [$resultKey];
                    $value = array_pull($item, $mapping);
                    foreach ($resultKey as $key)
                        $result[$itemKey][$key] = $value;
                }
                if (count($item) > 0)
                    $result[$itemKey]['@unmapped'] = $item;
            }
        }
        else
        {
            foreach ($this->externalToResultMapping as $mapping => $resultKey) {
                if (!is_array($resultKey))
                    $resultKey = [$resultKey];
                $value = array_pull($rawResult, $mapping);
                foreach ($resultKey as $key)
                    $result[$key] = $value;
            }
            if (count($rawResult) > 0)
                $result['@unmapped'] = $rawResult;
        }

        return $result;
    }

    protected function filterNullValues(array $array)
    {
        foreach ($array as $key => $value)
        {
            if ($value === null)
                unset($array[$key]);
            else if (is_array($value) && $value != [])
                $array[$key] = $this->filterNullValues($value);
        }
        return $array;
    }


    protected function filterEmptyArrayValues(array $array)
    {
        foreach ($array as $key => $value)
        {
            if ($value === [])
                unset($array[$key]);
            else if (is_array($value))
                $array[$key] = $this->filterEmptyArrayValues($value);
        }
        return $array;
    }

    // Globally useful transformation functions

    public function castToString($value)
    {
        return (string)$value;
    }

    public function castToInt($value)
    {
        return (int)$value;
    }

    public function castToBool($value)
    {
        return (bool)$value;
    }

    protected function formatInputDateTime($inputDateTime, $params, $key, $format)
    {
        try
        {
            $dateTime = new \DateTime($inputDateTime);
        }
        catch (\Exception $e)
        {
            $this->addErrorMessage($key, 'invalid-datetime-'. $key, 'Could not parse input date time `'. $inputDateTime .'`: '. $e->getMessage());
            return null;
        }

        return $dateTime->format($format);
    }
}
