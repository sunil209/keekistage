<?php

/**
 * Function for re-activating already installed advanced plugin
 */
function keekiShippingActivate(){
    keekiShippingInstall();
}
/**
 * Function for installing plugin
 *
 */
function keekiShippingInstall(){
    $version = get_option('keeki_shipping_version');

    switch($version) {
        case false:
        case 0:
            $keekiShippingDB=new KeekiShippingDB();
            $keekiShippingDB->installPostcodeData();
            $keekiShippingDB->installShippingPriceData();

            $postcodes = $keekiShippingDB->csvToArray(plugin_dir_path(__FILE__) .'data/postcodes.csv');
            $keekiShippingDB->addData('keeki_postcodes', $postcodes);

            $prices=$keekiShippingDB->constructPriceTable(plugin_dir_path(__FILE__) .'data/prices.csv');

            $keekiShippingDB->addData('keeki_shipping_prices', $prices);
        break;
    }
    update_option('keeki_shipping_version', '1.0');
}
