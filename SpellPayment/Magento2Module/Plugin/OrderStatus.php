<?php

declare(strict_types=1);

namespace SpellPayment\Magento2Module\Plugin;

use Magento\Sales\Model\Order\Payment\State\OrderCommand;
use SpellPayment\Magento2Module\Model\Order\StatusResolver;
use SpellPayment\Magento2Module\Model\Method\Checkout;
use Magento\Sales\Model\Order;

/**
 * Update order status to payment pending
 */
class OrderStatus
{
    /**
     * @var StatusResolver
     */
    private $statusResolver;

    /**
     * OrderStatus constructor.
     *
     * @param StatusResolver $statusResolver
     */
    public function __construct(
        StatusResolver $statusResolver
    ) {
        $this->statusResolver = $statusResolver;
    }

    /**
     * @param OrderCommand $subject
     * @param \Closure $proceed
     * @param Order\Payment $payment
     * @param float $amount
     * @param Order $order
     */
    public function aroundExecute(
        OrderCommand $subject,
        \Closure $proceed,
        Order\Payment $payment,
        float $amount,
        Order $order
    ) {
        $proceed($payment, $amount, $order);

        if ($payment->getMethod() === Checkout::CODE && $order->getState() === Order::STATE_PAYMENT_REVIEW) {
            $state = Order::STATE_PENDING_PAYMENT;
            $order->setState($state);
            $order->setStatus($this->statusResolver->getOrderStatusByState($order, $state));
        }
    }
}
