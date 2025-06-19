<?php
namespace SpellPayment\Magento2Module\Controller\Checkout;

class SetFormData extends \Magento\Framework\App\Action\Action
{
    private $_checkoutSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $formDataJson = $this->getRequest()->getParam('json');
        $this->_checkoutSession
            ->set_spellFormDataJson($formDataJson);
        $this->getResponse()->setBody(json_encode([
            'status' => 'success',
        ]));
    }
}
