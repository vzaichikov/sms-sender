<?php

namespace SmsSender;

use SmsSender\Exceptions\SmsSenderException;

/**
 * Class SmsClub
 * @package SmsClub\SmsSender
 */
class SmsSender
{
    const SMSCLUB_API_HOST = 'https://im.smsclub.mobi';
    const SMSCLUB_URL_SMS_SEND = '/sms/send';
    const SMSCLUB_URL_SMS_STATUS = '/sms/status';
    const SMSCLUB_URL_SMS_ORIGINATOR = '/sms/originator';
    const SMSCLUB_URL_SMS_BALANCE = '/sms/balance';
    const SMSCLUB_ARRAY_LIMIT = 100;

    private $token;
    private $integrationId;


    /**
     * SmsClub constructor
     *
     * @param string $token         - токен отправителя - можно получить на странице профиля в ЛК: https://my.smsclub.mobi/profile
     * @param int $integrationId    - идентификатор разработчика для использования реферальной системы (не обязательное свойство)
     * @throws SmsSenderException
     */
    public function __construct($token, $integrationId = 0)
    {
        $this->checkToken($token)->checkIntegrationId($integrationId);

        $this->token = $token;
        $this->integrationId = $integrationId;

        return $this;
    }

    /**
     * Отправка SMS сообщения
     *
     * @param string $alphaName             - альфа-имя отправителя
     * @param string $message               - текст сообщения
     * @param array|string|int $phoneList   - массив номеров, можно отправлять до 100 номеров за запрос
     * @return mixed
     * @throws SmsSenderException
     */
    public function smsSend($alphaName, $message, $phoneList)
    {
        $this->checkAlphaName($alphaName)->checkMessage($message);
        $phoneListPrepared = $this->preparePhone($phoneList);
        $this->checkDuplicate($phoneListPrepared);

        $data = [
            'phone' => $phoneListPrepared,
            'message' => $message,
            'src_addr' => $alphaName,
        ];

        if (!empty($this->integrationId)) {
            $data['integration_id'] = $this->integrationId;
        }

        return (array) $this->sendCommand(self::SMSCLUB_URL_SMS_SEND, $data);
    }

    /**
     * Получение статуса сообщений
     *
     * @param array|string|int $smsIdList - массив ID сообщений, по которым нужно получить статус (не более 100)
     * @return mixed
     * @throws SmsSenderException
     */
    public function smsStatus($smsIdList)
    {
        return $this->sendCommand(self::SMSCLUB_URL_SMS_STATUS, [
            'id_sms' => $this->prepareSmsIdList($smsIdList),
        ]);
    }

    /**
     * Получение списка альфа-имен пользователя
     *
     * @return mixed
     * @throws SmsSenderException
     */
    public function getSignatures()
    {
        return $this->sendCommand(self::SMSCLUB_URL_SMS_ORIGINATOR);
    }

    /**
     * Получение баланса пользователя
     *
     * @return mixed
     * @throws SmsSenderException
     */
    public function getBalance()
    {
        return $this->sendCommand(self::SMSCLUB_URL_SMS_BALANCE);
    }


    /**
     * @param $token
     * @return $this
     * @throws SmsSenderException
     */
    private function checkToken($token)
    {
        if (!is_string($token)) {
            throw new SmsSenderException('Wrong token. Must be string');
        }

        if (empty($token)) {
            throw new SmsSenderException('Token can\'t be empty');
        }

        return $this;
    }

    /**
     * @param $integrationId
     * @return $this
     * @throws SmsSenderException
     */
    private function checkIntegrationId($integrationId)
    {
        if (!is_numeric($integrationId)) {
            throw new SmsSenderException('Wrong type of integration ID. Should be numeric');
        }

        return $this;
    }

    /**
     * @param $alphaName
     * @return $this
     * @throws SmsSenderException
     */
    private function checkAlphaName($alphaName)
    {
        if (!preg_match('/^[\w\s.-]{1,11}$/', $alphaName)) {
            throw new SmsSenderException('Wrong alpha-name');
        }

        return $this;
    }

    /**
     * @param $message
     * @return $this
     * @throws SmsSenderException
     */
    private function checkMessage($message)
    {
        if (!is_string($message)) {
            throw new SmsSenderException('Message must be string');
        }

        return $this;
    }

    /**
     * @param $phone
     * @return $this
     * @throws SmsSenderException
     */
    private function checkPhone($phone)
    {
        if (!preg_match('/^380\d{9}$/', $phone)) {
            throw new SmsSenderException('Wrong phone number: ' . $phone);
        }

        return $this;
    }

    /**
     * @param $smsId
     * @return $this
     * @throws SmsSenderException
     */
    private function checkSmsId($smsId)
    {
        if (!is_numeric($smsId)) {
            throw new SmsSenderException('Wrong SMS ID: ' . $smsId);
        }

        return $this;
    }

    /**
     * @param $array
     * @throws SmsSenderException
     */
    private function checkDuplicate($array)
    {
        if (count($array) != count(array_unique($array, SORT_STRING))) {
            $duplicates = array_filter(array_count_values($array), function ($count) {
                return $count > 1;
            });

            $duplicates = array_keys($duplicates);
            $duplicatesString = implode(', ', $duplicates);

            throw new SmsSenderException('You have duplicate in array: ' . $duplicatesString);
        }
    }

    /**
     * @param $phoneList
     * @return array|string[]
     * @throws SmsSenderException
     */
    private function preparePhone($phoneList)
    {
        if (!is_array($phoneList)) {
            $phoneList = [$phoneList];
        } elseif (count($phoneList) > self::SMSCLUB_ARRAY_LIMIT) {
            throw new SmsSenderException(
                'One-time sending limit has been exceeded. Should be no more than '
                . self::SMSCLUB_ARRAY_LIMIT
                . ' numbers in the array'
            );
        }

        return array_map(function ($phone) {
            $phone = preg_replace('/[^\d]/', '', $phone);
            $this->checkPhone($phone);

            return $phone;
        }, $phoneList);
    }

    /**
     * @param $smsIdList
     * @return array
     * @throws SmsSenderException
     */
    private function prepareSmsIdList($smsIdList)
    {
        if (!is_array($smsIdList)) {
            $smsIdList = [$smsIdList];
        } elseif (count($smsIdList) > self::SMSCLUB_ARRAY_LIMIT) {
            throw new SmsSenderException(
                'One-time sending limit has been exceeded. Should be no more than '
                . self::SMSCLUB_ARRAY_LIMIT
                . ' IDs in the array'
            );
        }

        return array_map(function ($smsId) {
            $this->checkSmsId($smsId);

            return $smsId;
        }, $smsIdList);
    }

    /**
     * @param string $url
     * @param array $data
     * @return mixed
     * @throws SmsSenderException
     */
    private function sendCommand($url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Authorization: Bearer ' . $this->token
        ]);
        curl_setopt($ch, CURLOPT_URL, self::SMSCLUB_API_HOST . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);

        if (!$result) {
            $error = curl_error($ch);

            throw new SmsSenderException($error);
        }

        curl_close($ch);

        return $this->formatResponse($result);
    }

    /**
     * @param string $jsonResponse
     * @return mixed
     * @throws SmsSenderException
     */
    private function formatResponse($jsonResponse)
    {
        $response = json_decode($jsonResponse);

        if (isset($response->success_request) && isset($response->success_request->info)) {
            return $response->success_request->info;
        }

        throw new SmsSenderException('Error: ' . $jsonResponse);
    }
}