<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Page 
 * This controller show all static pages
 *
 * @author Octal
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Page extends CI_Controller {

    public $brand_data = null;
    public $viewData = array();
    public $segment = 3;

    const per_page = FRONT_PERPAGE;

    public function __construct() {
        parent::__construct();
        global $_sbd;
        $this->brand_data = (object) $_sbd;
        $this->layout->set_layout("default_layout");
        $this->load->language(array('home', 'form_validation'));
    }

    public function cookies() {
        $this->viewData['title'] = 'Cookies';
        $this->layout->view('page/cookies', $this->viewData);
    }

    public function accessibility() {
        $this->viewData['title'] = 'Accessibility';
        $this->layout->view('page/accessibility', $this->viewData);
    }

    public function enquiry() {
        $this->viewData['title'] = 'General Enquiries';
        $this->layout->view('page/enquiry', $this->viewData);
    }

    public function sitemap() {
        $this->viewData['title'] = 'Site Map';
        $this->layout->view('page/sitemap', $this->viewData);
    }

    public function avios_inspires() {
        $this->load->library("social_feeds");
        $instagram_feeds = array();
        $instagram_feeds = $this->social_feeds->get_instagram_feeds("aviosinspires", 12);
        $this->viewData['instagram_feeds'] = $instagram_feeds;
        $this->viewData['title'] = 'Inspires';
        $this->layout->view('page/avios_inspires', $this->viewData);
    }

}
