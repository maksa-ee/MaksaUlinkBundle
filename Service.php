<?php

/*
 * Copyright (c) 2010 "Cravler", http://github.com/cravler
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:

 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Maksa\Bundle\UlinkBundle;

/**
 * @author Cravler <http://github.com/cravler>
 */
class Service
{
    /**
     * @var integer
     */
    private $clientId = 0;
    /**
     * @var string
     */
    private $publicKeyPem = null;
    /**
     * @var string
     */
    private $privateKeyPem = null;
    /**
     * @var string
     */
    private $defaultCurrency = null;

    /**
     * @param integer $clientId
     * @param string $keyPath
     * @param string $publicKey
     * @param string $privateKey
     * @param string $defaultCurrency
     */
    public function __construct($clientId, $keyPath, $publicKey, $privateKey, $defaultCurrency)
    {
        $this->clientId        = $clientId;
        $this->publicKeyPem    = file_get_contents($keyPath . DIRECTORY_SEPARATOR . $publicKey);
        $this->privateKeyPem   = file_get_contents($keyPath . DIRECTORY_SEPARATOR . $privateKey);
        $this->defaultCurrency = $defaultCurrency;
    }

    /**
     * @return integer
     */
    private function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    private function getPublicKeyPem()
    {
        return $this->publicKeyPem;
    }

    /**
     * @return string
     */
    private function getPrivateKeyPem()
    {
        return $this->privateKeyPem;
    }

    /**
     * @return string
     */
    private function getDefaultCurrency()
    {
        return $this->defaultCurrency;
    }

    /**
     * @param string $clientTransactionId
     * @param string $amount
     * @param array $order
     * @param string $currency
     * @return string
     */
    public function encrypt($clientTransactionId, $amount, $order = array(), $currency = null)
    {
        $request = new \Ulink\PaymentRequest();
        $request->setClientTransactionId($clientTransactionId);
        $request->setAmount(new \Ulink\Money($amount));
        $request->setCurrency($currency ? $currency : $this->getDefaultCurrency());

        if ($order && count($order)) {
            $_order = new \Ulink\Order();
            /**
             * $item = array(
             *     'name'         => 'Some Name',
             *     'description'  => 'Some Description',
             *     'oneItemPrice' => '10.90',
             *     'quantity'     => 5
             * );
             */
            foreach ($order as $item) {
                $_order->addItem(
                    new \Ulink\OrderItem(
                        $item['name'],
                        $item['description'],
                        new \Ulink\Money($item['oneItemPrice']),
                        (isset($item['quantity']) ? $item['quantity'] : 1)
                    )
                );
            }
            $request->setOrder($_order);
        }

        $requestJson = $request->toJson();
        $requestJson = \Ulink\CryptoUtils::seal($requestJson, $this->getPublicKeyPem());
        $packet      = new \Ulink\TransportPacket();
        $packet->setRequest($requestJson);
        $signature   = \Ulink\CryptoUtils::sign($requestJson, $this->getPrivateKeyPem());

        $packet->setSignature($signature);
        $packet->setClientId($this->getClientId());

        return $packet->toJson();
    }

    /**
     * @throws Exception\UlinkException
     * @param string $rawData
     * @return array
     */
    public function decrypt($rawData)
    {
        $packet = \Ulink\TransportPacket::createFromJson($rawData);

        if (!$packet) {
            throw new Exception\UlinkException('Can not decrypt packet!');
        }

        if ($this->getClientId() != $packet->getClientId()) {
            throw new Exception\UlinkException('Client id does not match the id given in configuration!');
        }

        if (!$packet->getSignature()) {
            throw new Exception\UlinkException('Packet signature is broken!');
        }

        if (!$packet->validateAgainstKey($this->getPublicKeyPem())) {
            throw new Exception\UlinkException('Data signature does not match the packet content!');
        }

        $responseJson = \Ulink\CryptoUtils::unseal($packet->getRequest(), $this->getPrivateKeyPem());
        $response = \Ulink\RequestFactory::createFromJson($responseJson);

        $result = array(
            'clientTransactionId' => $response->getClientTransactionId(),
            'amount'              => (string)$response->getAmount(),
            'currency'            => $response->getCurrency(),
        );

        if (\Ulink\PaymentResponse::clazz() == get_class($response)) {
            $result = array_merge($result, array(
                'timestamp'  => $response->getTimestamp(),
                'success'    => $response->isSuccess(),
                'errors'     => $response->getErrors(),
                'errorCodes' => $response->getErrorCodes(),
            ));
        }

        $order = $response->getOrder();
        if ($order && count($order->getItems())) {
            $items = $order->getItems();
            $result['oreder'] = array();
            foreach ($items as $item) {
                $result['oreder'][] = array(
                    'name'         => $item->getName(),
                    'description'  => $item->getDescription(),
                    'oneItemPrice' => (string)$item->getOneItemPrice(),
                    'quantity'     => $item->getQuantity(),
                );
            }
        }

        return $result;
    }
}
