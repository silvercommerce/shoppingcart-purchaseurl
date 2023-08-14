<?php

namespace SilverCommerce\ShoppingCart\PurchaseURL;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverCommerce\ShoppingCart\Model\ShoppingCart as ShoppingCartModel;
use SilverCommerce\ShoppingCart\Control\ShoppingCart as ShoppingCartController;
use SilverStripe\Forms\ReadonlyField;

class ShoppingCartModelExtension extends DataExtension
{
    private static $casting = [
        'PurchaseLink' => 'Varchar'
    ];

    private static $field_labels = [
        'PurchaseLinkURL' => 'Purchase URL'
    ];

    /**
     * @return ShoppingCartModel
     */
    public function getOwner()
    {
        return parent::getOwner();
    }

    public function PurchaseLink()
    {
        $owner = $this->getOwner();
        $controller = Injector::inst()
            ->get(ShoppingCartController::class);

        $link = $controller->AbsoluteLink(
            ShoppingCartControllerExtension::ACTION_ACTIVATE
        );

        return Controller::join_links(
            $link,
            $owner->UuidSegment(),
            $owner->AccessKey
        );
    }

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();

        $fields->addFieldToTab(
            'Root.Main.OrdersDetails.OrdersDetailsMisc',
            ReadonlyField::create(
                'PurchaseLinkURL',
                $owner->fieldLabel('PurchaseLinkURL')
            )->setValue($owner->PurchaseLink())
        );
    }
}
