<?php

namespace SpellPayment\Magento2Module\Model\Method;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Model\ScopeInterface;

class Checkout extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'spellpayment_checkout';
    const SPELL_MODULE_VERSION = 'v1.2.0';

    /**
     * Checkout Method Code
     */
    protected $_code = self::CODE;

    protected $_canOrder = true;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canCancelInvoice = true;
    protected $_canVoid = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canAuthorize = true;
    protected $_isInitializeNeeded = false;
    protected $_checkoutSession = false;
    protected $_moduleHelper = false;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $localeResolver;
    /**
     * @var \SpellPayment\Magento2Module\Helper\SpellAPIFactory
     */
    private $spellAPIFactory;

    /**
     * Get Instance of the Magento Code Logger
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Get a string of concatenated product names up to 256 chars
     *
     * @param $order
     * @return string
     */
    private function getProductNames($order)
    {
        $ignoredTypes = [Configurable::TYPE_CODE, Type::TYPE_BUNDLE];
        $names = [];
        foreach ($order->getAllItems() as $item) {
            if (\in_array($item->getProductType(), $ignoredTypes)) {
                continue;
            }
            $names[] = $item->getName();
        }
        $nameString = implode(';', $names);
        return substr($nameString, 0, 10000);
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \SpellPayment\Magento2Module\Helper\Data $moduleHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \SpellPayment\Magento2Module\Helper\SpellAPIFactory $spellAPIFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleHelper = $moduleHelper;
        $this->localeResolver = $localeResolver;
        $this->spellAPIFactory = $spellAPIFactory;
    }

    private function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    private function makePaymentParams(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $isShippingSet = (bool)$order->getShippingAddress();
        $orderId = ltrim(
            $order->getIncrementId(),
            '0'
        );

        // ignoring Yen, Rubles, Dinars, etc - can't find API to get decimal
        // places in Magento, and it was done same way in other modules anyway
        $amountInCents = $amount * 100;

        return [
            'success_callback' => $this->getModuleHelper()->getReturnUrl(
                $this->getCode(),
                'success'
            ),
            'success_redirect' => $this->getModuleHelper()->getReturnUrl(
                $this->getCode(),
                'success'
            ),
            'failure_redirect' => $this->getModuleHelper()->getReturnUrl(
                $this->getCode(),
                'failure'
            ),
            'cancel_redirect' => $this->getModuleHelper()->getReturnUrl(
                $this->getCode(),
                'cancel'
            ),
            'creator_agent' => 'Magento2Module ' . self::SPELL_MODULE_VERSION,
            'platform' => 'magento',
            'reference' => (string)$orderId,
            'purchase' => [
                "currency" => $order->getBaseCurrencyCode(),
                "language" => strstr($this->localeResolver->getLocale(), '_', true),
                "products" => [
                    [
                        'name' => 'Payment',
                        'price' => $amountInCents,
                        'quantity' => 1,
                    ],
                ],
                "notes" => $this->getProductNames($order)
            ],
            'brand_id' => $this->_scopeConfig->getValue(
                'payment/spellpayment_checkout/shop_id',
                ScopeInterface::SCOPE_STORE
            ),
            'client' => [
                'email' => $this->_checkoutSession->getQuote()->getCustomerEmail(),
                'phone' => $isShippingSet ?
                    $order->getShippingAddress()->getTelephone() : $order->getBillingAddress()->getTelephone(),
                'full_name' => $order->getBillingAddress()->getFirstName() . ' '
                    . $order->getBillingAddress()->getLastName(),
                'street_address' => implode(' ', $order->getBillingAddress()->getStreet() ?? []),
                'country' => $order->getBillingAddress()->getCountryId(),
                'city' => $order->getBillingAddress()->getCity(),
                'zip_code' => $order->getBillingAddress()->getPostcode(),
                'shipping_street_address' => $isShippingSet ? implode(
                    ' ',
                    $order->getShippingAddress()->getStreet()
                ) : '',
                'shipping_country' => $isShippingSet ? $order->getShippingAddress()->getCountryId() : '',
                'shipping_city' => $isShippingSet ? $order->getShippingAddress()->getCity() : '',
                'shipping_zip_code' => $isShippingSet ? $order->getShippingAddress()->getPostcode() : '',
            ],
        ];
    }

    /**
     * Order Payment
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \RuntimeException|\Magento\Framework\Exception\LocalizedException
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $spell = $this->spellAPIFactory->create();
        $paymentParams = $this->makePaymentParams($payment, $amount);
        $paymentRs = $spell->createPayment($paymentParams);

        $checkout_url = $paymentRs['checkout_url'] ?? null;
        $id = $paymentRs['id'] ?? null;
        if (!$id) {
            $msg = 'Could not init payment in service - ' . json_encode($paymentRs);
            throw new \RuntimeException($msg);
        }
        $formDataJson = $this->_checkoutSession->get_spellFormDataJson() ?: 'null';
        $formData = json_decode($formDataJson, true);
        $chosenMethod = $formData['spell_payment_method'] ?? null;
        if ($chosenMethod) {
            $checkout_url .= '?preferred=' . $chosenMethod;
        }
        $this->_checkoutSession->set_spellPaymentCheckoutRedirectUrl($checkout_url);
        $this->_checkoutSession->set_spelPaymentId($id);

        $payment->setTransactionId($paymentRs['id']);
        $payment->setIsTransactionPending(true);
        $payment->setIsTransactionClosed(false);

        return $this;
    }

    /**
     * Determines method's availability based on config data and quote amount
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote)
            && $this->_scopeConfig->getValue('payment/spellpayment_checkout/active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Checks base currency against the allowed currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }
}
