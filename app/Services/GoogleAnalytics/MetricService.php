<?php

namespace App\Services\GoogleAnalytics;

use Google_Service_AnalyticsReporting_Metric;

class MetricService
{
    /**
     * @param array $goalInfo
     * @return array
     */
    public function getGoals($goalInfo)
    {
        $goalRequests = [];

        foreach ($goalInfo as $goal) {
            $goals = new Google_Service_AnalyticsReporting_Metric();
            $goals->setExpression("ga:goal" . $goal['id'] . "Completions");
            $goals->setAlias('Goal ' . $goal['name']);

            $goalRequests[] = $goals;
        }
        return $goalRequests;
    }

    /**
     * @return Google_Service_AnalyticsReporting_Metric
     */
    public function getPages()
    {
        $page = new Google_Service_AnalyticsReporting_Metric();
        $page->setExpression('ga:pageviews');
        $page->setAlias('Pageviews');

        return $page;
    }

}