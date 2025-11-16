<?php

namespace App\Controllers;

/**
 * Handles all public-facing static pages like Home and Contact.
 */
class PagesController extends BaseController
{
    /**
     * Displays the public homepage.
     * If the user is already logged in, they are redirected to their dashboard.
     */
    public function showHome(): void
    {
        // Check if user is logged in
        if ($this->session->has('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        // User is a guest, show the public homepage
        $this->render('pages/home.php', ['title' => 'Starlight Dominion']);
    }

    /**
     * Displays the public contact page.
     * If the user is already logged in, they are redirected to their dashboard.
     */
    public function showContact(): void
    {
        // Check if user is logged in
        if ($this->session->has('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        // User is a guest, show the public contact page
        $this->render('pages/contact.php', [
            'title' => 'Contact Us',
            'layoutMode' => 'full' // Use the full-width layout
        ]);
    }
}