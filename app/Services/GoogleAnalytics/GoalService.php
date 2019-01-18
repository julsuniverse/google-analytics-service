<?php

namespace App\Services\GoogleAnalytics;

use App\Models\Company;
use Google_Client;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;

class GoalService
{
    /** @var string $viewId */
    private $viewId;
    /** @var FormatService $format */
    private $format;
    /** @var ReportService $report */
    private $report;

    /**
     * GoalService constructor.
     * @param FormatService $formatService
     * @param ReportService $reportService
     */
    public function __construct(FormatService $formatService, ReportService $reportService)
    {
        $this->format = $formatService;
        $this->report = $reportService;
    }

    /**
     * @param Company $company
     * @param string $viewId
     * @param string $startDate
     * @param string $endDate
     * @param string|bool $city
     * @return array
     * @throws \Google_Exception
     */
    public function getGoals($company, $viewId, $startDate = '2018-12-01', $endDate = '2030-01-01', $city = false) : array
    {
        $access_token = json_decode($company->ga_token, true);
        $this->viewId = $viewId;

        $client = $this->getClient();
        $client->setAccessToken($access_token);

        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($access_token['refresh_token']);
            $company->ga_token = json_encode($client->getAccessToken());
            $company->save();
        }

        // Create an authorized analytics service object.
        $analytics = new Google_Service_AnalyticsReporting($client);

        // Get goals info
        $goalInfo = $this->getGoalsInfo($client);

        // Call the Analytics Reporting API V4
        $goals = $this->report->getGoalsReport($analytics, $goalInfo, $viewId, $startDate, $endDate);
        $pages = $this->report->getPagesReport($analytics, $goalInfo, $viewId, $startDate, $endDate, $city);

        // Get the goals

        $goalsArray = $this->format->getGoals($goals);
        $pageViewsArray = $this->format->getPages($pages);

        $allGoals = array_merge($goalsArray, $pageViewsArray);

        return $allGoals;
    }

    /**
     * @param Google_Client $client
     * @return array
     */
    public function getGoalsInfo($client) : array
    {
        $service = new Google_Service_Analytics($client);
        $goalInfo = [];

        // Request user accounts
        $accounts = $service->management_accountSummaries->listManagementAccountSummaries();

        foreach ($accounts->getItems() as $item) {
            /** @var \Google_Service_Analytics_WebPropertySummary $wp */
            foreach ($item->getWebProperties() as $wp) {
                $views = $wp->getProfiles();
                if (is_null($views)) {
                    continue;
                }
                foreach ($wp->getProfiles() as $view) {
                    if ($view->id != $this->viewId) {
                        continue;
                    }
                    $goals = $service->management_goals->listManagementGoals($item->id, $wp->id, $this->viewId);
                    foreach ($goals->items as $goal) {
                        $goalInfo[] = [
                            'id' => $goal->id,
                            'name' => $goal->name,
                            'type' => $goal->type,
                            'url' => $goal->urlDestinationDetails->url
                        ];
                    }
                }
            }
        }
        return $goalInfo;
    }

    /**
     * Create the client object and set the authorization configuration
     * from the client_secrets.json you downloaded from the Developers Console.
     * @return Google_Client
     * @throws \Google_Exception
     */
    public function getClient() : Google_Client
    {
        $client = new Google_Client();
        $client->setAuthConfig(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_CLIENT_REDIRECT_URL'));
        $client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
        $client->setAccessType("offline");
        $client->setApprovalPrompt('force');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }
}
