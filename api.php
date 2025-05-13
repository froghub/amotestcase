<?php

require_once 'env.php';
require_once 'logger.php';

class Api
{
    private $accessToken;
    private $refreshToken;
    private $expire;

    public function __construct()
    {
        $this->loadToken();
        if ($this->expire <= time()) {
            $this->tokenUpdate();
        }
    }

    private function loadToken()
    {
        if (file_exists('./data/token.json')) {
            $data = file_get_contents('./data/token.json');
            $data = json_decode($data, true);
            $this->accessToken = $data['access_token'];
            $this->refreshToken = $data['refresh_token'];
            $this->expire = $data['expired_at'];
        }
    }

    public function get($query)
    {
        Logger::log('GET query: ' . $query);
        $url = BASE_API_URL . $query;
        $headers = [];
        if($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);

        $resCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($resCode != 200) {
            Logger::log("Error $resCode while trying $query");
            Logger::log($out);
        }
        curl_close($curl);
        return $out;
    }

    public function post($query, $data)
    {
        Logger::log('POST query: ' . $query);
        Logger::log('DATA : ');
        Logger::log($data);
        $url = BASE_API_URL . $query;
        $headers = [
            'Content-Type: application/json'
        ];
        if($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_NUMERIC_CHECK));
        $out = curl_exec($curl);
        $resCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($resCode != 200) {
            Logger::log("Error $resCode while trying $query");
            Logger::log($out);
        }
        curl_close($curl);
        return $out;
    }

    public function processAuth()
    {
        $authorizationCode = $_GET['code'] ?? '';
        if (empty($authorizationCode)) {
            Logger::log('No auth code provided');
            die();
        }

        $url = 'oauth2/access_token';
        $payload = [
            'client_id' => $_GET['client_id'],
            'client_secret' => CLIENT_SECRET_KEY,
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'redirect_uri' => 'http://anvlbo.temp.swtest.ru/auth.php'
        ];

        $result = $this->post($url, $payload);
        $result = json_decode($result, true);
        if (!$result || json_last_error() !== JSON_ERROR_NONE) {
            Logger::log('Auth result error ' . $result);
            return;
        }
        $result['client_id'] = $_GET['client_id'];
        $result['expired_at'] = $result['expires_in'] + time();
        file_put_contents('./data/token.json', json_encode($result));
    }

    private function tokenUpdate()
    {
        if (file_exists('./data/token.json')) {
            $url = 'oauth2/access_token';
            $data = file_get_contents('./data/token.json');
            $data = json_decode($data, true);
            $payload = [
                'client_id' => $data['client_id'],
                'client_secret' => CLIENT_SECRET_KEY,
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'redirect_uri' => 'http://anvlbo.temp.swtest.ru/auth.php'
            ];

            $result = $this->post($url, $payload);
            Logger::log('Get token result ' . $result);
            $result = json_decode($result,true);
            
            if (!$result || json_last_error() !== JSON_ERROR_NONE) {
                Logger::log('Refresh result error ' . $result);
                return;
            }

            $data['expired_at'] = $result['expires_in'] + time();
            $data['refresh_token'] = $result['refresh_token'];
            $data['access_token'] = $result['access_token'];
            file_put_contents('./data/token.json', json_encode($data));

            $this->loadToken();
        }
    }
}
