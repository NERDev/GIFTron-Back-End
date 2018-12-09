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
        $raw_response = file_get_contents($url, false, $context) ?? exit($this->errors[0]);
        $response = json_decode($raw_response) ?? exit($this->errors[1]);
        return $response;
    }

    function get($url)
    {
        $token = $this->accessToken ?? $_GET['token'];

        $options = [
            'http' => [
                "header" => "Authorization: Bearer {$token}\r\n"
            ]
        ];

        $context  = stream_context_create($options);
        $raw_response = file_get_contents($url, false, $context) ?? exit($this->errors[0]);
        $response = json_decode($raw_response) ?? exit($this->errors[1]);
        return $response;
    }

    function getAccessToken($grant_type, $code)
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

        $this->refreshToken = $response->refresh_token;
        return $this->accessToken = $response->access_token;
    }
}