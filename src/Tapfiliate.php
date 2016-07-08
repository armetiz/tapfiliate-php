<?php

namespace Armetiz\TapfiliateSDK;

use Assert\Assertion;
use Buzz;
use Buzz\Message\Response;

/**
 * @author : Thomas Tourlourat <thomas@tourlourat.com>
 */
class Tapfiliate
{
    /**
     * @var Buzz\Browser
     */
    private $browser;

    /**
     * Tapfiliate constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        Assertion::string($key);

        // @see https://github.com/kriswallsmith/Buzz/pull/186
        $listener = new Buzz\Listener\CallbackListener(function (Buzz\Message\RequestInterface $request, $response = null) use ($key) {
            if ($response) {
                // postSend
            } else {
                // preSend
                $request->addHeader(sprintf('Api-Key: %s', $key));
            }
        });

        $this->browser = new Buzz\Browser(new Buzz\Client\Curl());
        $this->browser->addListener($listener);
    }

    private function guardOptions($options, $constraints)
    {
        Assertion::allInArray(array_keys($options), array_keys($constraints));

        foreach ($options as $key => $value) {
            $constraint = $constraints[$key];

            if (is_callable($constraint)) {
                call_user_func($constraint, $value);
            }
        }
    }

    public function createConversion($options, $overrideMaxCookieTime = null)
    {
        $this->guardOptions($options, [
            "click"           => function ($value) {
                Assertion::isArray($value);
                Assertion::keyExists("id", $value);
            },
            "coupon"          => [Assertion::class, "string"],
            "visitor_id"      => [Assertion::class, "string"],
            "external_id"     => [Assertion::class, "string"],
            "amount"          => [Assertion::class, "numeric"],
            "commission_type" => [Assertion::class, "string"],
            "commissions"     => function ($value) {
                Assertion::allKeyExists("sub_amount", $value);
                Assertion::allKeyExists("commission_type", $value);
            },
            "meta_data"       => function ($value) {
                Assertion::isArray($value);
            },
            "program_group"   => [Assertion::class, "string"],
        ]);

        $parameters = [];
        if (is_bool($overrideMaxCookieTime)) {
            $parameters = [
                "override_max_cookie_time" => $overrideMaxCookieTime,
            ];
        }

        /** @var Response $response */
        $response = $this->browser->post(
            $this->getEndpoint($parameters),
            [
                "content-type" => "application/json",
            ],
            json_encode($options)
        );

        $this->guardResponse($response);

        return json_decode($response->getContent(), true);
    }

    protected function getEndpoint(array $parameters = [])
    {
        $url = "https://tapfiliate.com/api/1.4/conversions/";

        if (!empty($parameters)) {
            $queryParameters = [];

            foreach ($parameters as $field => $value) {
                if (is_bool($value)) {
                    $queryParameters[] = sprintf("%s=%s", $field, $value ? "true" : "false");
                    continue;
                }

                $queryParameters[] = sprintf("%s=%s", $field, urlencode($value));
            }

            $url .= sprintf("?%s", join("&", $queryParameters));
        }

        return $url;
    }

    /**
     * @param Response $response
     */
    protected function guardResponse(Response $response)
    {
        if (200 !== $response->getStatusCode()) {
            $content = json_decode($response->getContent(), true);
            $message = "No details";
            if (isset($content["errors"])) {
                $message = join(", ", array_map(function(array $error) {
                    return $error["message"];
                }, $content["errors"]));
            }

            throw new \RuntimeException(sprintf(
                    'An HTTP %s error occurred when reaching tapfiliate API : %s',
                    $response->getStatusCode(),
                    $message
                )
            );
        }
    }
}