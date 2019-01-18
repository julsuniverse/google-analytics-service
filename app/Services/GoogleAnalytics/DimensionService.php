<?php

namespace App\Services\GoogleAnalytics;


use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;

class DimensionService
{
    /**
     * Available Dimensions https://developers.google.com/analytics/devguides/reporting/core/dimsmets
     *
     * @param string $name
     * @return Google_Service_AnalyticsReporting_Dimension
     */
    public function create($name) : Google_Service_AnalyticsReporting_Dimension
    {
        $dimension = new Google_Service_AnalyticsReporting_Dimension();
        $dimension->setName($name);

        return $dimension;
    }

    /**
     * @param string $dimensionName
     * @param $filterBy
     * @return Google_Service_AnalyticsReporting_DimensionFilterClause
     */
    public function filterDimension($dimensionName, $filterBy) : Google_Service_AnalyticsReporting_DimensionFilterClause
    {
        $dimensionFilter = new Google_Service_AnalyticsReporting_DimensionFilterClause();
        $filter = new Google_Service_AnalyticsReporting_DimensionFilter();
        $filter->setDimensionName($dimensionName);
        $filter->setOperator('ENDS_WITH');
        $filter->setExpressions($filterBy);
        $dimensionFilter->setFilters($filter);

        return $dimensionFilter;
    }
}