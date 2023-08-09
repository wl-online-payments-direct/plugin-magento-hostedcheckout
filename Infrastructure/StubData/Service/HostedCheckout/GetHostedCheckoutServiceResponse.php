<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Infrastructure\StubData\Service\HostedCheckout;

/**
 * phpcs:disable Magento2.Functions.StaticFunction
 */
class GetHostedCheckoutServiceResponse
{
    public static function getData(string $hostedCheckoutId, string $incrementId = 'test01'): string
    {
        $responsePool = [
            '3254564310' => static::getHostedCheckoutResponse($hostedCheckoutId, $incrementId),
            '3254564311' => static::getHostedCheckoutResponseWithDiscount($hostedCheckoutId, $incrementId),
            '3254564312' => static::getHostedCheckoutResponseWithBundle($hostedCheckoutId, $incrementId),
            '3254564313' => static::getHostedCheckoutResponseWithConfigurable($hostedCheckoutId, $incrementId),
            '3254564314' => static::getHostedCheckoutResponseWithVirtual($hostedCheckoutId, $incrementId),
            '3254564315' => static::getErrorHostedCheckoutResponse($hostedCheckoutId, $incrementId),
            '3254564316' => static::getHostedCheckoutResponseWithSurcharging($hostedCheckoutId, $incrementId)
        ];

        return $responsePool[$hostedCheckoutId] ?? '{}';
    }

    public static function getHostedCheckoutResponse(string $hostedCheckoutId, string $incrementId = 'test01'): string
    {
        return <<<DATA
{
    "createdPaymentOutput": {
        "payment": {
            "hostedCheckoutSpecificOutput": {
                "hostedCheckoutId": "$hostedCheckoutId"
            },
            "id": "3254564310",
            "paymentOutput": {
                "acquiredAmount": {
                    "amount": 2123,
                    "currencyCode": "EUR"
                },
                "amountOfMoney": {
                    "amount": 2123,
                    "currencyCode": "EUR"
                },
                "cardPaymentMethodSpecificOutput": {
                    "authorisationCode": "1846132702",
                    "card": {
                        "bin": "424242",
                        "cardNumber": "************4242",
                        "countryCode": "99",
                        "expiryDate": "0523"
                    },
                    "token": "a4ef6af2-b66d-49b4-b121-7fef47f9f7ce",
                    "fraudResults": {
                        "avsResult": "0",
                        "cvvResult": "0",
                        "fraudServiceResult": "accepted"
                    },
                    "paymentProductId": 1,
                    "threeDSecureResults": {
                        "acsTransactionId": "A4DCDBC9-98CB-450D-9535-3D6BED79C2A5",
                        "authenticationStatus": "Y",
                        "cavv": "kANnMdSX47pGwDsg15UKaJeB6eJl",
                        "challengeIndicator": "no-challenge-requested",
                        "dsTransactionId": "D7B76870-0765-4F36-A9F1-C5B23D35D3E2",
                        "eci": "5",
                        "exemptionEngineFlow":
                         "low-value-not-applicable-sca-requested-challenge-indicator-no-challenge-requested",
                        "flow": "challenge",
                        "liability": "issuer",
                        "schemeEci": "05",
                        "version": "2.2.0",
                        "xid": "MzI2NDU5MDAyNg=="
                    }
                },
                "customer": {
                    "device": {
                        "ipAddressCountryCode": "99"
                    }
                },
                "paymentMethod": "card",
                "references": {
                    "merchantReference": "$incrementId"
                }
            },
            "status": "CAPTURED",
            "statusOutput": {
                "isAuthorized": false,
                "isCancellable": false,
                "isRefundable": true,
                "statusCategory": "COMPLETED",
                "statusCode": 9
            }
        },
        "paymentStatusCategory": "SUCCESSFUL"
    },
    "status": "PAYMENT_CREATED"
}
DATA;
    }

    public static function getHostedCheckoutResponseWithDiscount(
        string $hostedCheckoutId,
        string $incrementId = 'test01'
    ): string {
        return <<<DATA
{
    "createdPaymentOutput": {
        "payment": {
            "hostedCheckoutSpecificOutput": {
                "hostedCheckoutId": "$hostedCheckoutId"
            },
            "id": "3254564311",
            "paymentOutput": {
                "acquiredAmount": {
                    "amount": 708,
                    "currencyCode": "EUR"
                },
                "amountOfMoney": {
                    "amount": 708,
                    "currencyCode": "EUR"
                },
                "cardPaymentMethodSpecificOutput": {
                    "authorisationCode": "1846132702",
                    "card": {
                        "bin": "424242",
                        "cardNumber": "************4242",
                        "countryCode": "99",
                        "expiryDate": "0523"
                    },
                    "fraudResults": {
                        "avsResult": "0",
                        "cvvResult": "0",
                        "fraudServiceResult": "accepted"
                    },
                    "paymentProductId": 1,
                    "threeDSecureResults": {
                        "acsTransactionId": "A4DCDBC9-98CB-450D-9535-3D6BED79C2A5",
                        "authenticationStatus": "Y",
                        "cavv": "kANnMdSX47pGwDsg15UKaJeB6eJl",
                        "challengeIndicator": "no-challenge-requested",
                        "dsTransactionId": "D7B76870-0765-4F36-A9F1-C5B23D35D3E2",
                        "eci": "5",
                        "exemptionEngineFlow":
                         "low-value-not-applicable-sca-requested-challenge-indicator-no-challenge-requested",
                        "flow": "challenge",
                        "liability": "issuer",
                        "schemeEci": "05",
                        "version": "2.2.0",
                        "xid": "MzI2NDU5MDAyNg=="
                    }
                },
                "customer": {
                    "device": {
                        "ipAddressCountryCode": "99"
                    }
                },
                "paymentMethod": "card",
                "references": {
                    "merchantReference": "$incrementId"
                }
            },
            "status": "CAPTURED",
            "statusOutput": {
                "isAuthorized": false,
                "isCancellable": false,
                "isRefundable": true,
                "statusCategory": "COMPLETED",
                "statusCode": 9
            }
        },
        "paymentStatusCategory": "SUCCESSFUL"
    },
    "status": "PAYMENT_CREATED"
}
DATA;
    }

    public static function getHostedCheckoutResponseWithBundle(
        string $hostedCheckoutId,
        string $incrementId = 'test01'
    ): string {
        return <<<DATA
{
    "createdPaymentOutput": {
        "payment": {
            "hostedCheckoutSpecificOutput": {
                "hostedCheckoutId": "$hostedCheckoutId"
            },
            "id": "3254564312",
            "paymentOutput": {
                "acquiredAmount": {
                    "amount": 4953,
                    "currencyCode": "EUR"
                },
                "amountOfMoney": {
                    "amount": 4953,
                    "currencyCode": "EUR"
                },
                "cardPaymentMethodSpecificOutput": {
                    "authorisationCode": "1846132702",
                    "card": {
                        "bin": "424242",
                        "cardNumber": "************4242",
                        "countryCode": "99",
                        "expiryDate": "0523"
                    },
                    "token": "a4ef6af2-b66d-49b4-b121-7fef47f9f7ce",
                    "fraudResults": {
                        "avsResult": "0",
                        "cvvResult": "0",
                        "fraudServiceResult": "accepted"
                    },
                    "paymentProductId": 1,
                    "threeDSecureResults": {
                        "acsTransactionId": "A4DCDBC9-98CB-450D-9535-3D6BED79C2A5",
                        "authenticationStatus": "Y",
                        "cavv": "kANnMdSX47pGwDsg15UKaJeB6eJl",
                        "challengeIndicator": "no-challenge-requested",
                        "dsTransactionId": "D7B76870-0765-4F36-A9F1-C5B23D35D3E2",
                        "eci": "5",
                        "exemptionEngineFlow":
                         "low-value-not-applicable-sca-requested-challenge-indicator-no-challenge-requested",
                        "flow": "challenge",
                        "liability": "issuer",
                        "schemeEci": "05",
                        "version": "2.2.0",
                        "xid": "MzI2NDU5MDAyNg=="
                    }
                },
                "customer": {
                    "device": {
                        "ipAddressCountryCode": "99"
                    }
                },
                "paymentMethod": "card",
                "references": {
                    "merchantReference": "$incrementId"
                }
            },
            "status": "CAPTURED",
            "statusOutput": {
                "isAuthorized": false,
                "isCancellable": false,
                "isRefundable": true,
                "statusCategory": "COMPLETED",
                "statusCode": 9
            }
        },
        "paymentStatusCategory": "SUCCESSFUL"
    },
    "status": "PAYMENT_CREATED"
}
DATA;
    }

    public static function getHostedCheckoutResponseWithConfigurable(
        string $hostedCheckoutId,
        string $incrementId = 'test01'
    ): string {
        return <<<DATA
{
    "createdPaymentOutput": {
        "payment": {
            "hostedCheckoutSpecificOutput": {
                "hostedCheckoutId": "$hostedCheckoutId"
            },
            "id": "3254564313",
            "paymentOutput": {
                "acquiredAmount": {
                    "amount": 7075,
                    "currencyCode": "EUR"
                },
                "amountOfMoney": {
                    "amount": 7075,
                    "currencyCode": "EUR"
                },
                "cardPaymentMethodSpecificOutput": {
                    "authorisationCode": "1846132702",
                    "card": {
                        "bin": "424242",
                        "cardNumber": "************4242",
                        "countryCode": "99",
                        "expiryDate": "0523"
                    },
                    "token": "a4ef6af2-b66d-49b4-b121-7fef47f9f7ce",
                    "fraudResults": {
                        "avsResult": "0",
                        "cvvResult": "0",
                        "fraudServiceResult": "accepted"
                    },
                    "paymentProductId": 1,
                    "threeDSecureResults": {
                        "acsTransactionId": "A4DCDBC9-98CB-450D-9535-3D6BED79C2A5",
                        "authenticationStatus": "Y",
                        "cavv": "kANnMdSX47pGwDsg15UKaJeB6eJl",
                        "challengeIndicator": "no-challenge-requested",
                        "dsTransactionId": "D7B76870-0765-4F36-A9F1-C5B23D35D3E2",
                        "eci": "5",
                        "exemptionEngineFlow":
                         "low-value-not-applicable-sca-requested-challenge-indicator-no-challenge-requested",
                        "flow": "challenge",
                        "liability": "issuer",
                        "schemeEci": "05",
                        "version": "2.2.0",
                        "xid": "MzI2NDU5MDAyNg=="
                    }
                },
                "customer": {
                    "device": {
                        "ipAddressCountryCode": "99"
                    }
                },
                "paymentMethod": "card",
                "references": {
                    "merchantReference": "$incrementId"
                }
            },
            "status": "CAPTURED",
            "statusOutput": {
                "isAuthorized": false,
                "isCancellable": false,
                "isRefundable": true,
                "statusCategory": "COMPLETED",
                "statusCode": 9
            }
        },
        "paymentStatusCategory": "SUCCESSFUL"
    },
    "status": "PAYMENT_CREATED"
}
DATA;
    }

    public static function getHostedCheckoutResponseWithVirtual(
        string $hostedCheckoutId,
        string $incrementId = 'test01'
    ): string {
        return <<<DATA
{
    "createdPaymentOutput": {
        "payment": {
            "hostedCheckoutSpecificOutput": {
                "hostedCheckoutId": "$hostedCheckoutId"
            },
            "id": "3254564314",
            "paymentOutput": {
                "acquiredAmount": {
                    "amount": 1000,
                    "currencyCode": "EUR"
                },
                "amountOfMoney": {
                    "amount": 1000,
                    "currencyCode": "EUR"
                },
                "cardPaymentMethodSpecificOutput": {
                    "authorisationCode": "1846132702",
                    "card": {
                        "bin": "424242",
                        "cardNumber": "************4242",
                        "countryCode": "99",
                        "expiryDate": "0523"
                    },
                    "token": "a4ef6af2-b66d-49b4-b121-7fef47f9f7ce",
                    "fraudResults": {
                        "avsResult": "0",
                        "cvvResult": "0",
                        "fraudServiceResult": "accepted"
                    },
                    "paymentProductId": 1,
                    "threeDSecureResults": {
                        "acsTransactionId": "A4DCDBC9-98CB-450D-9535-3D6BED79C2A5",
                        "authenticationStatus": "Y",
                        "cavv": "kANnMdSX47pGwDsg15UKaJeB6eJl",
                        "challengeIndicator": "no-challenge-requested",
                        "dsTransactionId": "D7B76870-0765-4F36-A9F1-C5B23D35D3E2",
                        "eci": "5",
                        "exemptionEngineFlow":
                         "low-value-not-applicable-sca-requested-challenge-indicator-no-challenge-requested",
                        "flow": "challenge",
                        "liability": "issuer",
                        "schemeEci": "05",
                        "version": "2.2.0",
                        "xid": "MzI2NDU5MDAyNg=="
                    }
                },
                "customer": {
                    "device": {
                        "ipAddressCountryCode": "99"
                    }
                },
                "paymentMethod": "card",
                "references": {
                    "merchantReference": "$incrementId"
                }
            },
            "status": "CAPTURED",
            "statusOutput": {
                "isAuthorized": false,
                "isCancellable": false,
                "isRefundable": true,
                "statusCategory": "COMPLETED",
                "statusCode": 9
            }
        },
        "paymentStatusCategory": "SUCCESSFUL"
    },
    "status": "PAYMENT_CREATED"
}
DATA;
    }

    public static function getErrorHostedCheckoutResponse(
        string $hostedCheckoutId,
        string $incrementId = 'test01'
    ): string {
        return <<<DATA
{
    "createdPaymentOutput": {
        "payment": {
            "hostedCheckoutSpecificOutput": {
                "hostedCheckoutId": "$hostedCheckoutId"
            },
            "id": "3254564315",
            "paymentOutput": {
                "acquiredAmount": {
                    "amount": 1000,
                    "currencyCode": "EUR"
                },
                "amountOfMoney": {
                    "amount": 1000,
                    "currencyCode": "EUR"
                },
                "customer": {
                    "device": {
                        "ipAddressCountryCode": "99"
                    }
                },
                "paymentMethod": "card",
                "references": {
                    "merchantReference": "$incrementId"
                }
            },
            "status": "CANCELLED",
            "statusOutput": {
                "errors": [{
                    "errorCode": "30171001",
                    "category": "PAYMENT_PLATFORM_ERROR",
                    "code": "9999",
                    "httpStatusCode":402,"id": "CANCELLED_BY_CUSTOMER",
                    "message": "Payment cancelled by the customer.",
                    "retriable": false
                }],
                "isAuthorized": false,
                "isCancellable": false,
                "isRefundable": true,
                "statusCategory": "UNSUCCESSFUL",
                "statusCode": 9
            }
        },
        "paymentStatusCategory": "REJECTED"
    },
    "status": "CANCELLED_BY_CONSUMER"
}
DATA;
    }

    public static function getHostedCheckoutResponseWithSurcharging(
        string $hostedCheckoutId,
        string $incrementId = 'test01'
    ): string {
        return <<<DATA
{
    "createdPaymentOutput": {
        "payment": {
            "hostedCheckoutSpecificOutput": {
                "hostedCheckoutId": "$hostedCheckoutId"
            },
            "id": "3254564316",
            "paymentOutput": {
                "acquiredAmount": {
                    "amount": 2123,
                    "currencyCode": "EUR"
                },
                "amountOfMoney": {
                    "amount": 2123,
                    "currencyCode": "EUR"
                },
                "surchargeSpecificOutput":{
                    "surchargeAmount":{
                        "amount":1000,
                        "currencyCode":"EUR"
                    }
                },
                "cardPaymentMethodSpecificOutput": {
                    "authorisationCode": "1846132702",
                    "card": {
                        "bin": "424242",
                        "cardNumber": "************4242",
                        "countryCode": "99",
                        "expiryDate": "0523"
                    },
                    "token": "a4ef6af2-b66d-49b4-b121-7fef47f9f7ce",
                    "fraudResults": {
                        "avsResult": "0",
                        "cvvResult": "0",
                        "fraudServiceResult": "accepted"
                    },
                    "paymentProductId": 1,
                    "threeDSecureResults": {
                        "acsTransactionId": "A4DCDBC9-98CB-450D-9535-3D6BED79C2A5",
                        "authenticationStatus": "Y",
                        "cavv": "kANnMdSX47pGwDsg15UKaJeB6eJl",
                        "challengeIndicator": "no-challenge-requested",
                        "dsTransactionId": "D7B76870-0765-4F36-A9F1-C5B23D35D3E2",
                        "eci": "5",
                        "exemptionEngineFlow":
                         "low-value-not-applicable-sca-requested-challenge-indicator-no-challenge-requested",
                        "flow": "challenge",
                        "liability": "issuer",
                        "schemeEci": "05",
                        "version": "2.2.0",
                        "xid": "MzI2NDU5MDAyNg=="
                    }
                },
                "customer": {
                    "device": {
                        "ipAddressCountryCode": "99"
                    }
                },
                "paymentMethod": "card",
                "references": {
                    "merchantReference": "$incrementId"
                }
            },
            "status": "CAPTURED",
            "statusOutput": {
                "isAuthorized": false,
                "isCancellable": false,
                "isRefundable": true,
                "statusCategory": "COMPLETED",
                "statusCode": 9
            }
        },
        "paymentStatusCategory": "SUCCESSFUL"
    },
    "status": "PAYMENT_CREATED"
}
DATA;
    }
}
