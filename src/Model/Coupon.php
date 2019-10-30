<?php

namespace Dynamic\Foxy\Coupons\Model;

use Dynamic\Foxy\Discounts\Model\DiscountTier;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Class Coupon
 * @package Dynamic\Foxy\Coupons\Model
 */
class Coupon extends DataObject implements PermissionProvider
{
    /**
     *
     */
    const COUPON_CREATE_PERMISSION_CODE = 'Coupon_Create';

    /**
     *
     */
    const COUPON_EDIT_PERMISSION_CODE = 'Coupon_Edit';

    /**
     *
     */
    const COUPON_DELETE_PERMISSION_CODE = 'Coupon_Delete';

    /**
     *
     */
    const COUPON_VIEW_PERMISSION_CODE = 'Coupon_View';

    /**
     *
     */
    const YES_NO = [
        false => 'No',
        true => 'Yes',
    ];

    /**
     * @var string
     */
    private static $table_name = 'Coupon';

    /**
     * @var string
     */
    private static $singular_name = 'Coupon';

    /**
     * @var string
     */
    private static $plural_name = 'Coupons';

    /**
     * @var array
     */
    private static $db = [
        //Editable Fields
        'Name' => 'Varchar(50)',
        'DateRestricted' => 'Boolean',
        'StartDate' => 'Date',
        'EndDate' => 'Date',
        'AllowedUses' => 'Int',
        'AllowedUsesPerCustomer' => 'Int',
        'UsesPerCode' => 'Int',
        'SkuRestrictions' => 'Text',//5,000 characters or less
        'Type' => 'Enum(array("quantity_amount","quantity_percentage","price_amount","price_percentage"))',
        'CouponDetails' => 'Varchar(200)',
        'Combinable' => 'Boolean',
        'MultipleCodesAllowed' => 'Boolean',
        'ExcludeCategoryDiscounts' => 'Boolean',
        'ExcludeLineItemDiscounts' => 'Boolean',
        'IsTaxable' => 'Boolean',

        //Readonly Fields
        'UsesToDate' => 'Int',
        'FoxyDateCreated' => 'Date',
        'FoxyDateModified' => 'Date',
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Codes' => CouponCode::class,
        'CouponTiers' => DiscountTier::class,
    ];

    private static $defaults = [
        'Type' => 'quantity_amount',
        'Combinable' => false,
        'MultipleCodesAllowed' => false,
        'IsTaxable' => false,
        'ExcludeCategoryDiscounts' => false,
        'ExcludeLineItemDiscounts' => false,
        'DateRestricted' => false,
        'AllowedUses' => 0,
        'AllowedUsesPerCustomer' => 0,
        'UsesPerCode' => 0,
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Name' => 'Name',
        'Codes.Count' => 'Unique Codes',
        'CouponTiers.Count' => 'Coupon Discount Tiers',
        'HasBeenSyncd.Nice' => "Has Sync'd with Foxy",
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Name',
        'Codes.Title',
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName([
                'CouponTiers',
                'Codes',
                'StartDate',
                'EndDate',
                'CouponDetails',
                'DateRestricted',
                'StartDate',
                'EndDate',
                'SkuRestrictions',
                'AllowedUses',
                'AllowedUsesPerCustomer',
                'UsesPerCode',
                'Combinable',
                'MultipleCodesAllowed',
                'ExcludeCategoryDiscounts',
                'ExcludeLineItemDiscounts',
                'IsTaxable',
                'UsesToDate',
                'FoxyDateCreated',
                'FoxyDateModified',
            ]);

            if ($this->exists() && !$this->CouponTiers()->count()) {
                // TODO display box notifying this coupon will not sync until tiers are created
            }

            if ($this->exists() && !$this->Codes()->count()) {
                // TODO display box notifying this coupon will not sync until codes are created
            }

            // TODO provide config descriptions via lang yml files
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    TextField::create('Name')
                        ->setTitle('Coupon Name')
                        ->setDescription($this->config()->get('NameDescription')),
                    DropdownField::create('Type')
                        ->setTitle('Coupon Discount Type')
                        ->setDescription($this->config()->get('DiscountTypeDescription'))
                        // TODO provide source via lang yml files
                        ->setSource([
                            'quantity_amount' => 'Discounts by an amount, based on the quantity of to-be-discounted products',
                            'quantity_percentage' => 'Discounts by a percentage, based on the quantity of to-be-discounted products',
                            'price_amount' => 'Discounts by an amount, based on the price of to-be-discounted products',
                            'price_percentage' => 'Discounts by a percentage, based on the price of to-be-discounted products',
                        ]),
                    ToggleCompositeField::create(
                        'Restrictions',
                        'Coupon Restrictions',
                        FieldList::create(
                            DropdownField::create('DateRestricted')
                                ->setSource(self::YES_NO)
                                ->setTitle('Is this coupon restricted to a date range?')
                                ->setDescription('This will only allow the coupon to be valid between the given dates'),
                            $startDate = DateField::create('StartDate')
                                ->setTitle('Coupon Start Date'),
                            $endDate = DateField::create('EndDate')
                                ->setTitle('Coupon End Date'),
                            TextareaField::create('SkuRestrictions')
                                ->setTitle('Sku restrictions')
                                ->setDescription($this->config()->get('SkuRestrictionsDescription')),
                            NumericField::create('AllowedUses')
                                ->setTitle('Allowed Uses Limit (0 for no limit)')
                                ->setDescription($this->config()->get('AllowedUsesDescription')),
                            NumericField::create('AllowedUsesPerCustomer')
                                ->setTitle('Allowed Uses Per Customer (0 for unlimited)')
                                ->setDescription($this->config()->get('AllowedUsesPerCustomerDescription')),
                            NumericField::create('UsesPerCode')
                                ->setTitle('Allowed Uses Per Code (0 for unlimited)')
                                ->setDescription($this->config()->get('UsesPerCodeDescription')),
                            DropdownField::create('Combinable')
                                ->setSource(self::YES_NO)
                                ->setTitle('Is this Coupon Combinable')
                                ->setDescription($this->config()->get('CombinableDescription')),
                            DropdownField::create('MultipleCodesAllowed')
                                ->setSource(self::YES_NO)
                                ->setTitle('Can multiple Codes from this coupon be used?')
                                ->setDescription($this->config()->get('MultipleCodesAllowedDescription')),
                            DropdownField::create('ExcludeCategoryDiscounts')
                                ->setSource(self::YES_NO)
                                ->setTitle('Exclude Category Discounts?')
                                ->setDescription($this->config()->get('ExcludeCategoryDiscountsDescription')),
                            DropdownField::create('ExcludeLineItemDiscounts')
                                ->setSource(self::YES_NO)
                                ->setTitle('Exclude Line Item Discounts?')
                                ->setDescription($this->config()->get('ExcludeLineItemDiscountsDescription')),
                            DropdownField::create('IsTaxable')
                                ->setSource(self::YES_NO)
                                ->setTitle('Tax items before discount?')
                                ->setDescription($this->config()->get('CombinableDescription'))
                        )
                    ),
                ]
            );

            $startDate->hideUnless('DateRestricted')->isEqualTo(true);
            $endDate->hideUnless('DateRestricted')->isEqualTo(true);

            if ($this->exists()) {
                $fields->addFieldToTab(
                    'Root.DiscountDetails',
                    GridField::create(
                        'CouponTiers',
                        'Discount Details',
                        $this->CouponTiers(),
                        $couponTiersConfig = GridFieldConfig_RelationEditor::create()
                    )
                );

                $couponTiersConfig->removeComponentsByType([
                    GridFieldAddExistingAutocompleter::class,
                ]);

                $fields->addFieldToTab(
                    'Root.CouponCodes',
                    GridField::create(
                        'Codes',
                        'Coupon Codes',
                        $this->Codes(),
                        $couponCodesConfig = GridFieldConfig_RelationEditor::create()
                    )
                );

                $couponCodesConfig->removeComponentsByType([
                    GridFieldAddExistingAutocompleter::class,
                ]);

                if ($this->getHasBeenSyncd()) {
                    $fields->addFieldsToTab(
                        'Root.CouponInformation',
                        [
                            ReadonlyField::create('UsesToDate')
                                ->setTitle('Coupon Uses to Date'),
                            ReadonlyField::create('FoxyDateCreated')
                                ->setTitle('Date created in Foxy'),
                            ReadonlyField::create('FoxyDateModified')
                                ->setTitle('Date last modified in Foxy'),
                        ]
                    );
                }
            } else {
                foreach ($this->config()->get('defaults') as $fieldName => $default) {
                    $fields->dataFieldByName($fieldName)->setValue($default);
                }
            }
        });

        return parent::getCMSFields();
    }

    /**
     * @return ValidationResult
     */
    public function validate()
    {
        $result = parent::validate();

        if (!$this->Name) {
            $result->addFieldError('Name', 'Name is required');
        }

        if ($this->DateRestricted) {
            if (!$this->StartDate) {
                $result->addFieldError('StartDate', "A coupon start date is required");
            }

            if (!$this->EndDate) {
                $result->addFieldError('EndDate', "A coupon end date is required");
            }

            if ($this->StartDate >= $this->EndDate) {
                $result->addError("The coupon start date can not be after the end date");
            }
        }

        return $result;
    }

    /**
     * @param null $member
     * @param array $context
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, self::COUPON_CREATE_PERMISSION_CODE);
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, self::COUPON_EDIT_PERMISSION_CODE);
    }

    /**
     * @param null $member
     * @return bool|int
     */
    public function canDelete($member = null)
    {
        return Permission::checkMember($member, self::COUPON_DELETE_PERMISSION_CODE);
    }

    /**
     * @param null $member
     * @return bool|int
     */
    public function canView($member = null)
    {
        return Permission::checkMember($member, self::COUPON_VIEW_PERMISSION_CODE);
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            self::COUPON_CREATE_PERMISSION_CODE => [
                'name' => _t(__CLASS__ . '.PERMISSION_CREATE_DESCRIPTION', 'Create Foxy Coupons'),
                'help' => _t(__CLASS__ . '.PERMISSION_CREATE_HELP', 'Allow permission to create coupons.'),
                'category' => _t('SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY', 'Foxy Coupons'),
                'sort' => 100,
            ],
            self::COUPON_EDIT_PERMISSION_CODE => [
                'name' => _t(__CLASS__ . '.PERMISSION_EDIT_DESCRIPTION', 'Manage access rights for content'),
                'help' => _t(__CLASS__ . '.PERMISSION_EDIT_HELP', 'Allow permission to edit coupons.'),
                'category' => _t('SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY', 'Foxy Coupons'),
                'sort' => 100,
            ],
            self::COUPON_DELETE_PERMISSION_CODE => [
                'name' => _t(__CLASS__ . '.PERMISSION_DELETE_DESCRIPTION', 'Manage access rights for content'),
                'help' => _t(__CLASS__ . '.PERMISSION_DELETE_HELP', 'Allow permission to delete coupons.'),
                'category' => _t('SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY', 'Foxy Coupons'),
                'sort' => 100,
            ],
            self::COUPON_VIEW_PERMISSION_CODE => [
                'name' => _t(__CLASS__ . '.PERMISSION_VIEW_DESCRIPTION', 'Manage access rights for content'),
                'help' => _t(__CLASS__ . '.PERMISSION_VIEW_HELP', 'Allow permission to view coupons.'),
                'category' => _t('SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY', 'Foxy Coupons'),
                'sort' => 100,
            ],
        ];
    }

    /**
     * @return DBField
     */
    public function getHasBeenSyncd()
    {
        return DBField::create_field(DBBoolean::class, $this->FoxyDateCreated && $this->FoxyDateModified);
    }
}
