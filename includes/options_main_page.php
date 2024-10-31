<?php
function wc_okey_main()
{
    ?>
    <div class="wrap">
        <h2><?php echo __('OKEY', 'wc_okey')?></h2>
    </div>
    <div class="container">
        <img src="<?php echo plugin_dir_url(__FILE__)?>../assets/images/okeyLogo.png" class="logo" />
        <div class="message">
            <p>Platforma online de <br> administrare a afacerii tale!</p>
        </div>
    </div>
    <p>
        <?php echo __('Nu aveti cont OKEY?', 'wc_okey')?>
        <br />
        <?php echo __('Inregistrati-va GRATUIT <a href="https://portal.eokey.ro/inregistrare" target="_blank">aici</a>', 'wc_okey')?>.
    </p>
    <?php
    // style files
    echo '<style>';
    include explode('includes', plugin_dir_path(__FILE__))[0].'style/main.css';
    echo '</style>';
}
