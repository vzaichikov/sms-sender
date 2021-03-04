## Sms Sender
Библиотека для отправки SMS

#### Установка:
```shell script
composer require smsclub/sms-sender
```

#### Использование:
```php
<?php
use SmsSender\Exceptions\SmsSenderException;
use SmsSender\SmsSender;

/**
 * Токен отправителя
 * Токен можно получить на странице профиля в ЛК: https://my.smsclub.mobi/profile
 */
$token = 'TOUCH_YOUR_TOKEN';

try {
    $sender = new SmsSender($token);

    // получаем баланс пользователя
    $balance = $sender->getBalance();
    
    // получаем список доступных подписей
    $signatures = $sender->getSignatures();

    // дамп с балансом и подписями для тестирования
    var_dump($balance, $signatures);
    
    // отправляем сообщение, используя первую из подписей в списке
    // если у Вас больше одной подписи, её лучше указать явно
    $smsInfo = $sender->smsSend($signatures[0], 'Тестовое сообщение', ['380YYXXXXXXX', '380YYXXXXXXX']);
    
    // получаем статусы отправленных сообщений
    // важно: в данном примере мы получаем статусы сразу после отправки сообщения, потому статус всегда будет ENROUTE
    $statusCodes = array_keys($smsInfo);
    $result = $sender->smsStatus($statusCodes);
    
    // получаем баланс пользователя после отправки сообщений
    $balance = $sender->getBalance();

    // дамп статусов и баланса
    var_dump($result, $balance);

} catch (SmsSenderException $e) {
    echo $e->getMessage();
}
```

Информация о возвращаемых статусах и формате данных в [описании API SmsClub](https://smsclub.mobi/ru/api)