<?php

class OAuth2Client
{
    private $errors;

    function __construct($params)
    {
        $this->errors = [
            "Can't talk to the server properly",
            "Cannot Decode Response: Check Permissions or Reauthorize"
        ];
        
        foreach ($params as $param => $value) {
            extract([$param]);
            $this->$param = $value;
        }
    }

    function post($url, $data)
    {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ]
        ];

        $context  = stream_context_create($options);
        $raw_response = file_get_contents($url, false, $context);
        $response = json_decode($raw_response);
        return $response;
    }

    function postJSON($url, $data, $token = null, $tokentype = 'Bearer')
    {
        //error_reporting(E_ALL); ini_set('display_errors', 1);
        $data = json_encode($data);
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\nAuthorization: $tokentype {$token}\r\nContent-Length: " . strlen($data),
                'method' => 'POST',
                'content' => $data
            ]
        ];

        $context = stream_context_create($options);
        $raw_response = file_get_contents($url, false, $context);
        $response = json_decode($raw_response);
        return $response;
    }

    function get($url, $token, $tokentype = 'Bearer')
    {
        $options = [
            'http' => [
                "header" => "Authorization: $tokentype {$token}\r\n"
            ]
        ];

        $context  = stream_context_create($options);
        $raw_response = file_get_contents($url, false, $context);
        $response = json_decode($raw_response);
        return $response;
    }

    function getAccessToken($grant_type, $code = null)
    {
        $data = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => $grant_type,
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => 'identify'
        ];
        
        $response = $this->post($this->urlAccessToken, $data);

        //$this->refreshToken = $response->refresh_token;
        return $response->access_token;
    }
}