<?php
declare(strict_types=1);

namespace Easeo\Shop\Payment;

class NotImplementedException extends \RuntimeException
{
    public function __construct(string $method)
    {
        parent::__construct(
            "Payment provider method '$method' is not implemented in the skeleton. "
            . "Implement when eurolight-webshop kickoff starts — see packages/shop-module/README.md."
        );
    }
}
