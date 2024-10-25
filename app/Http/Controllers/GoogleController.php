<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(resource_path('data/google-credentials.json'));
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->setRedirectUri(route('oauth2callback'));
    }

    public function redirectToGoogle()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $this->client->authenticate($request->code);
        session(['google_token' => $this->client->getAccessToken()]);
        return redirect()->route('home');
    }

    //TEST
    public function createEvent()
    {
        $this->client->setAccessToken(session('google_token'));

        $service = new Calendar($this->client);

        $event = new Event([
            'summary' => 'Test Event',
            'start' => ['dateTime' => '2024-09-02T10:00:00Z'],
            'end' => ['dateTime' => '2024-09-02T11:00:00Z'],
            // Add other event details here...
        ]);

        $calendarId = 'primary';
        $service->events->insert($calendarId, $event);

        return "Event created successfully!";
    }
}
