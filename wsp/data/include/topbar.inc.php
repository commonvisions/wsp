<div id="topholder">
    <div id="togglenav">
        <i class="fa-solid fa-bars"></i>
    </div>
    <div id="logoutarea">
        <span id="cntdwn"><b></b></span>
        <span id="logoutlink">
            <a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </span>
        <script>
            (() => {
                const display = document.querySelector('#cntdwn b');
                const logoutUrl = document.querySelector('#logoutlink a').href;
                const timeoutMinutes = <?php echo intval($_SESSION['wspvars']['autologout']); ?>;
                const deadline = Date.now() + timeoutMinutes * 60000;

                function updateCountdown() {
                    const remaining = deadline - Date.now();
                    if (remaining <= 0) {
                        window.location.href = logoutUrl;
                        return;
                    }

                    const seconds = Math.ceil(remaining / 1000);
                    display.textContent = [
                        Math.floor(seconds / 3600),
                        Math.floor(seconds / 60) % 60,
                        seconds % 60
                    ].map(value => String(value).padStart(2, '0')).join(':');

                    setTimeout(updateCountdown, remaining - (seconds - 1) * 1000);
                }

                updateCountdown();
            })();

            $('#togglenav').on('click', function() {
                const body = $('body').toggleClass('collapsed');
                document.cookie = `collapsed=${body.hasClass('collapsed') ? '1' : '0'}; Path=/; SameSite=Lax`;
            });

        </script>
    </div>
</div>
