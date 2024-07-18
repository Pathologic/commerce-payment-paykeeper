# commerce-payment-paykeeper
Платежный плагин Paykeeper для Evolution CMS Commerce.

Документация: https://docs.paykeeper.ru/

Параметры:
* shop_id - значение из ссылки https://{shop_id}.server.paykeeper.ru
* secret_key - секретный ключ из личного кабинета

Код налога может задаваться в свойстве товара `[meta][tax]`, по умолчанию берется из настройки плагина.
Код типа товара может задаваться в свойстве товара `[meta][item_type]`, по умолчанию goods (товар).

В личном кабинете нужно настроить:
* ссылка для колбэка - https://sitename.ru/commerce/paykeeper/payment-process
* ссылка для успешной оплаты - https://sitename.ru/commerce/paykeeper/payment-success
* ссылка для неуспешной оплаты - https://sitename.ru/commerce/paykeeper/payment-failed
  
