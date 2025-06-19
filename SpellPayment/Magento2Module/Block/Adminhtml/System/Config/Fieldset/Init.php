<?php

declare(strict_types=1);

namespace SpellPayment\Magento2Module\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Init extends Template implements RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'SpellPayment_Magento2Module::system/config/fieldset/init.phtml';

    /**
     * Render fieldset html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //
        //return $this->toHtml();
        return '';
    }
}
