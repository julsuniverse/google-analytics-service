<?php

namespace App\Services\GoogleAnalytics;

use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_ReportRequest;

class ReportService
{
    /** @var MetricService $metrics */
    private $metrics;
    /** @var DimensionService $dimensions */
    private $dimensions;

    /**
     * ReportService constructor.
     * @param MetricService $metricService
     * @param DimensionService $dimensionService
     */
    public function __construct(MetricService $metricService, DimensionService $dimensionService)
    {
        $this->metrics = $metricService;
        $this->dimensions = $dimensionService;
    }

    /**
     * Queries the Analytics Reporting API V4.
     * @param Google_Service_AnalyticsReporting $analytics
     * @param array $goal_info
     * @param string $viewId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getGoalsReport($analytics, $goal_info, $viewId, $startDate, $endDate) : array
    {
        $dateRange = $this->setDateRange($startDate, $endDate);
        $goals = $this->metrics->getGoals($goal_info);

        $requests = [];
        for($offset = 0; $offset < ceil(count($goal_info) / 10); $offset++) {
            $requests[] = $this->createBasicRequest($viewId, $dateRange, array_slice($goals, $offset == 0 ? $offset * 9 : $offset * 9 + 1, 10));
        }

        return $this->offsetReports($requests, $analytics);
    }

    /**
     * @param Google_Service_AnalyticsReporting $analytics
     * @param array $goal_info
     * @param string $viewId
     * @param string $startDate
     * @param string $endDate
     * @param string|bool $city
     * @return array
     */
    public function getPagesReport($analytics, $goal_info, $viewId, $startDate, $endDate, $city = false) : array
    {
        $dateRange = $this->setDateRange($startDate, $endDate);
        $pages = $this->metrics->getPages();

        $requests = [];
        foreach ($goal_info as $url) {

            $request = $this->createBasicRequest($viewId, $dateRange, $pages);

            $dimensions = [];
            $filters = [];

            $dimensions[] = $this->dimensions->create('ga:pagePath');
            $filters[] = $this->dimensions->filterDimension('ga:pagePath', $url['url']);

            if($city) {
                $dimensions[] = $this->dimensions->create('ga:city');
                $filters[] = $this->dimensions->filterDimension('ga:city', $city);
            }

            $request->setDimensions([$dimensions]);

            $request->setDimensionFilterClauses([$filters]);
            $requests[] = $request;
        }

        return $this->offsetReports($requests, $analytics);
    }

    /**
     * Create the DateRange object.
     * Start date: https://developers.google.com/analytics/devguides/reporting/core/v3/reference#startDate
     * End date: https://developers.google.com/analytics/devguides/reporting/core/v3/reference#endDate
     * @param string $start
     * @param string $end
     * @return Google_Service_AnalyticsReporting_DateRange
     */
    private function setDateRange(string $start, string $end) : Google_Service_AnalyticsReporting_DateRange
    {
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($start);
        $dateRange->setEndDate($end);

        return $dateRange;
    }

    /**
     * Create the ReportRequest object.
     *
     * @param string $viewId
     * @param Google_Service_AnalyticsReporting_DateRange $dateRange
     * @param $metrics
     * @return Google_Service_AnalyticsReporting_ReportRequest
     */
    private function createBasicRequest($viewId, $dateRange, $metrics) : Google_Service_AnalyticsReporting_ReportRequest
    {
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$metrics]);

        return $request;
    }

    /**
     * @param array $requests
     * @param Google_Service_AnalyticsReporting $analytics
     * @return array
     */
    public function offsetReports($requests, $analytics) : array
    {
        $reports = [];
        try {
            for($offset = 0; $offset < count($requests); $offset++) {
                $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
                $body->setReportRequests([array_slice($requests, $offset * 5, 5)]);
                $params = [
                    'quotaUser' => \Auth::user()->id
                ];
                $reports[] = $analytics->reports->batchGet($body, $params);
            }
        } catch (\Google_Exception $e) {
            if($e->getCode() == 403 || $e->getCode() == 429) {
                throw new \RuntimeException('Quota exceeded', 429);
            }
        }

        return $reports;
    }
}