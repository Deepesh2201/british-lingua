<?php if(get_frontend_settings('recaptcha_status')): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<!---------- Header Section End  ---------->
<section class="sign-up my-5 pt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12 col-12 text-center ">
                <img loading="lazy" width="65%" src="<?php echo site_url('assets/frontend/default-new/image/cloud-security.gif') ?>">
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12 col-12 ">
                <div class="sing-up-right text-center">
                    <h3 class="mb-4">OTP Verified</h3>
                    <div class="verified-icon mb-4">
                        <img src="<?php echo site_url('assets/frontend/default-new/image/checkmark-transparent.gif') ?>" alt="Verified" width="100">
                    </div>
                    <p class="mb-4">Your mobile number has been successfully verified. You can now proceed to log in.</p>
                    <div class="log-in">
                        <a href="<?php echo site_url('login') ?>" class="btn btn-primary btn-lg my-0">
                            <span class="fas fa-angle-left"></span>
                            <?php echo get_phrase('Back to login') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.sign-up {
    background-color: #f9f9f9;
    padding: 60px 0;
}

.sing-up-right {
    background: #fff;
    padding: 30px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.sing-up-right h3 {
    color: #4caf50;
    font-size: 28px;
    font-weight: 700;
}

.verified-icon img {
    width: 100px;
}

.log-in .btn {
    background-color: #4caf50;
    border-color: #4caf50;
    color: #fff;
    font-size: 18px;
    font-weight: 600;
    padding: 12px 30px;
    border-radius: 50px;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.log-in .btn:hover {
    background-color: #43a047;
    border-color: #43a047;
}

.log-in .btn .fas {
    margin-right: 8px;
}
</style>
