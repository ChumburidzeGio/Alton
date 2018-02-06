<?php
namespace App\Resources;

use App, Log;
use App\Helpers\ResourceFilterHelper;

/**
 * Basic CSV request
 *
 * User: Roeland Werring
 * Date: 16/04/15
 * Time: 11:51
 *
 */
class BasicCsvRequest extends AbstractMethodRequest
{
    /**
     * @var bool Don't cache this
     */
    protected $cacheDays = false;

    /**
     * @var string Path to location of the dot processed csv files
     */
    protected $csvPath;

    /**
     * @var array Map with as keys CSV header string, and value the database column name.
     */
    protected $headerMap = [];

    /**
     * @var array Map with as keys CSV header string, and value the database column name.
     */
    protected $filterMap = [];


    /**
     * CSV file column delimiter
     */
    const DELIMITER = ';';

    protected $processFields = [];


    protected $classUrl = '';


    public function __construct($path)
    {
        $this->csvPath = $path . '/Files/*.csv';
        $this->strictStandardFields = false;
    }

    public function getResult()
    {
        $csvFiles = glob($this->csvPath);
        $resultArr = [];
        foreach ($csvFiles as $csvFile) {
            $resultArr = array_merge($this->processCsvFile($csvFile), $resultArr);
        }

        return $resultArr;
    }

    public function executeFunction()
    {
        //do nothing
    }

    private function processCsvFile($filePath)
    {
        $handle = fopen($filePath, 'r');
        $header = false;
        $returnArr = [];
        while (($data = fgetcsv($handle, null, self::DELIMITER)) !== false) {
            if (!$header) {
                $header = $this->mapHeader($data);
                continue;
            }
            $row = [];
            for ($index = 0; $index < count($data); $index++) {
                $value = $data[$index];
                $headerValue = $header[$index];
                // apply a filter if needed
                $value = $this->filterValue($headerValue, $value);
                $row[$headerValue] = $value;
            }

            $returnArr[] = $row;
        }


        if (!empty($this->processFields)) {
            foreach ($this->processFields as $item)
                $returnArr = $this->{"process_$item"}($returnArr);
        }

        return $returnArr;
    }

    /**
     * Map header as specified in header Map
     *
     * @param array $header
     *
     * @return array
     */
    private function mapHeader(Array $header)
    {
        $returnArr = [];
        foreach ($header as $headField) {
            $returnArr[] = preg_replace('/[^a-zA-Z0-9\s_]/i', '',(isset($this->headerMap[$headField]) ? $this->headerMap[$headField] : $headField));
        }
        return $returnArr;
    }

    /**
     * filter value if defined in filterMap (filters are static methods in ResourceFilterHelper)
     *
     * @param $headerValue
     * @param $value
     *
     * @return mixed
     */

    private function filterValue($headerValue, $value)
    {
        if (isset($this->filterMap[$headerValue])) {
            $filter = $this->filterMap[$headerValue];

            if (is_array($filter)) {
                foreach ($filter as $filterValue) {
                    $value = ResourceFilterHelper::$filterValue($value);
                }
            } else {
                $value = ResourceFilterHelper::$filter($value);
            }
            return $value;
        }
        return $value;
    }

}
