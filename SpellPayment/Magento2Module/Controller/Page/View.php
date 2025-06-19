<?php

namespace SpellPayment\Magento2Module\Controller\Page;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class View extends Action
{
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /** @return \Magento\Framework\Controller\ResultInterface */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $data = ['message' => 'Hello world!'];

        return $result->setData($data);
    }
}
