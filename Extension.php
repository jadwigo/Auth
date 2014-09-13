<?php

namespace Bolt\Extension\Bolt\Members;

use \Bolt\Extension\Bolt\ClientLogin\ClientLoginEvent;

/**
 *
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Extension extends \Bolt\BaseExtension
{
    /**
     * @var string Extension name
     */
    const NAME = 'Members';

    /**
     * @var Members\Controller
     */
    private $controller;

    public function getName()
    {
        return Extension::NAME;
    }

    public function initialize()
    {
        /*
         * Backend
         */
        if ($this->app['config']->getWhichEnd() == 'backend') {
            // Check & create database tables if required
            $records = new MembersRecords($this->app);
            $records->dbCheck();
        }

        /*
         * Frontend
         */
        if ($this->app['config']->getWhichEnd() == 'frontend') {
            // Set up routes
            $this->setController();
        }

        /*
         * Hooks
         */
        $this->app['dispatcher']->addListener('clientlogin.Login',  array($this, 'loginCallback'));
        $this->app['dispatcher']->addListener('clientlogin.Logout', array($this, 'logoutCallback'));
    }

    /**
     * Create controller and define routes
     */
    private function setController()
    {
        // Create controller object
        $this->controller = new Controller($this->app);

        // New member
        $this->app->match("{$this->config['basepath']}/new", array($this->controller, 'getMemberNew'))
                    ->bind('getMemberNew')
                    ->method('GET|POST');

        // Member profile
        $this->app->match("{$this->config['basepath']}/profile", array($this->controller, 'getMemberProfile'))
                    ->bind('getMemberProfile')
                    ->method('GET|POST');
    }

    /**
     *
     * @param ClientLoginEvent $event
     */
    public function loginCallback(ClientLoginEvent $event)
    {
        $members = new Members($this->app);

        // Get the ClientLogin user data from the event
        $userdata = $event->getUser();

        // Build a query key
        $key = strtolower($userdata['provider']) . ':' . $userdata['identifier'];

        // See if we have this in our database
        $id = $members->isMemberClientLogin($key);

        if ($id) {
            //
        } else {
            // If registration is closed, don't do anything
            if (! $this->config['registration']) {
// @TODO handle this properly
                return;
            }

            // Save any redirect that ClientLogin has pending
            $this->app['session']->set('pending',     $this->app['request']->get('redirect'));
            $this->app['session']->set('clientlogin', $userdata);

            // Some providers (looking at you Twitter) don't supply an email
            if (empty($userdata['email'])) {
                //
            } else {
                //
            }

            // Redirect to the 'new' page
            simpleredirect("/{$this->config['basepath']}/new");
        }

    }

    /**
     *
     * @param ClientLoginEvent $event
     */
    public function logoutCallback(ClientLoginEvent $event)
    {
    }

    /**
     * Default config options
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return array(
            'basepath' => 'members',
            'templates' => array(
                'parent'  => 'members.twig',
                'new'     => 'members_new.twig',
                'profile' => 'members_profile.twig'
            ),
            'registration' => true
        );
    }
}
