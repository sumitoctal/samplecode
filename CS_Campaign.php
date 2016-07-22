<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CS_Campaign
 *
 * @author Octal
 */
class Cs_campaign {

    //put your code here
    var $CI = null;
    var $API = CS_API;

    public function __construct() {
        $this->CI = &get_instance();
    }

    /**
     * 
     * @return type
     */
    public function get_clients_id() {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_general.php';
        $auth = new CS_REST_General(array('api_key' => $this->API));
        return $auth->get_clients();
    }

    /**
     * 
     * @return type
     */
    public function get_client_detail($client_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_clients.php';
        $auth = new CS_REST_Clients($client_id, array('api_key' => $this->API));
        return $auth->get();
    }

    public function create_client($params = array()) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_clients.php';
        list($CompanyName, $Country, $Timezone) = $params;
        $wrap = new CS_REST_Clients(NULL, array('api_key' => $this->API));
        $result = $wrap->create(array(
            'CompanyName' => $CompanyName,
            'Country' => $Country,
            'Timezone' => $Timezone
        ));
        if ($result->was_successful()) {
            return array("status" => "success", "response" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    /**
     * @author Octal <developer@octalsoftware.com>
     * @param <string> $subject
     * @param <string> $name
     * @param <string> $from_name
     * @param <string> $from_email
     * @param <string> $reply_to
     * @param <string> $html_url
     * @param <array> $list_ids
     * @return <array> response code with data and message
     */
    public function create_campaign($client_id, $subject, $name, $from_name, $from_email, $reply_to, $html_url, $list_ids = array()) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns(null, array('api_key' => $this->API));
        $result = $wrap->create($client_id, array(
            'Subject' => $subject,
            'Name' => $name,
            'FromName' => $from_name,
            'FromEmail' => $from_email,
            'ReplyTo' => $reply_to,
            'HtmlUrl' => $html_url,
            # 'TextUrl' => 'Optional campaign text import URL',
            'ListIDs' => $list_ids,
                #'SegmentIDs' => array('First Segment', 'Second Segment')
        ));
        if ($result->was_successful()) {
            return array("status" => "success", $result->response);
        } else {
            return array("status" => "error", $result->response);
        }
    }

    public function create_campaign_from_template($client_id, $subject, $name, $from_name, $from_email, $reply_to, $template_id, $template_content, $list_ids = array()) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns(null, array('api_key' => $this->API));
        $result = $wrap->create_from_template($client_id, array(
            'Subject' => $subject,
            'Name' => $name,
            'FromName' => $from_name,
            'FromEmail' => $from_email,
            'ReplyTo' => $reply_to,
            'ListIDs' => $list_ids,
            'TemplateID' => $template_id,
            'TemplateContent' => array(
                "Images" =>
                array(
                array(
                    "Content" => isset($template_content['logo']) ? $template_content['logo'] : '',
                    "Alt" => ""
                ),
                array(
                    "Content" => isset($template_content['img1']) ? $template_content['img1'] : '',
                    "Alt" => ""
                ),
                array(
                    "Content" => isset($template_content['img2']) ? $template_content['img2'] : '',
                    "Alt" => ""
                ),
                array(
                    "Content" => isset($template_content['img3']) ? $template_content['img3'] : '',
                    "Alt" => ""
                ),
                array(
                    "Content" => isset($template_content['img4']) ? $template_content['img4'] : '',
                    "Alt" => ""
                ) 
                ),
                "Singlelines" =>
                array(
                    array(
                        "Content" => isset($template_content['date']) ? $template_content['date'] : ''
                    ),
                    array(
                        "Content" => isset($template_content['title']) ? $template_content['title'] : ''
                    )
                ),
                "Multilines" =>
            array(
                array(
                    "Content" => isset($template_content['subheading']) ? $template_content['subheading'] : ''
                ),
                array(
                    "Content" => isset($template_content['description']) ? $template_content['description'] : ''
                ),
                array(
                    "Content" => isset($template_content['boilerplate']) ? $template_content['boilerplate'] : ''
                ),
                array(
                    "Content" => isset($template_content['contactinfo']) ? $template_content['contactinfo'] : ''
                )
            ) 
            )
        ));
        if ($result->was_successful()) {
            return array("status" => "success", $result->response);
        } else {
            return array("status" => "error", $result->response);
        }
    }

    /**
     * @author Octal S <developer@octalsoftware.com>
     * @return <array> All receipts list's
     */
    public function get_all_lists($client_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_clients.php';
        $wrap = new CS_REST_Clients($client_id
                , array('api_key' => $this->API));

        $result = $wrap->get_lists();
        if ($result->was_successful()) {
            return $result->response;
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    /**
     * @author Octal S <developer@octalsoftware.com>
     * @return <array> All receipts list's
     */
    public function get_all_templates($client_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_clients.php';
        $wrap = new CS_REST_Clients($client_id
                , array('api_key' => $this->API));

        $result = $wrap->get_templates();
        if ($result->was_successful()) {
            return $result->response;
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    /**
     * @author Octal S <developer@octalsoftware.com>
     * @param <String [Crypt]> $campaign_id
     * @param <String [Email]> $confirmation_mail
     * @param <String [Date]> $send_date
     * @return <Array>
     */
    public function schedule_campaign($campaign_id, $confirmation_mail, $send_date) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));

        $result = $wrap->send(array(
            'ConfirmationEmail' => $confirmation_mail,
            'SendDate' => $send_date
        ));
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    /**
     * @author Octal S <developer@octalsoftware.com>
     * @param <String [Crypt]> $campaign_id
     * @param <String [Email]> $confirmation_mail
     * @return <Array>
     */
    public function campaign_send_preview($campaign_id, $test_emails = array()) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->send_preview($test_emails, 'Fallback');
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    public function delete_campaign($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->delete();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    public function unschedule_campaign($campaign_id) {
        error_reporting(0);
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->unschedule();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    /**
     * 
     * @param type $client_id
     * @param type $list_name
     * @return type
     */
    public function create_list($client_id, $list_name) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_lists.php';
        $wrap = new CS_REST_Lists(NULL, array('api_key' => $this->API));
        $result = $wrap->create($client_id, array(
            'Title' => $list_name,
            'UnsubscribePage' => '',
            'ConfirmedOptIn' => false,
            'ConfirmationSuccessPage' => '',
            'UnsubscribeSetting' => CS_REST_LIST_UNSUBSCRIBE_SETTING_ALL_CLIENT_LISTS
        ));
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "list_id" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }

    /**
     * 
     * @param type $list_id
     * @param type $subscribers
     * @return type
     */
    public function add_subscriber($list_id, $subscribers) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_subscribers.php';
        $wrap = new CS_REST_Subscribers($list_id, array('api_key' => $this->API));
        $result = $wrap->import($subscribers, true);
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
    public function campaign_summary($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->get_summary();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "summary" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
    public function campaign_recipients($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->get_recipients();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "recipients" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
    public function campaign_opens($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->get_opens();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "opens" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
    public function campaign_clicks($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->get_clicks();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "clicks" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
    public function campaign_unsubscribes($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->get_unsubscribes();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "unsubscribes" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
    public function campaign_bounces($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->get_bounces();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "bounces" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
    public function campaign_spam($campaign_id) {
        require_once APPPATH . 'third_party/campaignmonitor-createsend-php-4e2e822/csrest_campaigns.php';
        $wrap = new CS_REST_Campaigns($campaign_id, array('api_key' => $this->API));
        $result = $wrap->get_spam();
        if ($result->was_successful()) {
            return array("status" => "success", "http_status_code" => $result->http_status_code, "spam" => $result->response);
        } else {
            return array("status" => "error", "http_status_code" => $result->http_status_code, "response" => $result->response->Message);
        }
    }
    
}

?>
