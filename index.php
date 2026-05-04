<?php
    include_once(__DIR__.'/libs.php');
    // init
    if (!file_exists(__DIR__.'/cache')) mkdir(__DIR__.'/cache');
    if (!file_exists(__DIR__.'/channels.txt')) file_put_contents(__DIR__.'/channels.txt','');
    $CHANNELS = file_parse();
    $VERSION  = '4';
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EzyTel | تلگرام خوان تحت وب</title>
    <script src="./src/script.js?ver=<?= $VERSION ?>"></script>
    <link rel="stylesheet" href="./src/style.css?ver=<?= $VERSION ?>">
</head>
<body>
    <div class="page_progress_bar"><div class="subline inc"></div><div class="subline dec"></div></div>
    <div class="menu_block">
    <div class="tgme_header_search">
        <svg class="tgme_header_search_form_icon" width="20" height="20" viewBox="0 0 20 20">
            <g fill="none" stroke="#7D7F81" stroke-width="1.4">
                <circle cx="9" cy="9" r="6"></circle><path d="M13.5,13.5 L17,17" stroke-linecap="round"></path>
            </g>
        </svg>
        <input class="tgme_header_search_form_input js-header_search" placeholder="Search" name="q" autocomplete="off" value="">
    </div>
    <?php if (count($CHANNELS) == 0): ?>
        <div class="menu_empty"><p>کانال های خوتونو به فایل channels.txt اضافه کنید و صفحه را رفرش کنید</p></div>
    <?php else: ?>
        <?php foreach ($CHANNELS as $chanenl): ?>
            <div class="menu_channel_wrapper" dateint="0">
                <div class="menu_channel_avatar" style="background: hsl(<?= rand(0,360) ?> 100% 75% / 1)"><img src="" style="opacity: 0;"><p><?= strtoupper(substr($chanenl,0,1)) ?></p></div>
                <div class="menu_channel_info"><p><?= $chanenl ?></p><span>... Loading</span></div>
                <div class="menu_channel_stats"></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
        <div class="menu_copyright"><p>© 2026 EzyTel (ver <?= $VERSION ?>)</p><span>Made with ❤️ for the Brave People of IRAN by J[o]King & contributors</span></div>
    </div>
    <div class="main_block">

    </div>
</body>
</html>
