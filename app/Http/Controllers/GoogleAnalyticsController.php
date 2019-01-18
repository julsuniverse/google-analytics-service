<?php

namespace App\Http\Controllers\Analytics;

use App\Services\GoogleAnalytics\GoalService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;

class GoogleAnalyticsController extends Controller
{
    /** @var GoalService $analytics */
    private $analytics;
    /** @var string $baseUrl */
    private $baseUrl;

    /**
     * GAController constructor.
     * @param GoalService $reportService
     */
    public function __construct(GoalService $reportService)
    {
        $this->analytics = $reportService;
        $this->baseUrl = request()->getSchemeAndHttpHost() . '/';
    }

    public function getGoals()
    {
        // If the user has already authorized this app then get an access token
        // else redirect to ask the user to authorize access to Google Analytics.
        $access_token = isset($_COOKIE['access_token']) ? json_decode($_COOKIE['access_token'], true) : null;
        if (!$access_token) {
            $redirect_uri = $this->baseUrl . 'ga-redirect';
            return Redirect::to($redirect_uri);
        }
        try {
            $viewId = '1123456789';
            $startDate = '7daysAgo';
            $endDate = 'yesterday';
            $city = 'Kyiv';
            $goals = $this->analytics->getGoals($access_token, $viewId, $startDate, $endDate);
            return $goals;
        } catch (\Google_Exception $e) {
            //TODO: return error message
            throw new \Google_Exception($e->getMessage());
        }

    }

    /**
     * @throws \Google_Exception
     */
    public function gaRedirect()
    {
        try {
            $client = $this->clientService->getClient();
        } catch (\Google_Exception $e) {
            throw new \Google_Exception($e->getMessage());
        }

        // Handle authorization flow from the server.
        if (!isset($_GET['code'])) {
            $auth_url = $client->createAuthUrl();
            return Redirect::to($auth_url);
        }
        $client->authenticate($_GET['code']);
        SetCookie('access_token', json_encode($client->getAccessToken()), time() + 120 * 60);

        $redirect_uri = $this->baseUrl . 'get-goals';
        return Redirect::to($redirect_uri);
    }
}

