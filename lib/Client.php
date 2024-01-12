<?php

namespace Emetect;

use Exception;

class Client
{
    const API_BASE_URL = "https://emetec.pro/v1/";
    const SANDBOX_API_BASE_URL = "https://test.emetec.pro/v1/";

    protected static bool $_sandbox = false;
    protected string $entityId;
    private string $token;

    public function __construct($entityId, $token)
    {
        $this->entityId = $entityId;
        $this->token = $token;
    }

    public function sandboxMode(bool $isTest = false): Client
    {
        if ($isTest){
            self::$_sandbox = true;
        }

        return $this;
    }

    public function prepareCheckout(array $data): array
    {
        try{
            $data['entityId'] = $this->entityId;
            $data['paymentType'] = 'DB';

            $checkout = wp_remote_post(self::getBaseURL() . 'checkouts', [
                'headers' => array(
                    'content-type' => 'application/x-www-form-urlencoded',
                    'authorization' => 'Bearer '. $this->token ),
                'body' => $data
            ]);

            $body_checkout = wp_remote_retrieve_body( $checkout );
            $res = json_decode($body_checkout, true);
        }catch (Exception $exception){
            $res = null;
        }

        return $res;
    }

    public function createRegistration()
    {
        try{
            $data['entityId'] = $this->entityId;
            $data['createRegistration'] = 'true';

            if (self::$_sandbox){
                $data['testMode'] = 'EXTERNAL';
            }

            $checkout = wp_remote_post(self::getBaseURL() . 'checkouts', [
                'headers' => array(
                    'content-type' => 'application/x-www-form-urlencoded',
                    'authorization' => 'Bearer '. $this->token ),
                'body' => $data
            ]);

            $body_checkout = wp_remote_retrieve_body( $checkout );
            $res = json_decode($body_checkout, true);
        }catch (Exception $exception){
            $res = null;
        }

        return $res;
    }


    public function getPaymentStatus(string $checkoutId)
    {
        try{
            $status = wp_remote_get(self::getBaseURL() . "checkouts/$checkoutId/payment");
            $body_status = wp_remote_retrieve_body( $status );
            $res = json_decode($body_status, true);
        }catch (Exception $exception){
            $res = null;
        }

        return $res;
    }

    public function getRegistrationStatus(string $checkoutId)
    {
        try {
            $data['entityId'] = $this->entityId;

            $status = wp_remote_get(self::getBaseURL() . "checkouts/$checkoutId/registration", [
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'authorization' => 'Bearer '. $this->token
                ],
                'body' => $data
            ]);
            $body_status = wp_remote_retrieve_body( $status );
            $res = json_decode($body_status, true);
        }catch(Exception $exception){
            $res = null;
        }

        return $res;
    }

    public function paymentUseToken(string $id, array $data)
    {
        try{
            $data['entityId'] = $this->entityId;
            $data['paymentType'] = 'DB';

            if (self::$_sandbox){
                $data['testMode'] = 'EXTERNAL';
            }

            $payment = wp_remote_post(self::getBaseURL() . "registrations/$id/payments", [
                'headers' => array(
                    'content-type' => 'application/x-www-form-urlencoded',
                    'authorization' => 'Bearer '. $this->token ),
                'body' => $data
            ]);

            $body_checkout = wp_remote_retrieve_body( $payment );
            $res = json_decode($body_checkout, true);
        }catch (Exception $exception){
            $res = null;
        }

        return $res;
    }

    public static function getBaseURL(): string
    {
        if (self::$_sandbox)
            return self::SANDBOX_API_BASE_URL;
        return self::API_BASE_URL;
    }

}//end of class
