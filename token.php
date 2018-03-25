<?php
include 'vendor/autoload.php';
include 'access.php';

use Strava\API\OAuth;
use Strava\API\Exception;

try {
    $options = [
        'clientId'     => $config['CLIENT_ID'],
        'clientSecret' => $config['CLIENT_SECRET'],
        'redirectUri'  => 'http://localhost/callback.php'
    ];
    $oauth = new OAuth($options);

    if (!isset($_GET['code'])) {
        print '<a href="'.$oauth->getAuthorizationUrl().'">connect</a>';
    } else {
        $token = $oauth->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
        print $token;
    }
} catch(Exception $e) {
    print $e->getMessage();
}