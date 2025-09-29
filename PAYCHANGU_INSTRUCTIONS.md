# PayChangu Gateway Instructions

# USEFUL LINKS
CHECK THE FOLLOWING LINKS BEFORE ATTEMPTING ANYTHING

## TESTING
https://developer.paychangu.com/docs/test

## PAYCHANGU ERRORS
https://developer.paychangu.com/docs/paychangu-errors

## WEBHOOKS
https://developer.paychangu.com/docs/webhooks

## TRANSATION VERIFICATION
https://developer.paychangu.com/docs/transaction-verification

## BALANCES
https://developer.paychangu.com/docs/balance

## INTEGRATION OPTIONS
https://developer.paychangu.com/docs/introduction

### INLINE CHEKOUT
https://developer.paychangu.com/docs/inline-popup

### STANDARD CHECKOUT
https://developer.paychangu.com/docs/standard-checkout

### HTML CHECKOUT
https://developer.paychangu.com/docs/html-checkout

## Overview
Welcome to PayChangu Documentation

Welcome to the PayChangu Developer Documentation! Here, you’ll discover how to create seamless and innovative payment solutions using the powerful PayChangu API.

Provide your customers with the option to pay by card or mobile money, ensuring a seamless checkout experience. Collect payment information quickly and securely.

⚙️
Before you can do anything
Make sure you have a PayChangu account sign up by clicking here. With this account, you can start the integration process and test sending transactions to PayChangu.

## Getting Started
### PayChangu Errors
PayChangu API errors can be grouped into three main categories. The validation errors, PayChangu errors, and provider errors.

They are usually returned in this format with the 400 HTTP status code:

JSON

{
"message": {
"callback_url": [
"The callback url field is required."
]
},
"status": "failed",
"data": null
}
Provider Errors
Provider errors are returned from the payment provider. Below are some possible provider errors you can expect:

DECLINED&#xA;
Transaction declined.

TIMED_OUT

Response timed out

EXPIRED_CARD&#xA;
Transaction declined due to expired card

INSUFFICIENT_FUNDS

Transaction declined due to insufficient funds

AUTHENTICATION_FAILED

3DS authentication failed

NOT_ENROLLED_3D_SECURE&#xA;
Cardholder is not enrolled in 3D Secure

EXCEEDED_RETRY_LIMIT

Transaction retry limit exceeded

CARD_NOT_ENROLLED

The card is not enrolled for 3DS authentication

AUTHENTICATION_NOT_AVAILABLE

Authentication is not currently available

AUTHENTICATION_ATTEMPTED

Authentication was attempted but the card issuer did not perform the authentication

CARD_DOES_NOT_SUPPORT_3DS

The card does not support 3DS authentication

### Webhooks
Webhooks play a crucial role in your payment integration. They enable PayChangu to inform you about events occurring in your account, such as a successful payment or a failed transaction.

A webhook URL is an endpoint on your server designed to receive notifications about these events. When an event takes place, a POST request will be made to that endpoint with a JSON body that includes details about the event, such as the event type and the related data.

When to use webhooks
Webhooks are compatible with all types of payment methods. By setting up a webhook, you allow us to inform you when payments are completed. Within your webhook endpoint, you can then:

Email a customer when a payment fails.
Update your order records when the status of a pending payment is updated to successful.
Structure of a webhook payload
Here are some sample webhook payloads for transfers and payments:

#### Transfer Webhook Payload

```JSON
{
  "event_type": "api.charge.payment",
  "currency": "MWK",
  "amount": 1000,
  "charge": "20",
  "mode": "test",
  "type": "Direct API Payment",
  "status": "success",
  "charge_id": "5d676fg",
  "reference": "71308131545",
  "authorization": {
    "channel": "Mobile Bank Transfer",
    "card_details": null,
    "bank_payment_details": {
      "payer_bank_uuid":"82310dd1-ec9b-4fe7-a32c-2f262ef08681"
      "payer_bank": "National Bank of Malawi",
      "payer_account_number": "10010000",
      "payer_account_name": "Jonathan Manda"
    },
    "mobile_money": null,
    "completed_at": "2025-01-15T19:53:18.000000Z"
  },
  "created_at": "2025-01-15T19:53:18.000000Z",
  "updated_at": "2025-01-15T19:53:18.000000Z"
}                                                       
```

#### Payment Webhook Payload

```JSON
{
  "event_type":"api.payout",
    "charge_id":"4567tfuty",
    "reference":"54438943842",
    "first_name":null,
    "last_name":null,
    "email":null,
    "currency":"MWK",
    "amount":1000,
    "charge":"0",
    "mode":"live",
    "type":"API Payout",
    "status":"success",
    "recipient_account_details":{"bank_name":"National Bank of Malawi",
    "bank_uuid":"82310dd1-ec9b-4fe7-a32c-2f262ef08681",
    "account_name":"Nohaata Seven",
    "account_number":"1007534422"
  }
}
```
Implementing a Webhook
Creating a webhook endpoint on your server is similar to writing any other API endpoint, but there are a few important details to keep in mind:

Authentication of Webhook Requests
To ensure that the request received on your webhook is actually coming from PayChangu, it is necessary to carry out a validation check on the incoming request. This is achieved through the “Signature” header, which is always present in the header of each webhook request. The value of the “Signature” header is a SHA-256 HMAC hash of the webhook payload sent to your server.

To verify the validity of the webhook (confirming it is from PayChangu), generate the SHA-256 HMAC hash of the webhook payload using your web secret key, which is generated on your dashboard. The resulting hash should then be compared with the value of the “Signature” header in the request headers. If the generated hash matches the “Signature” header value, the webhook is a valid request from PayChangu. Otherwise, it is an invalid request that has either been tampered with or originated from an untrusted source.

Here is an example of how to verify the webhook signature in PHP:

```php
function handleWebhookEvent(){
// retrieve request body
$payload = file_get_contents('php://input');
// retrieve all headers
$headers = getallheaders();
$computedSignature = hash_hmac('sha256', $payload, $webhookSecret);
/* change the value of webhookSecret to the webhook secret generated on your
merchant dashboard */
$webhookSecret = 'your_webhook_secret_key';
// generate hash of the webhook payload using the secret key
$computedSignature = hash_hmac('sha256', $payload, $webhookSecret);
// compare the computed signature of the incoming request with the value on the
"Signature" header
if($computedSignature != $headers['Signature']) {
/* request may have been tampered with or is likely from another source */
/* enter code to discard webhook */
}
else{
/* request is from PayChangu */
/* enter code to implement on the basis of the data on the webhook payload */
}
}
```

Responding to Webhook Requests
To acknowledge receipt of a webhook, your endpoint must return a 200 HTTP status code. Any other response codes, including 3xx codes, will be considered a failure. The response body or headers do not matter to us.

📍
If we do not receive a 200 status code (for example, if your server is unreachable), we will retry the webhook call three times, with a 30-minute interval between each attempt.

Don't rely solely on webhooks
Have a backup strategy in place in case your webhook endpoint fails. For instance, if your webhook endpoint is encountering server errors, you won’t be notified of new customer payments because the webhook requests will fail.

To mitigate this, we recommend setting up a background job that regularly polls for the status of any pending transactions using the transaction verification endpoint, or for direct charge use charge verification endpoint. For example, you could check every hour until a successful or failed response is returned.

Always Re-query
Whenever you receive a webhook notification, before providing the customer with any value, you should call our API again to verify the received details and ensure that the data has not been compromised.

### Transaction verification

This shows you how to verify transactions using the transaction ID

After a successful charge, you need to verify with PayChangu that the payment was successful before providing value to your customer on your website. For every transaction, you must supply a transaction ID.

Here are some important things to check when verifying the payment:

Verify that the transaction reference matches the one you generated.
Verify that the status of the transaction is successful.
Verify that the currency of the payment matches the expected currency.
Verify that the amount paid is greater than or equal to the expected amount. If the amount is greater, you can provide the customer with the value and refund the excess.
To verify a payment, use the verify transaction endpoint by passing in the transaction ID in the URL. You can obtain the transaction ID from the tx_ref present in the response you receive after creating a transaction or in the webhook payload for any transaction.

Below is a sample code of how to implement server-side validation

```cURL
curl -X GET "https://api.paychangu.com/verify-payment/{tx_ref}"
-H "Accept: application/json"
-H "Authorization: Bearer {secret_key}"
```

### Verification Response

A successful response from the verification endpoint will look like this:

```JSON
{
  "status": "success",
  "message": "Payment details retrieved successfully.",
  "data": {
    "event_type": "checkout.payment",
    "tx_ref": "PA54231315",
    "mode": "live",
    "type": "API Payment (Checkout)",
    "status": "success",
    "number_of_attempts": 1,
    "reference": "26262633201",
    "currency": "MWK",
    "amount": 1000,
    "charges": 40,
    "customization": {
      "title": "iPhone 10",
      "description": "Online order",
      "logo": null
    },
    "meta": null,
    "authorization": {
      "channel": "Card",
      "card_number": "230377******0408",
      "expiry": "2035-12",
      "brand": "MASTERCARD",
      "provider": null,
      "mobile_number": null,
      "completed_at": "2024-08-08T23:21:22.000000Z"
    },
    "customer": {
      "email": "yourmail@example.com",
      "first_name": "Mac",
      "last_name": "Phiri"
    },
    "logs": [
      {
        "type": "log",
        "message": "Attempted to pay with card",
        "created_at": "2024-08-08T23:20:59.000000Z"
      },
      {
        "type": "log",
        "message": "Processing and verification of card payment completed successfully.",
        "created_at": "2024-08-08T23:21:22.000000Z"
      }
    ],
    "created_at": "2024-08-08T23:20:21.000000Z",
    "updated_at": "2024-08-08T23:20:21.000000Z"
  }
}
```

### Balance
Retrieve Wallet balance by currency

This endpoint allows you to retrieve your PayChangu wallet balance in a specific currency (e.g., MWK, USD). It is designed for server-side integrations and supports authentication using your API secret key. This is useful for tracking funds, performing balance checks before payouts, or simply monitoring account status in real-time.

Below is a sample code of how to implement server-side validation

```cURL
curl -X GET "https://api.paychangu.com/wallet-balance?currency=MWK"
-H "Accept: application/json"
-H "Authorization: Bearer {secret_key}"
```

### Balance Response
A successful response from the balance endpoint will look like this:
```JSON
{
  "status": "success",
  "message": "Wallet balances retrieved successfully.",
  "data": {
    "environment": "live",
    "currency": "MWK",
    "main_balance": "52637.95",
    "collection_balance": 0
  }
}
```

Collection Balance: This is where money is deposited when customers make payments through PayChangu to your account. The funds remain in the collection balance until they are settled to your registered bank account or transferred to your main balance.
Main Balance: This balance represents the funds available for you to transfer out of PayChangu. You can use it to send money to your own bank account or to other bank accounts.

## INTEGRATIONS
### Introduction
#### Collect Payments
Here are a few ways to quickly integrate PayChangu into your projects. Once completed, you will be able to receive payments from customers worldwide.

PayChangu Standard Checkout
The Standard Checkout integration is simple. Use our API on your server to generate a payment link, redirect your customer to this link, and we’ll handle the rest, redirecting back to you once the payment is complete. check it here

PayChangu Inline Checkout
Our primary client-side integration is simple to implement. Add the JavaScript library to your checkout page and link it to your payment button. When the customer clicks the payment button, our pop-up will appear on your website to handle the process and redirect to you upon completion. check it here

PayChangu HTML Checkout
The easiest way to integrate PayChangu into your project. This method only requires a simple HTML form to initiate the payment process. Once the customer submits the form, they’ll be redirected to our secure payment page. After completing the payment, they’ll be redirected back to your website. check it here

Mobile Money Direct Charge
Build your own UI and payment flow, gather your customers’ payment information, and charge them directly using our API for both Airtel Money and TNM Mpamba. check it here

Bank Transfer Direct Charge
This allows you to collect payments from your customers via Instant Bank Transfer. Create your own custom user interface and payment process, collect your customers’ payment details seamlessly, and manage direct charges using our API. check it here

Card Direct Charge
Our Card Direct API is perfect for merchants and partners who want complete control over the design and user experience of their card payment forms.. check it here

SDKs and Plugins
Easily integrate PayChangu with your platform using any of our SDKs or for those using eCommerce platforms like WordPress, WooCommerce, or Give! we offer compatible plugins.

If you prefer not to write code, you can choose from the following options:

Payment Links: Effortlessly collect payments by generating and sharing a simple payment link with your customers. This feature allows you to provide a seamless checkout experience without the need for a website or custom integration. Just create a link and send it via email, social media, or messaging apps, making it easy for your customers to pay.

## Inline Checkout

PayChangu Inline Checkout provides a simple and convenient payment flow for web. It can be integrated in five easy steps, making it the easiest way to start accepting payments.

### An example
Here's what an implementation of PayChangu Inline Checkout on a checkout page could look like:

https://files.readme.io/bf805a4977a7a54fae4a3ff36614676cdf8de17367aab8619142df6faccc93b3-ScreenRecording2025-07-22at10.55.42am-ezgif.com-video-to-gif-converter.gif

### Try it out
Use test card 4242 4242 4242 4242 Airtel Money Number 990000000

#### Let’s break down the main functions of this code:
First, you include the PayChangu Inline library with a script tag:

```javascript
<script src="https://in.paychangu.com/js/popup.js"></script>
```

Next up is the payment button. This is the button the customer clicks after they've reviewed their order and are ready to pay you. You'll attach an onclick event handler to this button that calls makePayment(), a custom JS function you're going to write.

```html
  <div id="wrapper"></div>

  <button type="button" onClick="makePayment()">
    Pay Now
  </button>
```

Finally, in the makePayment() function, you call the PayChanguCheckout() function with some custom parameters:

```javascript
function makePayment(){
    PaychanguCheckout({
      "public_key": "pub-test-HYSBQpa5K91mmXMHrjhkmC6mAjObPJ2u",
      "tx_ref": '' + Math.floor((Math.random() * 1000000000) + 1),
      "amount": 1000,
      "currency": "MWK",
      "callback_url": "https://example.com/callbackurl",
      "return_url": "https://example.com/returnurl",
      "customer":{
        "email": "yourmail@example.com",
        "first_name":"Mac",
        "last_name":"Phiri",
      },
      "customization": {
        "title": "Test Payment",
        "description": "Payment Description",
      },
      "meta": {
        "uuid": "uuid",
        "response": "Response"
      }
    });
}
```

### Sample inline Implementation
You can embed PayChangu on your page using our PayChanguCheckout() JavaScript function. The function responds to your request in accordance with your request configurations. If you specify a callback_url in your request, the function will redirect your users to the provided callback URL when they complete the payment.

```html
<form>
  <script src="https://in.paychangu.com/js/popup.js"></script>
  <div id="wrapper"></div>
  <button type="button" onClick="makePayment()">Pay Now</button>
</form>
<script>
    function makePayment(){
      PaychanguCheckout({
        "public_key": "pub-test-HYSBQpa5K91mmXMHrjhkmC6mAjObPJ2u",
        "tx_ref": '' + Math.floor((Math.random() * 1000000000) + 1),
        "amount": 1000,
        "currency": "MWK",
        "callback_url": "https://example.com/callbackurl",
        "return_url": "https://example.com/returnurl",
        "customer":{
          "email": "yourmail@example.com",
          "first_name":"Mac",
          "last_name":"Phiri",
        },
        "customization": {
          "title": "Test Payment",
          "description": "Payment Description",
        },
        "meta": {
          "uuid": "uuid",
          "response": "Response"
        }
      });
    }
</script>
```

### After the Payment
Four things will happen when a payment is successful:

- We’ll redirect you to your callback_url with status tx_ref after payment is complete.
- We’ll send you a webhook if you have it enabled. Learn more about webhooks and see examples here.
- We’ll send an email receipt to your customer if the payment was successful (unless you’ve disabled this feature).
- We’ll send you an email notification (unless you’ve disabled this feature).

On your server, you should handle the redirect and always verify the final state of the transaction.

### Transaction verification
This shows you how to verify transactions using the transaction ID

After a successful charge, you need to verify with PayChangu that the payment was successful before providing value to your customer on your website. For every transaction, you must supply a transaction ID.

Here are some important things to check when verifying the payment:

- Verify that the transaction reference matches the one you generated.
- Verify that the status of the transaction is successful.
- Verify that the currency of the payment matches the expected currency.
- Verify that the amount paid is greater than or equal to the expected amount. If the amount is greater, you can provide the customer with the value and refund the excess.
- To verify a payment, use the verify transaction endpoint by passing in the transaction ID in the URL. You can obtain the transaction ID from the tx_ref present in the response you receive after creating a transaction or in the webhook payload for any transaction.

Below is a sample code of how to implement server-side validation

```cURL
curl -X GET "https://api.paychangu.com/verify-payment/{tx_ref}"
-H "Accept: application/json"
-H "Authorization: Bearer {secret_key}"
```

### Verification response
Here's a sample verification response

```JSON
{
  "status": "success",
  "message": "Payment details retrieved successfully.",
  "data": {
    "event_type": "checkout.payment",
    "tx_ref": "PA54231315",
    "mode": "live",
    "type": "API Payment (Checkout)",
    "status": "success",
    "number_of_attempts": 1,
    "reference": "26262633201",
    "currency": "MWK",
    "amount": 1000,
    "charges": 40,
    "customization": {
      "title": "iPhone 10",
      "description": "Online order",
      "logo": null
    },
    "meta": null,
    "authorization": {
      "channel": "Card",
      "card_number": "230377******0408",
      "expiry": "2035-12",
      "brand": "MASTERCARD",
      "provider": null,
      "mobile_number": null,
      "completed_at": "2024-08-08T23:21:22.000000Z"
    },
    "customer": {
      "email": "yourmail@example.com",
      "first_name": "Mac",
      "last_name": "Phiri"
    },
    "logs": [
      {
        "type": "log",
        "message": "Attempted to pay with card",
        "created_at": "2024-08-08T23:20:59.000000Z"
      },
      {
        "type": "log",
        "message": "Processing and verification of card payment completed successfully.",
        "created_at": "2024-08-08T23:21:22.000000Z"
      }
    ],
    "created_at": "2024-08-08T23:20:21.000000Z",
    "updated_at": "2024-08-08T23:20:21.000000Z"
  }
}
```

### What if the Payment Fails?
If the payment attempt fails (for instance, due to insufficient funds), you don’t need to take any action. The payment page will remain open, allowing the customer to try again until the payment succeeds or they choose to cancel. Once the customer cancels or after multiple failed attempts, we will redirect to the return_url with the query parameters tx_ref and status of failed.

If you have webhooks enabled, we’ll send you a notification for each failed payment attempt. This can be useful if you want to reach out to customers who experienced issues with their payment.

## Standard Checkout
This page will help you with Standard Checkout - API integration flow.

PayChangu provides access to your resources through RESTful endpoints, allowing you to test the API

### HTTP Request Sample
We provide cURL request samples so you can quickly test each endpoint on your terminal or command line. Need a quick how-to for making cURL requests? Just use an HTTP client such as Postman, like the rest of us!

### Requests and Responses
Both request body data and response data are formatted as JSON. Content type for responses are always of the type application/JSON. You can use the PayChangu API in test mode, which does not affect your live data. The API key you use to authenticate the request determines whether the request is live mode or test mode

Initiate Transaction
Parameter

Required

Description

secret_key string

Yes

This is important for creating payment links

callback_url url

Yes

This is your IPN URL, which is essential for receiving payment notifications. Successful transactions will redirect to this URL after payment. The {tx_ref} is returned, so you don’t need to include it in your URL.

return_url url

Yes

Once the customer cancels or after multiple failed attempts, we will redirect to the return_url with the query parameters tx_ref and status of failed.

tx_ref string

Optional

Your transaction reference. This MUST be unique for every transaction.

first_name string

Optional

This is the first name of your customer.

last_name string

Optional

This is the last name of your customer.

email string

Optional

This is the email address of your customer. Transaction notification will be sent to this email address

currency string

Yes

Currency to charge in. [ 'MWK', 'USD' ]

amount int32

Yes

Amount to charge the customer.

customization array

Optional

{ "title":"Title of payment", "description":"Description of payment", }

meta array

Optional

You can pass on extra information here.

### Sample Request
```cURL
curl -X POST "https://api.paychangu.com/payment"
-H "Accept: application/json"
-H "Authorization: Bearer {secret_key}"
-d "{
    "amount": "100",
    "currency": "MWK",
    "email": "yourmail@example.com",
    "first_name":"Kelvin",
    "last_name":"Banda",
    "callback_url": "https://webhook.site/9d0b00ba-9a69-44fa-a43d-a82c33c36fdc",
    "return_url": "https://webhook.site",
    "tx_ref": '' + Math.floor((Math.random() * 1000000000) + 1),
    "customization": {
      "title": "Test Payment",
      "description": "Payment Description",
    },
    "meta": {
      "uuid": "uuid",
      "response": "Response"
    }
}"
```

### Sample Response
A successful response from the initiate transaction endpoint will look like this:
```JSON
{
  "message": "Hosted payment session generated successfully.",
  "status": "success",
  "data": {
    "event": "checkout.session:created",
    "checkout_url": "https://checkout.paychangu.com/923677185321",
    "data": {
      "tx_ref": "ae041eae-6abd-4602-a949-56fbd65c29fe",
      "currency": "MWK",
      "amount": 100,
      "mode": "live",
      "status": "pending"
    }
  }
}
```

When you provide the user with the returned link, they will be directed to our checkout page to complete the payment, as shown below.

https://files.readme.io/6cae2be900685fa2f63691ff28c3701927b7ec794c354f1521330cb9e77227df-Screenshot_2025-07-11_at_1.15.16_am.png

What happens when the user completes the transaction on the page?
When the user enters their payment details, PayChangu will validate and then charge the card. Once the charge is completed, we will:

Call your specified redirect_url and post the response to you. We will also append your transaction ID (transaction_id), transaction reference (tx_ref), and the transaction status.

Call your webhook URL (if one is set).

Send an email to you and your customer on the successful payment.

Before providing value to the customer, please make a server-side call to our transaction verification endpoint to confirm the status of the transaction.

After the Payment
Four things will happen when a payment is successful:

We’ll redirect you to your callback_url with status tx_ref after payment is complete.
We’ll send you a webhook if you have it enabled. Learn more about webhooks and see examples here.
If the payment was successful, we’ll email a receipt to your customer (unless you’ve disabled this feature).
We’ll send you an email notification (unless you’ve disabled this feature).
On your server, you should handle the redirect and always verify the final state of the transaction.


## HTML Checkout
This document will show you how to collect payments from your customers using PayChangu in a HTML file

Our HTML Checkout works similarly to PayChangu Inline, but it’s built entirely with HTML. Just create a standard HTML form containing the necessary payment details. When the customer submits the form, they’ll be redirected to our secure payment page to complete their transaction.

Let’s break down what this code is doing:

To begin with, we’re creating a standard HTML form. It’s important that the form uses the POST method and sets its action attribute to PayChangu’s checkout URL.


```html
<form 
      method="POST" 
      action="https://api.paychangu.com/hosted-payment-page" >

</form>
```

Next is the payment button. This is what the customer clicks after reviewing their order and deciding to proceed with the payment. Ensure the button is placed inside the form and set its type attribute to "submit" so that the form is submitted when clicked.

```html
<form method="POST" action="https://api.paychangu.com/hosted-payment-page" >
      <input type="submit" value="submit" />
</form>
```

Finally, we include hidden input fields in the form to define the payment options. These options use the same values as those in the Inline and Standard flows but are represented as form fields. For nested object fields, use square bracket notation to reference them.

Parameter	Required	Description
public_key	Yes	This is important for creating payment links
callback_url	Yes	This is your IPN URL, which is essential for receiving payment notifications. Successful transactions will redirect to this URL after payment. The {tx_ref} is returned, so you don’t need to include it in your URL.
return_url	Yes	Once the customer cancels or after multiple failed attempts, we will redirect to the return_url with the query parameters tx_ref and status of failed.
tx_ref	Optional	Your transaction reference. This must be unique for each transaction. Alternatively, you can leave this field out, and we’ll generate one for you.
first_name	Optional	This is the first name of your customer.
last_name	Optional	This is the last name of your customer.
email	Optional	This is the email address of your customer. Transaction notification will be sent to this email address
currency	Yes	Currency to charge in. [ 'MWK', 'USD' ]
amount int32	Yes	Amount to charge the customer.
description	Optional	Payment Description
meta array	Optional	You can pass on extra information here.

```html
    <input type="hidden" name="public_key" value="{public_key}" />
    <input type="hidden" name="callback_url" value="https://example.com/callbackurl" />
    <input type="hidden" name="return_url" value="https://example.com/returnurl" />
    <input type="hidden" name="tx_ref" value="2346vrcd" />
    <input type="hidden" name="amount" value="100" />
    <input type="hidden" name="currency" value="MWK" />
    <input type="hidden" name="email" value="yourmail@example.com" />
    <input type="hidden" name="first_name" value="Mac" />
    <input type="hidden" name="last_name" value="Phiri" />
    <input type="hidden" name="title" value="Test Payment" />
    <input type="hidden" name="description" value="Payment Description" />
    <input type="hidden" name="meta" value="" />
```

That’s all for the HTML checkout setup. Once the customer submits the form, they’ll be redirected to the payment page to complete their transaction.

Full Sample

```html
<form method="POST" action="https://api.paychangu.com/hosted-payment-page" >
    <input type="hidden" name="public_key" value="{public_key}" />
    <input type="hidden" name="callback_url" value="https://example.com/callbackurl" />
    <input type="hidden" name="return_url" value="https://example.com/returnurl" />
    <input type="hidden" name="tx_ref" value="2346vrcd" />
    <input type="hidden" name="amount" value="100" />
    <input type="hidden" name="currency" value="MWK" />
    <input type="hidden" name="email" value="yourmail@example.com" />
    <input type="hidden" name="first_name" value="Mac" />
    <input type="hidden" name="last_name" value="Phiri" />
    <input type="hidden" name="title" value="Test Payment" />
    <input type="hidden" name="description" value="Payment Description" />
    <input type="hidden" name="meta" value="" />
    <input type="submit" value="submit" />
</form>
```

After the Payment
Four things will happen when a payment is successful:

- We’ll redirect you to your callback_url with status tx_ref after payment is complete.
- We’ll send you a webhook if you have it enabled. Learn more about webhooks and see examples here.
- We’ll send an email receipt to your customer if the payment was successful (unless you’ve disabled this feature).
- We’ll send you an email notification (unless you’ve disabled this feature).

On your server, you should handle the redirect and always verify the final state of the transaction.

### What if the Payment Fails?
If the payment attempt fails (for instance, due to insufficient funds), you don’t need to take any action. The payment page will remain open, allowing the customer to try again until the payment succeeds or they choose to cancel. Once the customer cancels or after multiple failed attempts, we will redirect to the return_url with the query parameters tx_ref and status of failed.

If you have webhooks enabled, we’ll send you a notification for each failed payment attempt. This can be useful if you want to reach out to customers who experienced issues with their payment.


HOSTED STANDARD CHECKOUT API LINKS

https://developer.paychangu.com/reference/initiate-transaction
https://developer.paychangu.com/reference/verify-transaction-status

