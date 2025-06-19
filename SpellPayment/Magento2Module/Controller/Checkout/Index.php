<?php
namespace SpellPayment\Magento2Module\Controller\Checkout;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;

class Index extends \Magento\Framework\App\Action\Action
{
    private $_checkoutSession;
    private $_orderFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
    }

    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    protected function getOrderFactory()
    {
        return $this->_orderFactory;
    }

    /**
     * Get an Instance of the current Checkout Order Object
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        $orderId = $this->getCheckoutSession()->getLastRealOrderId();

        if (!isset($orderId)) {
            return null;
        }

        $order = $this->getOrderFactory()->create()->loadByIncrementId(
            $orderId
        );

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    protected function redirectToCheckoutFragmentPayment()
    {
        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }

    public function execute()
    {
        $order = $this->getOrder();
        if (!$order) {
            throw new NotFoundException(__('No active order in session.'));
        }
        $redirectUrl = $this->getCheckoutSession()->get_spellPaymentCheckoutRedirectUrl();
        if (!$redirectUrl) {
            throw new LocalizedException(__('Failed to pass the payment gateway url.'));
        }
        $this->getResponse()->setRedirect($redirectUrl);
    }
}
