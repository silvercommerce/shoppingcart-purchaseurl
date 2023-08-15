<?php

namespace SilverCommerce\ShoppingCart\PurchaseURL\Tests;

use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\FunctionalTest;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\ShoppingCart\Model\ShoppingCart as ShoppingCartModel;
use SilverCommerce\ShoppingCart\PurchaseURL\ShoppingCartControllerExtension;
use SilverCommerce\ShoppingCart\Control\ShoppingCart as ShoppingCartController;

class ShoppingCartControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'ShoppingCart.yml';

    public function setUp(): void
    {
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
            $cart->AbsoluteLink(ShoppingCartController::ACTION_ACTIVATE),
            $cart->UuidSegment(),
            $cart->AccessKey
        );

        $this->assertEquals($link, $cart->PurchaseLink());

        $result = $this
            ->get($cart->Link(ShoppingCartController::ACTION_ACTIVATE));

        // If no key/UUID provided
        $this->assertEquals(404, $result->getStatusCode());

        // Unassigned cart can be attached to anyone
        $result = $this->get($cart->PurchaseLink());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testActivateMember() 
    {
        $member = $this->objFromFixture(Member::class, 'testuser');
        $this->logInAs($member);

        $link = Controller::join_links(
            $cart->Link(ShoppingCartController::ACTION_ACTIVATE),
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
        $result = $this->get($cart->PurchaseLink());

        $this->assertExactHTMLMatchBySelector(
            "",
            ["Log in"]
        );

        $this->assertPartialMatchBySelector(
            "body",
            ['<form id="MemberLoginForm_LoginForm"']
        );

        // If incorrect user logged in
        $this->logInAs($member);
        $result = $this->get($cart->PurchaseLink());
        $this->assertEquals(403, $result->getStatusCode());
        $this->logOut();

        // If correct user logged in
        $member = $this->objFromFixture(
            Member::class,
            'testuser2'
        );
        $cart = $this->objFromFixture(
            ShoppingCartModel::class,
            'test2'
        );

        $this->logInAs($member);
        $result = $this->get($cart->PurchaseLink());
        $this->assertEquals(200, $result->getStatusCode());
        $this->logOut();
    }
}
