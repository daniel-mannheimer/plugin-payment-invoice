<?php //strict

namespace Invoice\Methods;

use Invoice\Services\SessionStorageService;
use Invoice\Services\SettingsService;
use Plenty\Plugin\Application;
use Plenty\Modules\Frontend\Contracts\Checkout;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;

/**
 * Class InvoicePaymentMethod
 * @package Invoice\Methods
 */
class InvoicePaymentMethod extends PaymentMethodService
{
    /** @var SettingsService */
    private $settings;

    /** @var  SessionStorageService */
    private $session;

    /** @var  Checkout */
    private $checkout;


    public function __construct(SettingsService $settings, SessionStorageService $session, Checkout $checkout)
    {
        $this->settings = $settings;
        $this->session  = $session;
        $this->checkout = $checkout;
    }

    /**
     * Check the configuration if the payment method is active
     * Return true if the payment method is active, else return false
     *
     * @param BasketRepositoryContract $basketRepositoryContract
     * @return bool
     */
    public function isActive( BasketRepositoryContract $basketRepositoryContract):bool
    {

        /** @var Basket $basket */
        $basket = $basketRepositoryContract->load();

        $lang = $this->session->getLang();

        /**
         * Check the minimum amount
         */
        if( $this->settings->getSetting('minimumAmount',$lang) > 0.00 &&
            $basket->basketAmount < $this->settings->getSetting('minimumAmount'))
        {
            return false;
        }

        /**
         * Check the maximum amount
         */
        if( $this->settings->getSetting('maximumAmount',$lang) > 0.00 &&
            $this->settings->getSetting('maximumAmount',$lang) < $basket->basketAmount)
        {
            return false;
        }

        /**
         * Check whether the invoice address is the same as the shipping address
         */
        if( $this->settings->getSetting('invoiceEqualsShippingAddress',$lang) == 1)
        {
            return false;
        }

        /**
        * Check whether the user is logged in
        */
        if( $this->settings->getSetting('disallowInvoiceForGuest',$lang) == 1)
        {
            return false;
        }

        if(!in_array($this->checkout->getShippingCountryId(), $this->settings->getSetting('shippingCountries')))
        {
            return false;
        }

        return true;
    }

    /**
     * Get the name of the payment method. The name can be entered in the config.json.
     *
     * @return string
     */
    public function getName( ):string
    {
        $lang = $this->session->getLang();

        if(!empty($lang))
        {
            $name = $this->settings->getSetting('name', $lang);
        }
        else
        {
            $name = $this->settings->getSetting('name');
        }


        return $name;

    }

    /**
     * Get additional costs for the payment method. Additional costs can be entered in the config.json.
     *
     * @param BasketRepositoryContract $basketRepositoryContract
     * @return float
     */
    public function getFee( BasketRepositoryContract $basketRepositoryContract):float
    {
        $basket = $basketRepositoryContract->load();
        if($basket->shippingCountryId == 1)
        {
            return $this->settings->getSetting('feeDomestic', $this->session->getLang());
        }
        else
        {
            return $this->settings->getSetting('feeForeign', $this->session->getLang());
        }
    }

    /**
     * Get the path of the icon
     *
     * @return string
     */
    public function getIcon( ):string
    {
        if( $this->settings->getSetting('logo') == 1)
        {
            return $this->settings->getSetting('logoUrl');
        }
        elseif($this->settings->getSetting('logo') == 2)
        {
            $app = pluginApp(Application::class);
                $icon = $app->getUrlPath('invoice').'/images/icon.png';

                return $icon;
        }

        return '';
    }

    /**
     * Get the description of the payment method. The description can be entered in the config.json.
     *
     * @return string
     */
    public function getDescription(  ):string
    {
        switch($this->settings->getSetting('infoPageType', $this->session->getLang()))
        {
            case  1:    return $this->settings->getSetting('infoPageIntern', $this->session->getLang());
            case  2:    return $this->settings->getSetting('infoPageExtern', $this->session->getLang());
            default:    return '';
        }
    }
    
    /**
     * Check if it is allowed to switch to this payment method
     *
     * @return bool
     */
    public function isSwitchableTo()
    {
        return false;
    }
    
    /**
     * Check if it is allowed to switch from this payment method
     *
     * @return bool
     */
    public function isSwitchableFrom()
    {
        return true;
    }
}
