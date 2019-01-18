<?php

namespace App\Services\GoogleAnalytics;


class FormatService
{
    /**
     * @param array $reportsCollection
     * @return array
     */
    function getGoals($reportsCollection) : array
    {
        $result = [];
        foreach ($reportsCollection as $reports) {


            /** @var \Google_Service_AnalyticsReporting_GetReportsResponse $report */
            foreach ($reports as $report) {
                $reportResult = [];
                foreach ($report->columnHeader->metricHeader->metricHeaderEntries as $metric) {
                    $reportResult[] = [
                        'name' => $metric->name
                    ];
                }
                foreach ($report->data->rows as $row) {
                    for($i = 0; $i < count($reportResult); $i++) {
                        $reportResult[$i]['value'] = $row->metrics[0]->values[$i];
                    }

                }
                $result = array_merge($result, $reportResult);
            }
        }
        return $result;
    }

    /**
     * @param array $reportsCollection
     * @return array
     */
    public function getPages($reportsCollection) : array
    {
        $pages = [];
        foreach ($reportsCollection as $reports) {
            /** @var \Google_Service_AnalyticsReporting_GetReportsResponse $report */
            foreach ($reports as $report) {
                foreach ($report->data->rows as $row) {
                    $dimensions = '';
                    foreach ($row->dimensions as $dimension) {
                        $dimensions .= $dimension . ' ';
                    }
                    $pages[] = [
                        'name' => 'Page Views of ' . $dimensions,
                        'value' => $row->metrics[0]->values[0]
                    ];
                }
            }
        }

        return $pages;
    }
}