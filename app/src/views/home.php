<!DOCTYPE html>
<html lang="<?php echo Language::getCurrentLanguage(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="icon" href="<?php echo SITE_URL; ?>/dist/icons/marreta.svg" type="image/svg+xml">
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo SITE_URL; ?>" />
    <meta property="og:title" content="<?php echo SITE_NAME; ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars(SITE_DESCRIPTION); ?>" />
    <meta property="og:image" content="<?php echo SITE_URL; ?>/dist/images/opengraph.png" />
    <style>
    <?php
        $css_file = 'dist/css/style.css';
        if (file_exists($css_file)) {
            echo file_get_contents($css_file);
        }
    ?>
    </style>
</head>

<body>
    <div class="container">
        <header>
            <div class="brand">
                <span class="icon icon--marreta"></span>
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <nav>
                <a target="_blank" href="https://github.com/manualdousuario/marreta/wiki/API-Rest">API Rest</a>
                <a target="_blank" href="https://github.com/manualdousuario/marreta/">Github</a>
                <div class="integration">
                    <button class="integration__toggle"><?php echo Language::get('nav_integration'); ?><span class="arrow"></span></button>
                    <div class="integration__menu">
                        <a target="_blank" href="https://bsky.app/profile/marreta.pcdomanual.com"><span class="name">Bsky</span><span class="icon icon--bsky"></span></a>
                        <a target="_blank" href="https://t.me/leissoai_bot"><span class="name">Telegram</span><span class="icon icon--telegram"></span></a>
                        <a target="_blank" href="https://www.icloud.com/shortcuts/3594074b69ee4707af52ed78922d624f"><span class="name">MacOS</span><span class="icon icon--apple"></span></a>
                    </div>
                </div>
            </nav>
            <div class="extension">
                <button class="extension__toggle"><?php echo Language::get('nav_extension'); ?></button>
                <div class="extension__menu">
                    <a target="_blank" href="https://addons.mozilla.org/pt-BR/firefox/addon/marreta/"><span class="name">Firefox</span><span class="icon icon--firefox"></span></a>
                    <a target="_blank" href="#"><span class="name">Chrome</span><span class="icon icon--chrome"></span></a>
                </div>
            </div>
        </header>

        <main>
            <h2 class="description"><?php echo SITE_DESCRIPTION; ?></h2>
            <p class="walls_destroyed">
                <strong><?php echo number_format($cache_folder, 0, ',', '.'); ?></strong> <span><?php echo Language::get('walls_destroyed'); ?></span>
            </p>
            <form id="urlForm" method="POST" onsubmit="return validateForm()" class="space-y-6">
                <?php if ($message): ?>
                    <div class="<?php echo $message_type === 'error' ? 'error' : 'warning'; ?>">
                        <div>
                            <div>
                                <?php if ($message_type === 'error'): ?>
                                    <span class="icon icon--error"></span>
                                <?php else: ?>
                                    <span class="icon icon--warning"></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="<?php echo $message_type === 'error' ? 'error' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($message); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="fields">
                    <div class="input">
                        <span class="icon icon--link"></span>
                        <input type="url"
                            name="url"
                            id="url"
                            placeholder="<?php echo Language::get('url_placeholder'); ?>"
                            value="<?php echo htmlspecialchars($url); ?>"
                            required
                            pattern="https?://.+"
                            title="<?php echo Language::getMessage('INVALID_URL')['message']; ?>">
                    </div>
                    <button type="submit" alt="<?php echo Language::get('analyze_button'); ?>">
                        <span class="icon icon--marreta"></span>
                    </button>
                </div>
                <p class="adblock"><?php echo str_replace('{site_name}', SITE_NAME, Language::get('adblocker_warning')); ?></p>
            </form>

            <div class="plus">
                <div class="add_as_app">
                    <h2>
                        <span class="icon icon--android"></span><?php echo Language::get('add_as_app'); ?>
                    </h2>
                    <div class="text">
                        <div>
                            <ol>
                                <li><?php echo Language::get('add_as_app_step1'); ?></li>
                                <li><?php echo Language::get('add_as_app_step2'); ?></li>
                                <li><?php echo Language::get('add_as_app_step3'); ?></li>
                                <li><?php echo str_replace('{site_name}', SITE_NAME, Language::get('add_as_app_step4')); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="bookmarklet">
                    <h2>
                        <span class="icon icon--bookmark"></span><?php echo Language::get('bookmarklet_title'); ?>
                    </h2>
                    <div class="text">
                        <p>
                            <?php echo str_replace('{site_name}', SITE_NAME, Language::get('bookmarklet_description')); ?>
                        </p>
                        <div>
                            <a href="javascript:(function(){let currentUrl=window.location.href;window.location.href='<?php echo SITE_URL; ?>/p/'+encodeURIComponent(currentUrl);})()"
                                onclick="return false;">
                                <?php echo str_replace('{site_name}', SITE_NAME, Language::get('open_in')); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

    </div>

    <script>
    <?php
        $js_file = 'dist/js/scripts.js';
        if (file_exists($js_file)) {
            echo file_get_contents($js_file);
        }
    ?>
    </script>
</body>
</html>