<?php

namespace SpellPayment\Magento2Module\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthenticationException;

/**
 * Factory class for @see \SpellPayment\Magento2Module\Helper\SpellAPI
 */
class SpellAPIFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = '\\SpellPayment\\Magento2Module\\Helper\\SpellAPI'
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \SpellPayment\Magento2Module\Helper\SpellAPI
     * @throws AuthenticationException
     */
    public function create(array $data = [])
    {
        //Need to test if this gets correctly scoped values in all scenarios?
        $brand_id = $this->scopeConfig->getValue(
            'payment/spellpayment_checkout/shop_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        $private_key = $this->scopeConfig->getValue(
            'payment/spellpayment_checkout/shop_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        $debug = (bool)$this->scopeConfig->getValue(
            'payment/spellpayment_checkout/enable_logging',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $instanceData = array_merge([
            'private_key' => $private_key,
            'brand_id' => $brand_id,
            'debug' => $debug
        ], $data);

        if (!$instanceData['private_key'] || !$instanceData['brand_id']) {
            throw new AuthenticationException(__(
                'Shop authentication token/brand id of Citadele E-commerce Gateway are not set'
            ));
        }

        return $this->_objectManager->create(
            $this->_instanceName,
            $instanceData
        );
    }
}
