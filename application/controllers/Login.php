<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set(get_settings('timezone'));

        // Your own constructor code
        $this->load->database();
        $this->load->library('session');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        //Check custom session data
        $this->user_model->check_session_data();
    }

    public function index()
    {
        //Check custom session data
        $this->user_model->check_session_data('login');

        $page_data['page_name'] = 'login';
        $page_data['page_title'] = site_phrase('login');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function sign_up()
    {
        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }
        $page_data['page_name'] = 'sign_up';
        $page_data['page_title'] = site_phrase('sign_up');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function validate_login($from = "")
    {
        if ($this->crud_model->check_recaptcha() == false && get_frontend_settings('recaptcha_status') == true) {
            $this->session->set_flashdata('error_message', get_phrase('recaptcha_verification_failed'));
            redirect(site_url('login'), 'refresh');
        }

        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $credential = array('email' => $email, 'password' => sha1($password), 'status' => 1);

        // Checking login credential for admin
        $query = $this->db->get_where('users', $credential);

        if ($query->num_rows() > 0) {
            $row = $query->row();
            // Check if phone is verified
            // if ($row->email_verified == 0) {
            //     $this->session->set_userdata('email', $email);
            //     $page_data['page_name'] = 'verification_code';
            // $page_data['page_title'] = site_phrase('verification_code');
            // $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
            // }
            // else{

            
            // Check if phone is verified
            if ($row->phone_verified == 0) {
                $this->otp_verification('+91' . $row->phone);
            } else {
                $this->user_model->new_device_login_tracker($row->id);
                $this->user_model->set_login_userdata($row->id);
                redirect(site_url('user'), 'refresh');
            }
        // }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('invalid_login_credentials'));
            redirect(site_url('login'), 'refresh');
        }
    }

    public function new_login_confirmation($param1 = "")
    {
        $new_device_code_expiration_time = $this->session->userdata('new_device_code_expiration_time');
        if (!$new_device_code_expiration_time || $new_device_code_expiration_time < (time())) {
            $this->session->set_flashdata('error_message', get_phrase('time_over') . '! ' . site_phrase('please_try_again'));
            redirect(site_url('login'), 'refresh');
        }

        if ($param1 == 'submit') {
            $new_device_verification_code = $this->input->post('new_device_verification_code');
            if ($new_device_verification_code != $this->session->userdata('new_device_verification_code')) {
                $this->session->set_flashdata('error_message', get_phrase('verification_code_is_wrong'));
                redirect(site_url('login/new_login_confirmation'), 'refresh');
            }

            // Checking login credential for admin
            $query = $this->db->get_where('users', array('id' => $this->session->userdata('new_device_user_id')));

            if ($query->num_rows() > 0) {
                $row = $query->row();

                // For device login tracker
                $this->user_model->new_device_login_tracker($row->id, true);
                $this->user_model->set_login_userdata($row->id);
            }
            $this->session->set_flashdata('error_message', get_phrase('something_is_wrong') . '! ' . site_phrase('please_try_again'));
            redirect(site_url('home'), 'refresh');
        }

        if ($param1 == 'resend') {
            $this->email_model->new_device_login_alert();
            return;
        }

        $page_data['page_name'] = 'new_login_confirmation';
        $page_data['page_title'] = site_phrase('new_login_confirmation');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function fb_validate_login($access_token = "", $fb_user_id = "")
    {
        $this->social_login_modal->fb_validate_login($access_token, $fb_user_id);
    }

    public function register()
    {

        if ($this->crud_model->check_recaptcha() == false && get_frontend_settings('recaptcha_status') == true) {
            $this->session->set_flashdata('error_message', get_phrase('recaptcha_verification_failed'));
            redirect(site_url('login'), 'refresh');
        }

        $data['first_name'] = html_escape($this->input->post('first_name'));
        $data['last_name'] = html_escape($this->input->post('last_name'));
        $data['email'] = html_escape($this->input->post('email'));
        $data['phone'] = html_escape($this->input->post('phone'));
        $data['password'] = sha1($this->input->post('password'));

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['phone']) || empty($data['password'])) {
            $this->session->set_flashdata('error_message', site_phrase('your_sign_up_form_is_empty') . '. ' . site_phrase('fill_out_the_form with_your_valid_data'));
            redirect(site_url('sign_up'), 'refresh');
        }

        $verification_code = rand(100000, 200000);
        $data['verification_code'] = $verification_code;

        if (get_settings('student_email_verification') == 'enable') {
            $data['status'] = 1;
        } else {
            $data['status'] = 1;
        }

        $data['wishlist'] = json_encode(array());
        $data['date_added'] = strtotime(date("Y-m-d H:i:s"));
        $social_links = array(
            'facebook' => "",
            'twitter' => "",
            'linkedin' => "",
        );
        $data['social_links'] = json_encode($social_links);
        $data['role_id'] = 2;

        $data['payment_keys'] = json_encode(array());

        $validity = $this->user_model->check_duplication('on_create', $data['email']);

        if ($validity === 'unverified_user' || $validity == true) {

            //Check instructor application document
            if (get_settings('allow_instructor')):
                if (isset($_POST['instructor']) && $_POST['instructor'] == 'yes') {
                    if (empty($_POST['phone'])) {
                        $this->session->set_flashdata('error_message', get_phrase('Enter your valid phone number'));
                        redirect(site_url('sign_up'), 'refresh');
                    }
                    if (empty($_FILES['document']['name'])) {
                        $this->session->set_flashdata('error_message', get_phrase('Please choice your document file'));
                        redirect(site_url('sign_up'), 'refresh');
                    }
                    $accepted_ext = array('doc', 'docs', 'pdf', 'txt', 'png', 'jpg', 'jpeg');
                    $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), $accepted_ext)) {
                        $instructor_apply = true;
                    } else {
                        $this->session->set_flashdata('error_message', get_phrase('Invalide document file'));
                        redirect(site_url('sign_up'), 'refresh');
                    }
                }
            endif;
            //End Check  instructor application document

            if ($validity === true) {
                $user_id = $this->user_model->register_user($data);
            } else {
                $this->user_model->register_user_update_code($data, $data['status']);
            }

            //instructor application
            if (isset($instructor_apply) && $instructor_apply == true):
                $this->user_model->instructor_application();
            endif;
            //End instructor application

            if (get_settings('student_email_verification') == 'enable') {
                $this->email_model->send_email_verification_mail($data['email'], $verification_code);

                if ($validity === 'unverified_user') {
                    $this->session->set_flashdata('info_message', get_phrase('you_have_already_registered') . '. ' . get_phrase('please_verify_your_email_address'));
                } else {
                    $this->session->set_flashdata('flash_message', get_phrase('your_registration_has_been_successfully_done') . '. ' . get_phrase('please_check_your_mail_inbox_to_verify_your_email_address') . '.');
                }
                $this->session->set_userdata('register_email', $this->input->post('email'));
                redirect(site_url('sign_up/verification_code'), 'refresh');
            } else {
                if (isset($user_id)) {
                    $this->email_model->signup_mail($user_id);
                }
                $this->session->set_flashdata('flash_message', get_phrase('your_registration_has_been_successfully_done'));
                redirect(site_url('login'), 'refresh');
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('you_have_already_registered'));
            redirect(site_url('login'), 'refresh');
        }
    }

    public function logout($from = "")
    {
        //destroy sessions of specific userdata. We've done this for not removing the cart session
        $this->user_model->session_destroy();
        redirect(site_url('login'), 'refresh');
    }

    public function forgot_password_request()
    {
        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }
        $page_data['page_name'] = 'forgot_password';
        $page_data['page_title'] = site_phrase('forgot_password');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }
    // OTP Verification starts here

    // Send OTP
    public function otp_verification($mobile)
    {
        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }

        // API details
        $api_url = "https://auth.otpless.app/auth/otp/v1/send";
        $client_id = "T2EVF10EFKERN4P9MRSD9P1N0DYSOE3X";
        $client_secret = "vfxumoix8g275yu7ppiytk9ltj2ghjsh";

        // Prepare the data to be sent
        $post_data = array(
            'phoneNumber' => $mobile,
            'channel' => 'SMS',
            'otpLength' => 4,
            'expiry' => 300,
        );

        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'clientId: ' . $client_id,
            'clientSecret: ' . $client_secret,
        ));

        // Execute cURL request
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if the OTP was sent successfully
        if ($http_status == 200) {
            $response_data = json_decode($response, true);
            $order_id = $response_data['orderId'];

            // Store the mobile number and orderId in the session for verification
            $this->session->set_userdata('otp_mobile_number', $mobile);
            $this->session->set_userdata('otp_order_id', $order_id);

            $page_data['page_name'] = 'otp_verification';
            $page_data['page_title'] = site_phrase('otp_verification');
            $page_data['mobile_number'] = $mobile;
            $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
        } else {
            $this->session->set_flashdata('error_message', 'Failed to send OTP. Please try again.');
            redirect(site_url('login'), 'refresh');
        }
    }

    // Verify OTP
    public function verify_otp()
    {
        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }

        // API details
        $api_url = "https://auth.otpless.app/auth/otp/v1/verify";
        $client_id = "T2EVF10EFKERN4P9MRSD9P1N0DYSOE3X";
        $client_secret = "vfxumoix8g275yu7ppiytk9ltj2ghjsh";

        $order_id = $this->session->userdata('otp_order_id'); // Retrieve orderId from session
        $otp = implode('', $this->input->post('otp')); // Retrieve and combine OTP from input form
        $mobile_number = $this->session->userdata('otp_mobile_number'); // Retrieve mobile number from session

        // Prepare the data to be sent
        $post_data = array(
            'orderId' => $order_id,
            'otp' => $otp,
            'phoneNumber' => $mobile_number,
        );

        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'clientId: ' . $client_id,
            'clientSecret: ' . $client_secret,
        ));

        // Execute cURL request
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if the OTP verification was successful
        if ($http_status == 200) {
            $response_data = json_decode($response, true);

            if (isset($response_data['isOTPVerified']) && $response_data['isOTPVerified'] === true) {
                $phone = substr($mobile_number, 3);
                // Update phone_verified status in your database
                $user_details = $this->db->get_where('users', array('phone' => $phone));

                $user_details = $user_details->row_array();

                $updater = array(
                    'phone_verified' => 1,
                );
                $this->db->where('id', $user_details['id']);
                $this->db->update('users', $updater);

                // $this->db->update('users', array('phone_verified' => 1), array('phone_number' => $mobile_number));

                // Clear OTP session data
                $this->session->unset_userdata('otp_mobile_number');
                $this->session->unset_userdata('otp_order_id');

                // Redirect to OTP verified page or user dashboard
                $page_data['page_name'] = 'otp_verified';
                $page_data['page_title'] = site_phrase('otp_verified');
                $page_data['mobile_number'] = $mobile_number;
                $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
            } else {
                // Handle OTP verification failure
                $this->session->set_flashdata('error_message', 'Invalid OTP. Please try again.');
                // Handle OTP verification failure
                // $this->session->set_flashdata('error_message', 'Failed to verify OTP. Please try again.');
                $page_data['page_name'] = 'otp_verification';
                $page_data['page_title'] = site_phrase('otp_verification');
                $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data); // Load the same OTP verification page with error message
            }
        } else {
            // Handle cURL request failure or HTTP status other than 200
            $this->session->set_flashdata('error_message', 'Failed to verify OTP. Please try again.');
            redirect(site_url('login/otp_verification'), 'refresh');
        }
    }

    // Resend OTP
    public function resend_otp()
    {
        $mobile = $this->session->userdata('otp_mobile_number');
        $order_id = $this->session->userdata('otp_order_id');

        // API details
        $api_url = "https://auth.otpless.app/auth/otp/v1/resend";
        $client_id = "T2EVF10EFKERN4P9MRSD9P1N0DYSOE3X";
        $client_secret = "vfxumoix8g275yu7ppiytk9ltj2ghjsh";

        // Prepare the data to be sent
        $post_data = array(
            'orderId' => $order_id,
        );

        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'clientId: ' . $client_id,
            'clientSecret: ' . $client_secret,
        ));

        // Execute cURL request
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if the OTP was resent successfully
        if ($http_status == 200) {
            $this->session->set_flashdata('success_message', 'OTP resent successfully.');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to resend OTP. Please try again.');
        }

                // Handle OTP verification failure
                // $this->session->set_flashdata('error_message', 'Failed to verify OTP. Please try again.');
                $page_data['page_name'] = 'otp_verification';
                $page_data['page_title'] = site_phrase('otp_verification');
                $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    // OTP Verification Ends Here

    public function forgot_password($from = "")
    {

        if ($this->crud_model->check_recaptcha() == false && get_frontend_settings('recaptcha_status') == true) {
            $this->session->set_flashdata('error_message', get_phrase('recaptcha_verification_failed'));
            redirect(site_url('login'), 'refresh');
        }
        $email = $this->input->post('email');
        $query = $this->db->get_where('users', array('email' => $email, 'status' => 1));
        if ($query->num_rows() > 0) {
            $this->crud_model->forgot_password();
            redirect(site_url('login'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', get_phrase('user_not_found'));
            redirect(site_url('login'), 'refresh');
        }
    }

    public function change_password($verification_code = "")
    {

        if ($verification_code == "") {
            $this->session->set_flashdata('error_message', get_phrase('invalid_verification_code') . '. ' . get_phrase('please_send_a_new_forgot_password_request'));
            redirect(site_url('login'), 'refresh');
        } else {
            $verification_code = str_replace(' ', '', $verification_code);
            $verification_code = str_replace('%20', '', $verification_code);
            $verification_code = str_replace('=', '', $verification_code);

            $decoded_verification_code = explode('--', base64_decode($verification_code));
            $solid_email = $decoded_verification_code[0];
            $email = str_replace('__', '@', $solid_email);

            $current_time = time();
            $expired_time = $current_time - 900;
            $this->db->where('email', $email);
            $this->db->where('verification_code', $verification_code);
            $row = $this->db->get('users');

            if ($row->row('last_modified') < $expired_time || $row->num_rows() <= 0) {
                $this->session->set_flashdata('error_message', get_phrase('This link has been expired.') . ' ' . get_phrase('Please send a new request'));
                redirect(site_url('login/forgot_password_request'), 'refresh');
            }
        }

        if (isset($_POST['new_password']) && isset($_POST['confirm_password']) && !empty($_POST['confirm_password']) && $verification_code) {
            $new_password = $this->input->post('new_password');
            $confirm_password = $this->input->post('confirm_password');
            if ($new_password == $confirm_password):
                $this->crud_model->change_password_from_forgot_passord($verification_code);
                $this->session->set_flashdata('flash_message', get_phrase('password_has_changed_successfully'));
                redirect(site_url('login'), 'refresh');
            else:
                $this->session->set_flashdata('error_message', get_phrase('the_confirmed_password_is_not_matching_with_the_new_password'));
                redirect(site_url('login/change_password/' . $verification_code), 'refresh');
            endif;
        }

        $page_data['verification_code'] = $verification_code;
        $page_data['page_name'] = 'change_password_from_forgot_password';
        $page_data['page_title'] = site_phrase('change_password');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);

    }

    public function resend_verification_code()
    {
        $email = $this->input->post('email');
        $verification_code = $this->db->get_where('users', array('email' => $email))->row('verification_code');
        $this->email_model->send_email_verification_mail($email, $verification_code);

        return true;
    }

    public function verify_email_address()
    {
        $email = $this->input->post('email');
        if(!$email){
            $email = $this->session->userdata('email');
        }
        $verification_code = $this->input->post('verification_code');
        $user_details = $this->db->get_where('users', array('email' => $email, 'verification_code' => $verification_code));
        
        if ($user_details->num_rows() > 0) {
            $user_details = $user_details->row_array();

            $updater = array(
                'email_verified' => 1,
            );
            $this->db->where('id', $user_details['id']);
            $this->db->update('users', $updater);

            $this->email_model->signup_mail($user_details['id']);

            $this->session->set_flashdata('flash_message', get_phrase('congratulations') . '! ' . get_phrase('your_email_address_has_been_successfully_verified') . '.');
            $this->session->set_userdata('register_email', null);

            echo true;
        } else {
            $this->session->set_flashdata('error_message', get_phrase('the_verification_code_is_wrong') . '.'.$email);
            echo false;
        }
    }

    public function check_recaptcha_with_ajax()
    {
        if ($this->crud_model->check_recaptcha()) {
            echo true;
        } else {
            echo false;
        }
    }

}
