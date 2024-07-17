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
                <div class="sing-up-right">
                    <h3>Verify Mobile</h3>
                    <p>An OTP has been sent to <?php echo $mobile_number; ?></p>

                    <form id="otp-form" action="<?php echo site_url('login/verify_otp'); ?>" method="post">
                        <div class="mb-3">
                            <h5>Enter OTP</h5>
                            <div class="otp-input-container">
                                <input class="otp-input" type="text" maxlength="1" name="otp[]" oninput="moveToNext(this, 'otp-2')" required>
                                <input id="otp-2" class="otp-input" type="text" maxlength="1" name="otp[]" oninput="moveToNext(this, 'otp-3')" required>
                                <input id="otp-3" class="otp-input" type="text" maxlength="1" name="otp[]" oninput="moveToNext(this, 'otp-4')" required>
                                <input id="otp-4" class="otp-input" type="text" maxlength="1" name="otp[]" required>
                            </div>
                        </div>
                        <p>Resend OTP in <span id="timer">01:30</span> seconds</p>
                        <a id="resend-otp" href="resend_otp" class="disabled" style="pointer-events: none; color: grey;">Resend OTP</a>
                        <div class="log-in">
                            <button type="submit" class="btn btn-primary">Verify Now</button>
                        </div>
                    </form>

                    <div class="log-in">
                        <a href="<?php echo site_url('login') ?>" class="btn btn-primary my-0">
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
.otp-input-container {
    display: flex;
    justify-content: space-between;
}

.otp-input {
    width: 50px;
    height: 50px;
    text-align: center;
    font-size: 24px;
    margin-right: 10px;
}

.otp-input:last-child {
    margin-right: 0;
}
</style>

<script>
function moveToNext(currentInput, nextInputId) {
    if (currentInput.value.length >= currentInput.maxLength) {
        document.getElementById(nextInputId).focus();
    }
}

// Countdown timer
function startTimer(duration, display) {
    var timer = duration, minutes, seconds;
    var interval = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            clearInterval(interval);
            document.getElementById('resend-otp').classList.remove('disabled');
            document.getElementById('resend-otp').style.pointerEvents = 'auto';
            document.getElementById('resend-otp').style.color = '';
            display.textContent = "00:00";
        }
    }, 1000);
}

window.onload = function () {
    var duration = 120; // 120 seconds
    var display = document.querySelector('#timer');
    startTimer(duration, display);
};
</script>
