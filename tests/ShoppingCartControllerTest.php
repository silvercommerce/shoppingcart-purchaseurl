<?php

namespace SilverCommerce\ShoppingCart\PurchaseURL\Tests;

use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\FunctionalTest;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\ContactAdmin\Helpers\ContactHelper;
use SilverCommerce\ShoppingCart\Model\ShoppingCart as ShoppingCartModel;
use SilverCommerce\ShoppingCart\PurchaseURL\ShoppingCartControllerExtension;
use SilverCommerce\ShoppingCart\Control\ShoppingCart as ShoppingCartController;

class ShoppingCartControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'ShoppingCart.yml';

    public function setUp(): void
    {
        ContactHelper::config()->set('auto_sync', false);
        parent::setUp();
    }

    public function testActivateAnon()
    {
        $member = $this->objFromFixture(Member::class, 'testuser');
        $contact = $this->objFromFixture(Contact::class, 'testcontact');
        $this->logOut();

        $cart = $this->objFromFixture(
            ShoppingCartModel::class,
            'test1'
        );
        $link = Controller::join_links(
            $cart->AbsoluteLink(ShoppingCartControllerExtension::ACTION_ACTIVATE),
            $cart->UuidSegment(),
            $cart->AccessKey
        );

        $this->assertEquals($link, $cart->PurchaseLink());

        $result = $this
            ->get($cart->Link(ShoppingCartControllerExtension::ACTION_ACTIVATE));

        // If no key/UUID provided
        $this->assertEquals(404, $result->getStatusCode());

        // Unassigned cart can be attached to anyone
        $result = $this->get($cart->PurchaseLink());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testActivateMember()
    {
        $member = $this->objFromFixture(
            Member::class,
            'testuser'
        );
        $this->logInAs($member);

        $cart = $this->objFromFixture(
            ShoppingCartModel::class,
            'test1'
        );

        $link = Controller::join_links(
            $cart->Link(ShoppingCartControllerExtension::ACTION_ACTIVATE),
            $cart->UuidSegment(),
            $cart->AccessKey
        );

        // Current user should be able to see
        $result = $this->get($link);
        $this->assertEquals(200, $result->getStatusCode());
        $this->logOut();

        // If user needs to be logged in
        $cart = $this->objFromFixture(
            ShoppingCartModel::class,
            'test3'
        );
        $result = $this->get($link);

        $this->assertPartialMatchBySelector(
            "h1",
            ["Log in"]
        );

        $this->assertPartialMatchBySelector(
            "",
            ['That page is secured. Enter your credentials below and we will send you right along.']
        );

        // If incorrect user logged in
        $this->logInAs($member);
        $cart = $this->objFromFixture(
            ShoppingCartModel::class,
            'test3'
        );

        $link = Controller::join_links(
            $cart->Link(ShoppingCartControllerExtension::ACTION_ACTIVATE),
            $cart->UuidSegment(),
            $cart->AccessKey
        );

        $result = $this->get($link);
        $this->assertEquals(403, $result->getStatusCode());
        $this->logOut();

        // If correct user logged in
        $member = $this->objFromFixture(
            Member::class,
            'testuser2'
        );
        $contact = $this->objFromFixture(
            Contact::class,
            'testcontact2'
        );
        $contact->MemberID = $member->ID;
        $contact->write();

        // Finally, correct user logged in
        $this->logInAs($member);
        $result = $this->get($link);
        $this->assertEquals(200, $result->getStatusCode());
        $this->logOut();
    }
}
