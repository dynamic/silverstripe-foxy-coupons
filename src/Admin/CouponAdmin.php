<?php

namespace Dynamic\Foxy\Coupons\Admin;

use Dynamic\Foxy\Coupons\Model\Coupon;
use SilverStripe\Admin\ModelAdmin;

/**
 * Class CouponAdmin
 * @package Dynamic\Foxy\Orders\Admin
 */
class CouponAdmin extends ModelAdmin
{
    /**
     * @var array
     */
    private static $managed_models = [
        Coupon::class,
    ];

    /**
     * @var string
     */
    private static $url_segment = 'coupons';

    /**
     * @var string
     */
    private static $menu_title = 'Coupons';

    /**
     * @var int
     */
    private static $menu_priority = 4;

    /**
     * @var string
     */
    private static $menu_icon_class = 'font-icon-tags';
}
