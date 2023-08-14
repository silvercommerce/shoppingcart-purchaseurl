<?php

namespace SilverCommerce\ShoppingCart\PurchaseURL;

use LeKoala\Uuid\UuidExtension;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverCommerce\ShoppingCart\ShoppingCartFactory;
use SilverCommerce\ShoppingCart\Model\ShoppingCart as ShoppingCartModel;
use SilverCommerce\ShoppingCart\Control\ShoppingCart as ShoppingCartController;

/**
 * Inject activate action onto shoppingcart
 */
class ShoppingCartControllerExtension extends Extension
{
    const ACTION_ACTIVATE = 'activate';

    private static $allowed_actions = [
        self::ACTION_ACTIVATE
    ];

    /**
     * @return ShoppingCartController
     */
    public function getOwner()
    {
        return parent::getOwner();
    }

    /**
     * Activate a shopping cart via its link
     */
    public function activate()
    {
        $owner = $this->getOwner();
        $member = Security::getCurrentUser();
        $request = $owner->getRequest();
        $id = $request->param('ID');
        $key = $request->param('OtherID');
        $object = null;

        // If needed keys are not present, return 404
        if (empty($id) || empty($key)) {
            return $owner->httpError(404);
        }

        $object = UuidExtension::getByUuid(ShoppingCartModel::class, $id);

        // Ensure cart is available and has an access key
        if (empty($object)|| empty($object->AccessKey)) {
            return $owner->httpError(403);
        }

        // If access key is invalid, return an error
        if ($object->AccessKey !== $key) {
            return $owner->httpError(403);
        }

        $factory = ShoppingCartFactory::create();
        $customer = $object->Customer();
        $contact = null;

        // If order's customer not the current user, require login
        if (!empty($member)) {
            $contact = $member->Contact();
        }

        if (!empty($contact)
            && $customer->ID > 0
            && $customer->ID != $contact->ID
        ) {
            return Security::permissionFailure();
        }

        // Finally, assign the order and redirect
        $factory
            ->setOrder($object)
            ->write();

        return $owner->redirect($owner->Link());
    }#
}
