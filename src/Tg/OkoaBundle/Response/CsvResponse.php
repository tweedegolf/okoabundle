<?php

namespace Tg\OkoaBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Dump a variable as a response.
 */
class CsvResponse extends Response
{
    /**
     * Construct using any number of variables which will all be var_dumped as
     * a response.
     */
    public function __construct($data = array(), $filename = 'export.csv')
    {
        $csvData = $this->arrayToCsv($data);

        $contentDisposition = sprintf('attachment; filename="%s"', $filename);

        parent::__construct($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => $contentDisposition
        ]);
    }

    /**
     * @param  array  $data
     * @return string
     */
    private function arrayToCsv($data)
    {
        $fp = tmpfile();
        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }
        $info = stream_get_meta_data($fp);
        $csvData = file_get_contents($info['uri']);
        fclose($fp);

        return $csvData;
    }
}
