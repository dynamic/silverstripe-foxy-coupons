<?php

namespace Dynamic\Foxy\Coupons\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;

/**
 * Class CouponCode
 * @package Dynamic\Foxy\Coupons\Model
 */
class CouponCode extends DataObject
{
    /**
     * @var string
     */
    private static $table_name = 'CouponCode';

    /**
     * @var string
     */
    private static $singular_name = 'Coupon Code';

    /**
     * @var string
     */
    private static $plural_name = 'Coupon Codes';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(50)',

        //Readonly Fields
        'NumberOfUsesToDate' => 'Int',
        'FoxyDateCreated' => 'Date',
        'FoxyDateModified' => 'Date',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Coupon' => Coupon::class,
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Code',
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'Title' => true,
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName([
                'Title',
                'NumberOfUsesToDate',
                'FoxyDateCreated',
                'FoxyDateModified',
                'CouponID',
            ]);

            $fields->addFieldsToTab(
                'Root.Main',
                [
                    TextField::create('Title')
                        ->setTitle('Code'),
                ]
            );
        });

        return parent::getCMSFields();
    }

    /**
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if (!$this->exists()) {
            if ($couponCode = CouponCode::get()->filter('Title:nocase', $this->Code)->first()) {
                $result->addError("Coupon code {$couponCode->Title} already exists. Please use a different code or remove {$couponCode->Title} first.");
            }
        }

        return $result;
    }

    /**
     * @param null $member
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return Coupon::singleton()->canCreate($member, $context);
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        return Coupon::singleton()->canEdit($member);
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        return Coupon::singleton()->canDelete($member);
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canView($member = null)
    {
        return Coupon::singleton()->canView($member);
    }
}
